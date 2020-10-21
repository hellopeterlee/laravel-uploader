<?php

/*
 * This file is part of the overtrue/laravel-uploader.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace HelloPeterlee\LaravelUploader\Http\Controllers;

use HelloPeterlee\LaravelUploader\Services\FileUpload;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Intervention\Image\ImageManager;

/**
 * class UploadController.
 */
class UploadController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(config('uploader.middleware', []));
    }

    /**
     * Handle file upload.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        $strategy = $request->get('strategy', 'default');
        $config = uploader_strategy($strategy);

        $inputName = Arr::get($config, 'input_name', 'file');
        $directory = Arr::get($config, 'directory', '{Y}/{m}/{d}');
        $disk = Arr::get($config, 'disk', 'public');
        $max_width = Arr::get($config, 'max_width', false);
        $wantDataURL = Arr::get($config, 'want_dataurl', false);
        if (!$request->hasFile($inputName)) {
            return [
                'code' => -1,
                'msg' => 'no file found.',
            ];
        }
        $file = $request->file($inputName);

        $filename = $this->getFilename($file, $config);

        return app(FileUpload::class)->store($file, $disk, $filename, $directory, $max_width, $wantDataURL);
    }

    public function getFilename(UploadedFile $file, $config)
    {
        switch (Arr::get($config, 'filename_hash', 'default')) {
            case 'origional':
                return $file->getClientOriginalName();
            case 'md5_file':
                return md5_file($file->getRealPath()) . '.' . $file->getClientOriginalExtension();
//                return md5_file($file->getRealPath()) . '.' . $file->guessExtension();
                break;
            case 'random':
            default:
                return $file->hashName();
        }
    }

    /**
     * Delete file.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $result = ['result' => app(FileUpload::class)->delete($request->file)];

//        Event::fire(new FileDeleted($request->file));

        return $result;
    }

    /*
    imgUrl: http://tsl.peirenlei.cn/storage/crop/2019/11/59a58cb4f4f140d0cc4ec732ec937b5c.png
    imgInitW: 2034
    imgInitH: 1042
    imgW: 621.700575815739
    imgH: 318.49164208456244
    imgY1: 0
    imgX1: 0
    cropH: 298
    cropW: 261
    rotation: 0
    width: 261
    height: 298
     * */
    public function crop(Request $request)
    {
        $form_data = $request->all();
        $image_url = $form_data['imgUrl'];

        // /storage/crop/2019/11/59a58cb4f4f140d0cc4ec732ec937b5c.png
        $image_url = public_path(array_get(parse_url($image_url), 'path'));

        // resized sizes
        $imgW = $form_data['imgW'];
        $imgH = $form_data['imgH'];
        // offsets
        $imgY1 = $form_data['imgY1'];
        $imgX1 = $form_data['imgX1'];
        // crop box
        $cropW = $form_data['width'];
        $cropH = $form_data['height'];
        // rotation angle
        $angle = $form_data['rotation'];
        $filename_array = explode('/', $image_url);
        $filename = $filename_array[sizeof($filename_array) - 1];
        $manager = new ImageManager();
        $image = $manager->make($image_url);

        $strategy = $request->get('strategy', 'default');
        $config = uploader_strategy($strategy);

        $directory = array_get($config, 'directory', '{Y}/{m}/{d}');
        $disk = array_get($config, 'disk', 'public');

        $image
            ->encode('jpg')
            ->resize($imgW, $imgH)
            ->rotate(-$angle)
            ->crop($cropW, $cropH, $imgX1, $imgY1);
//            ->save(env('UPLOAD_PATH') . 'cropped-' . $filename);
        $result = app(FileUpload::class)->saveCropedImage($image, $disk, $directory, $filename);

        if (!$image) {
            return response()->json([
                'code' => -1,
                'status' => 'error',
                'message' => '保存图片出现错误',
                'msg' => '保存图片出现错误',
            ], 200);
        }
        return response()->json([
            'code' => 0,
            'status' => 'success',
            'storage_path' => $result['storage_path'],
            'relative_url' => $result['relative_url'],
            'url' => $result['url'],
        ], 200);
    }
}
