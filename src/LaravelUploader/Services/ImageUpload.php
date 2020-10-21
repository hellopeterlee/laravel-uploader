<?php
/**
 * Created by PhpStorm.
 * User: Wukong
 * Date: 2020/9/27
 * Time: 12:27
 */

namespace HelloPeterlee\LaravelUploader\Services;

use Illuminate\Filesystem\FilesystemManager;

class ImageUpload
{
    protected $disk;

    public function __construct(FilesystemManager $filesystem)
    {
        $this->disk = $filesystem->disk('public');
    }

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

    private function mkFileSavePath($folder)
    {
        $disk_folder = $this->disk->path('');

        $formatDir = $this->formatDir($folder);
        $file_path = $disk_folder . '/' . $formatDir;
        $path_in_disk = $formatDir;
        // 创建文件夹
        if (!file_exists($file_path)) {
            mkdir($file_path, 0777, true);
        }
        return [$file_path, $path_in_disk];
    }

    private function image_data($file)
    {
        if (stripos($file, 'data:image/jpeg;base64,') === 0) {
            $img = base64_decode(str_replace('data:image/jpeg;base64,', '', $file));
        } else if (stripos($file, 'data:image/png;base64,') === 0) {
            $img = base64_decode(str_replace('data:image/png;base64,', '', $file));
        } else {
            throw new \Exception('非图片文件');
        }
        return $img;
    }

    /**
     *  将base64数据的图片存入指定位置指定名字下
     *  $file base64文件
     *  $folder 文件夹名
     *  $name 文件名
     *  $file_prefix 后缀名
     * @param $file
     * @param $folder
     * @param $name
     * @param $file_prefix
     * @return array
     */
    public function saveBase64Image($file, $folder, $file_prefix)
    {
        list($upload_path, $path_in_disk) = $this->mkFileSavePath($folder);

        if (stripos($file, 'data:image/jpeg;base64,') === 0) {
            $img = base64_decode(str_replace('data:image/jpeg;base64,', '', $file));
        } else if (stripos($file, 'data:image/png;base64,') === 0) {
            $img = base64_decode(str_replace('data:image/png;base64,', '', $file));
        } else {
            return array('error' => '非图片文件');
        }

        $hashName = sprintf('%s.%s', md5($img), $file_prefix);

        $file_path_indisk = $path_in_disk . "/" . $hashName;
        $result = $this->disk->put($file_path_indisk, $img);

        if ($result == FALSE) {
            $data['code'] = -1;
            $data['success'] = false;
            $data['status'] = 'fail';
            $data['error'] = '写入文件失败，可能没有权限';
            return $data;
        }

        $data = [
            'code' => 0,
            'success' => true,
            'status' => 'success',
            'filename' => $hashName,
            'file_path_indisk' => $file_path_indisk,
            'storage_path' => str_replace('/storage/', '', $file_path_indisk),
            'relative_url' => str_replace(env('APP_URL'), '', $this->disk->url($file_path_indisk)),
            'url' => $this->disk->url($file_path_indisk),
        ];
        return $data;
    }


    public function saveBase64ImageAndCreateThumb($file, $folder, $file_prefix, $max_width)
    {
        $res = $this->saveBase64Image($file, $folder, $file_prefix);
//        $res = $this->save_base64_image($file, $folder, $file_prefix);
        if (!$res['success']) {
            return $res;
        }
        $file_path_indisk = $res['file_path_indisk'];

        $image = $this->disk->path($res['storage_path']);
        // 先实例化，传参是文件的磁盘物理路径
        $image = Image::make($image);

        // 进行大小调整的操作
        $image->resize($max_width, null, function ($constraint) {
            // 设定宽度是 $max_width，高度等比例双方缩放
            $constraint->aspectRatio();
            // 防止裁图时图片尺寸变大
            $constraint->upsize();
        });

        $reduce_path = (pathinfo($file_path_indisk, PATHINFO_DIRNAME)) . '/' . $image->filename . '.thumb' . '.' . $image->extension ?? $file_prefix;
        $image_encode_data = $image->encode(pathinfo($image->basePath(), PATHINFO_EXTENSION));
        $this->disk->put($reduce_path, $image_encode_data); // 重新保存

        $resize_res = [
            'resize_storage_path' => $reduce_path,
            'resize_image_url' => $this->disk->url($reduce_path),
            'resize_relative_url' => str_replace(env('APP_URL'), '', $this->disk->url($reduce_path))
        ];
        return array_merge($res, $resize_res);
    }
}