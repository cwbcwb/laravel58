<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use phpqrcode;

class PosterController extends Controller
{
    /**
     * 实现海报
     *
     * @param Request $request 请求参数
     */
    public function getPoster(Request $request){
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
        // 确定本地存储二维码的名称
        $qrcodeName = 'qrcode'.$time.'.png';
        // 生成二维码
        $img->png($qrcodeText, $qrcodeName, $errorCorrectionLevel, $qrcodeSize, 0);
        if(Cache::has($postUrl)){
            //从缓存中获取底图
            $path = Cache::get($postUrl);
            $imageRes = imagecreatefromjpeg($path);
        }else {
            // 获取海报图片大小
            $backgroundInfo = getimagesize($postUrl);
            // 根据图片后缀名生成对应创建图像的函数名
            $backgroundFunc = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
            // 创建图像
            $background = $backgroundFunc($postUrl);
            // 获得图像宽度
            $backgroundWidth = imagesx($background);
            // 获得图像高度
            $backgroundHeight = imagesy($background);
            // 绘制指定宽高的图像
            $imageRes = imagecreatetruecolor($backgroundWidth, $backgroundHeight);
            $color = imagecolorallocate($imageRes, 0, 0, 0);
            // 指定颜色的区域填充
            imagefill($imageRes, 0, 0, $color);
            // 将原图像复制到新图像上
            imagecopy($imageRes, $background, 0, 0, 0, 0, $backgroundWidth, $backgroundHeight);
            $postName = 'post'.$time.'.jpg';
            imagejpeg($imageRes, $postName);
            $postPath = public_path().'/'.$postName;
            Cache::put($postUrl,$postPath);
        }
        // 从本地存储获得二维码的位置
        $qrcode = public_path().'/'.$qrcodeName;
        $srcImg = imagecreatefrompng($qrcode);
        // 获取宽度
        $srcW = imagesx($srcImg);
        // 获取高度
        $srcH = imagesy($srcImg);
        // 将该二维码复制到设置好了的图像的指定位置
        imagecopy($imageRes, $srcImg, $qrcodeX, $qrcodeY, 0, 0, $srcW, $srcH);
        // 根据需求生成图片
        ob_clean();
        if(!$request->get('picName')){
            header("Content-type:image/jpeg");
            imagejpeg($imageRes);
        }else{
            $picName = $request->get('picName');
            $picName = $picName.'.jpg';
            // 保存海报图片到本地
            imagejpeg($imageRes, $picName);
            // 获得海报图片在本地的位置
            $picPath = public_path().'/'.$picName;
            $fp=fopen($picPath,"r");
            $file_size=filesize($picPath);
            // 返回的文件
            header("Content-type: application/octet-stream");
            // 按照字节大小返回
            header("Accept-Ranges:bytes");
            // 返回文件大小
            header("Accept-Length:$file_size");
            // 这里是客户端的弹出对话框，对应的文件名
            header("Content-Disposition: attachment;filename=".$picName);
            // 读取的最大字节数
            $buffer=1024;
            $file_count=0;
            while(!feof($fp) && $file_count<$file_size){
                $file_data=fread($fp,$buffer);
                $file_count+=$buffer;
                echo $file_data;
            }
            fclose($fp);
        }
        // 销毁画布
        imagedestroy($imageRes);
    }
    public function test(){
        return view('admin.member.add');
    }
}

