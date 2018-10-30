<?php

/*
 * This file is part of the overtrue/laravel-uploader.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace HelloPeterlee\LaravelUploader\Services;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUpload
{
    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\FilesystemManager
     */
    protected $filesystem;

    /**
     * Create a new ImageUploadService instance.
     *
     * @param \Illuminate\Filesystem\FilesystemManager $filesystem
     */
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Construct the data URL for the JSON body.
     *
     * @param string $mime
     * @param string $content
     *
     * @return string
     */
    protected function getDataUrl($mime, $content)
    {
        $base = base64_encode($content);

        return 'data:' . $mime . ';base64,' . $base;
    }

    /**
     * Handle the file upload. Returns the response body on success, or false
     * on failure.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
     * @param string $disk
     * @param string $filename
     * @param string $dir
     *
     * @param bool $max_width
     * @param bool $want_dataURL
     * @return array|bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function store(UploadedFile $file, $disk, $filename, $dir = '', $max_width = false, $want_dataURL = false)
    {
        $hashName = str_ireplace('.jpeg', '.jpg', $filename);

        $dir = $this->formatDir($dir);

        $mime = $file->getMimeType();

        $path = $this->filesystem->disk($disk)->putFileAs($dir, $file, $hashName);

        if (!$path) {
            throw new Exception('Failed to store file.');
        }

        $reduce_path = '';
        if ($max_width > 0) { //如果是图片需要压缩尺寸
            $reduceResult = $this->reduceSize($this->filesystem->disk($disk)->path($path), $max_width);
            $reduce_path = $dir . '/' . pathinfo($hashName, PATHINFO_FILENAME) . '.s' . '.' . pathinfo($hashName, PATHINFO_EXTENSION);
            $this->filesystem->disk($disk)->put($reduce_path, $reduceResult['data']); // 重新保存
//            $width = $reduceResult['image']->width();
//            $height = $reduceResult['image']->height();
//            $size = $disk->size($path);
        }

        $data = [
            'success' => true,
            'filename' => $hashName,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getClientSize(),
            'storage_path' => $path,
            'relative_url' => str_replace(env('APP_URL'), '', Storage::disk($disk)->url($path)),
            'url' => Storage::disk($disk)->url($path),
        ];

        if ($want_dataURL) {
            $data['dataURL'] = $this->getDataUrl($mime, $this->filesystem->disk($disk)->get($path));
        }
        if ($max_width > 0) {
            $data['reduce_relative_url'] = str_replace(env('APP_URL'), '', Storage::disk($disk)->url($reduce_path));
        }
        return $data;
    }

    /**
     * Replace date variable in dir path.
     *
     * @param string $dir
     *
     * @return string
     */
    protected function formatDir($dir)
    {
        $replacements = [
            '{Y}' => date('Y'),
            '{m}' => date('m'),
            '{d}' => date('d'),
            '{H}' => date('H'),
            '{i}' => date('i'),
        ];

        return str_replace(array_keys($replacements), $replacements, $dir);
    }

    /**
     * Delete a file from disk.
     *
     * @param string $path
     *
     * @return array
     */
    public function delete($path, $disk)
    {
        if (0 === stripos($path, 'storage')) {
            $path = substr($path, strlen('storage'));
        }

        $this->filesystem->disk($disk)->delete($path);
    }

    /**
     * 剪裁图片
     *
     * @param $image
     * @param $max_width
     * @return array
     */
    protected function reduceSize($image, $max_width)
    {
        // 先实例化，传参是文件的磁盘物理路径
        $image = Image::make($image);

        // 进行大小调整的操作
        $image->resize($max_width, null, function ($constraint) {

            // 设定宽度是 $max_width，高度等比例双方缩放
            $constraint->aspectRatio();

            // 防止裁图时图片尺寸变大
            $constraint->upsize();
        });

        return ['data' => $image->encode(pathinfo($image->basePath(), PATHINFO_EXTENSION)), 'image' => $image];
    }
}
