<?php

namespace Kuiba\wechat;

use think\facade\Cache;


class OpenId
{
    /**
     * 获取小程序二维码（带参数）
     *
     * @param [type] $APPID
     * @param [type] $APPSECRET
     * @param [type] $JSCODE 临时登录凭证
     * @return string
     */
    public function getAppletOpenId($APPID = null, $APPSECRET = null, $JSCODE = NULL)
    {
        if (is_null($APPID) || is_null($APPSECRET) || is_null($JSCODE)) {
            return null;
        }
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$APPID&secret=$APPSECRET&js_code=$JSCODE&grant_type=authorization_code";
        $json = httpRequest($url);
        $json = json_decode($json, true);
        return $json;
    }
}