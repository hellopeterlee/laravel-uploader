<?php

/*
 * This file is part of the overtrue/laravel-uploader.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace HelloPeterlee\LaravelUploader;

use Illuminate\Support\Facades\Facade;

class LaravelUploader extends Facade
{
    public static function routes()
    {
        if (!self::$app->routesAreCached()) {
            self::$app->make('router')->post('files/upload', [
                'uses' => '\HelloPeterlee\LaravelUploader\Http\Controllers\UploadController@upload',
                'as' => 'file.upload',
            ]);
        }
    }
}
