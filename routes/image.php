<?php

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Enums\GroupConfigKey;
use App\Enums\ConfigKey;

// 图片 + 视频 + 其他文件后缀
$extensions = array_unique(array_merge(
    (array) config('convention.group.accepted_file_suffixes'),
    (array) config('convention.group.accepted_video_suffixes'),
    (array) config('convention.group.accepted_other_file_suffixes'),
));
Route::middleware('cache.headers:public;max_age=2628000;etag')->group(function () use ($extensions) {
    $extensions = array_merge(array_map('strtoupper', $extensions), array_map('strtolower', $extensions));
    Route::any('{key}.{extension}', [
        Controller::class, 'output',
    ])->where('extension', implode('|', $extensions));
});
