<?php

namespace App\Enums;

final class ImageType
{
    /** @var int 图片 */
    const Image = 1;

    /** @var int 视频 */
    const Video = 2;

    /** @var int 其他文件 */
    const File = 3;

    /**
     * 根据文件后缀判断文件类型
     *
     * @param  string  $extension
     * @return int
     */
    public static function fromExtension(string $extension): int
    {
        $extension = strtolower($extension);

        if (in_array($extension, (array) config('convention.group.' . GroupConfigKey::AcceptedVideoSuffixes, []), true)) {
            return self::Video;
        }

        if (in_array($extension, (array) config('convention.group.' . GroupConfigKey::AcceptedOtherFileSuffixes, []), true)) {
            return self::File;
        }

        return self::Image;
    }

    /**
     * 是否为图片类型
     *
     * @param  int  $type
     * @return bool
     */
    public static function isImage(int $type): bool
    {
        return $type === self::Image;
    }

    /**
     * 是否为视频类型
     *
     * @param  int  $type
     * @return bool
     */
    public static function isVideo(int $type): bool
    {
        return $type === self::Video;
    }

    /**
     * 是否为其他文件类型
     *
     * @param  int  $type
     * @return bool
     */
    public static function isFile(int $type): bool
    {
        return $type === self::File;
    }
}
