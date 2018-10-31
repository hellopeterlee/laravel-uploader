<?php

/*
 * This file is part of the overtrue/laravel-uploader.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    'strategies' => [
        /*
         * default strategy.
         */
        'default' => [
            'input_name' => 'file',
            'mimes' => ['image/jpeg', 'image/png', 'image/bmp', 'image/gif'],
            'disk' => 'public',
            'directory' => 'uploads/{Y}/{m}/{d}', // directory,
            'max_file_size' => '2m',
            'filename_hash' => 'random', // random/md5_file/original
            'max_width' => false, //如果是图片文件，设置最大宽度，用于压缩
            'want_dataurl' => false, //返回结果中时候需要base64格式的dataurl
        ],

        // avatar extends default
        'avatar' => [
            'directory' => 'avatars/{Y}/{m}/{d}',
        ],
    ],
];

// @uploader('file', ['strategy' => 'avatar', 'data' => [$product->images]])
