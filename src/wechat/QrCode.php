<?php

namespace Kuiba\wechat;

use think\facade\Cache;


class QrCode
{
    /**
     * 获取小程序二维码（带参数）
     *
     * @param [type] $APPID
     * @param [type] $APPSECRET
     * @param [type] $param 参数
     * @param [type] $name  图片名称
     * @param [type] $path  文件路径
     * @return string
     */
    public function getAppletQrCode($APPID = null, $APPSECRET = null, $param = NULL, $name = null, $path = NULL)
    {

        if (is_null($APPID) || is_null($APPSECRET)) {
            return null;
        }
        //获取access_token
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";
        $ACCESS_TOKEN = "";
        if (!Cache::get('access_token')) {
            $json = httpRequest($access_token);
            $json = json_decode($json, true);
            //缓存access_token
            Cache::set('access_token', $json['access_token'], 7200);
            $ACCESS_TOKEN = $json["access_token"];
        }
        $ACCESS_TOKEN =  Cache::get('access_token');

        $qcode = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$ACCESS_TOKEN";

        if (is_null($param)) return null;
        $param = json_encode($param);
        //POST参数
        $result = http_request($qcode, $param, "POST");
        is_null($name) && $name = 'qrcode' . time() . rand(100, 999) . '.png';
        is_null($path) && $path = '/static/qrcode/';

        $file_put_path = '../public' . $path;
        //不存在则生成文件夹
        !is_dir($file_put_path) && mkdir($file_put_path, 0777, true);
        //生成二维码
        file_put_contents($file_put_path . $name, $result);
        return  $path . $name;
    }
}