<?php
/**
 * Created by PhpStorm.
 * User: Wukong
 * Date: 2020/9/27
 * Time: 12:23
 */

namespace HelloPeterlee\LaravelUploader\Http\Controllers;

use HelloPeterlee\LaravelUploader\Services\ImageUpload;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;

class SlimImageUploadController extends BaseController
{
    public function upload()
    {
        $request = request();
        $strategy = $request->get('strategy', 'default');
        $config = uploader_strategy($strategy);
        $directory = Arr::get($config, 'directory', '{Y}/{m}/{d}');
        $createThumb = Arr::get($config, 'createThumb', false);

        if ($request->has('slim') && $request->slim[0]) {

            $output = $request->slim[0];
            $output = json_decode($output, TRUE);

            if (isset($output) && isset($output['output']) && isset($output['output']['image']))
                $image = $output['output']['image'];

            if (isset($image)) {
                if ($createThumb) {
                    $data = app(ImageUpload::class)->saveBase64ImageAndCreateThumb($image, $directory, 'jpg');
                } else {
                    $data = app(ImageUpload::class)->saveBase64Image($image, $directory, 'jpg');
                }
                if (is_array($data) && $data['code'] && $data['code'] == 0) {
                    // 写入数据库
                }
                return $data;
            }
            return '没有图片文件';
        }
        return '非使用slim cropper裁剪';
    }
}