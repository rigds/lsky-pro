# 视频与通用文件上传支持（V 2.1.1 video-file）

本项目原本只接受图片后缀，本次改造增加视频与其他文件的完整上传、存储、访问与后台管理能力。

## 一、核心变更

### 1. 文件类型枚举
`app/Enums/ImageType.php`（新增）
- Image = 1 / Video = 2 / File = 3
- fromExtension(string $extension): int 按配置自动归类

### 2. 配置项
`config/convention.php`

| 键 | 说明 |
|---|---|
| GroupConfigKey::AcceptedFileSuffixes | 原图片白名单，保持不变 |
| GroupConfigKey::AcceptedVideoSuffixes（新增） | mp4 webm mov m4v ogv mkv avi flv wmv ts mpg mpeg 3gp |
| GroupConfigKey::AcceptedOtherFileSuffixes（新增） | pdf zip rar 7z tar gz bz2 xz txt md json xml csv doc(x) xls(x) ppt(x) epub apk ipa exe dmg iso torrent mp3 flac wav aac ogg m4a opus ttf otf woff woff2 |
| all_file_suffixes（新增） | 上述三类合并，供路由约束使用 |
| ConfigKey::AppVersion | V 2.1 -> V 2.1.1 (video-file) |

`app/Enums/GroupConfigKey.php` 新增两个常量。

### 3. 数据表
`database/migrations/2024_01_01_000000_add_type_to_images_table.php`（新增）
- images.type tinyint，默认 1，带索引
- 升级时必须执行：php artisan migrate

### 4. 模型
`app/Models/Image.php`
- scopeImage() / scopeVideo() / scopeFile()
- isImage() / isVideo() / isFile()
- url / thumb_url 访问器对非图片返回真实文件地址

### 5. 上传服务
`app/Services/ImageService.php`
- 按类型选择对应白名单校验（组配置优先，回落到惯例配置）
- 图片压缩 / 格式转换 / 水印仅对图片类型生效，跳过 ico gif svg
- 视频：不转换、不加水印；thumb_url 回落到原文件（前端自行截帧）
- 其他文件：不生成缩略图、跳过内容扫描
- 宽高解析失败时置 0

### 6. 文件访问
`app/Http/Controllers/Controller.php`
- outputRaw() 输出原始文件，支持 Accept-Ranges / Range 请求（视频拖动进度必需）
- 非图片访问走 raw 输出，不再走图片解码

`routes/image.php`
- 路由后缀约束改用 all_file_suffixes

### 7. 校验
`app/Http/Requests/Admin/GroupRequest.php`
- 新增 accepted_video_suffixes / accepted_other_file_suffixes 的 array|in: 规则
- 图片白名单规则不变

### 8. 后台
- `resources/views/admin/image/video.blade.php`（新增）：视频列表，封面 + 播放图标，弹窗用 video 标签预览
- `resources/views/admin/image/file.blade.php`（新增）：文件列表，类型图标卡片，弹窗提供下载链接
- `app/Http/Controllers/Admin/ImageController.php`：新增 videos() / files()，listByType() 统一分页
- `routes/web.php`：新增 admin.videos / admin.files
- `resources/views/layouts/sidebar.blade.php`：新增「视频管理」「文件管理」入口
- `resources/views/admin/group/add.blade.php` / `edit.blade.php`：三组后缀多选框

### 9. 用户端上传
- `resources/views/components/upload.blade.php`
  - accept 属性合并三类后缀
  - 前端按后缀分流：其他文件 -> 类型图标；视频 -> 异步截取首帧作预览；图片 -> 原逻辑
  - 上传统计文案由「图片」改为「文件」
- `resources/views/user/images.blade.php`：列表项左上角显示类型角标（视频/文件）
- `app/Http/Controllers/User/ImageController.php`：列表接口返回 type、extension

## 二、部署步骤

    composer install
    php artisan migrate          # 必需：新增 images.type 列
    php artisan config:clear
    php artisan view:clear

若已有历史数据，type 默认 1（图片），无需回填。

## 三、注意事项

1. PHP 上传大小限制：视频通常很大，需同时调整 php.ini 的 upload_max_filesize、post_max_size，以及 Nginx 的 client_max_body_size，否则大文件会失败。
2. 执行时间限制：max_execution_time 建议调高或使用队列。
3. 存储策略：本地策略会占用磁盘；大视频建议配置对象存储策略。
4. 组权限：管理员需在「角色组」中勾选允许的视频/文件后缀，默认已带全套。
5. 视频内容不做 NSFW 扫描（原扫描仅支持图片）。
