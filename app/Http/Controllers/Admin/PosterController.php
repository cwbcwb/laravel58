<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use phpqrcode;

class PosterController extends Controller
{
    /**
     * 实现海报
     *
     * @param Request $request 请求参数
     * @return void
     */
    public function getPoster(Request $request)
    {
        // 获取海报底图地址
        $postUrl = $request->get('postUrl');
        // 获取二维码内容
        $qrcodeText = $request->get('qrcodeText');
        // 获取二维码在海报中的横坐标
        $qrcodeX = $request->get('qrcodeX');
        // 获取二维码在海报中的纵坐标
        $qrcodeY = $request->get('qrcodeY');
        // 获取二维码在海报中的尺寸
        $qrcodeSize = $request->get('qrcodeSize');
        // 容错级别
        $errorCorrectionLevel = 'H';
        $img = new \QRcode();
        $time = microtime(true)*10000;
        // 所有生成图片临时放置在这个目录中
        $imgPath = storage_path('app/public/');
        // 确定本地存储二维码的名称
        $qrcodeName = 'qrcode'.$time.'.png';
        // 确定二维码的位置
        $qrcodePath = $imgPath.$qrcodeName;
        // 生成二维码
        $img->png($qrcodeText, $qrcodePath, $errorCorrectionLevel, $qrcodeSize, 0);
        if (Cache::has(md5($postUrl))) {
            // 从缓存中获取底图
            $data = Cache::get(md5($postUrl));
            $fileName = 'post'.$time.'.png';
            $filePath = $imgPath.$fileName;
            // 将内容写入文件中
            Storage::put($fileName, $data);
            $imageRes = imagecreatefrompng($filePath);
            // 删除文件
            Storage::delete($fileName);
        } else {
            // 获取海报图片大小
            $backgroundInfo = getimagesize($postUrl);
            // 根据图片后缀名生成对应创建图像的函数名
            $backgroundFunc = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
            // 创建图像
            $imageRes = $backgroundFunc($postUrl);
            $data =file_get_contents($postUrl);
            Cache::put(md5($postUrl), $data);
        }
        // 从本地存储获得二维码的位置
        $srcImg = imagecreatefrompng($qrcodePath);
        Storage::delete($qrcodeName);
        // 获取宽度
        $srcW = imagesx($srcImg);
        // 获取高度
        $srcH = imagesy($srcImg);
        // 将该二维码复制到设置好了的图像的指定位置
        imagecopy($imageRes, $srcImg, $qrcodeX, $qrcodeY, 0, 0, $srcW, $srcH);
        // 根据需求生成图片
        $picLocalName = 'pic'.$time.'.jpg';
        $picLocalPath = $imgPath.$picLocalName;
        imagejpeg($imageRes, $picLocalPath);
        if (!$request->get('picName')) {
            $headers = array(
                'content-type' => 'image/jpeg'
            );
            // 生成图片
            return response()->file($picLocalPath, $headers);
        } else {
            $picName = $request->get('picName');
            $picName = $picName.'.jpg';
            // 下载图片
            return response()->download($picLocalPath, $picName);
        }
    }
    public function test()
    {
        return view('admin.member.add');
    }
}

