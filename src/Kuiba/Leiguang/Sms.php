<?php

namespace Kuiba\Leiguang;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Sms
{
    // // /*
    // *构造函数
    // *@param key    AccessKeyId      key
    // *@param secret AccessKeySecret 密钥
    // *@param sign   SignName        签名
    // */
    // public function __construct($key = ACCESSKEYID, $secret = ACCESSKEYSECRET, $sign = SIGNNAME)
    // {
    //     $this->key = $key;
    //     $this->type = $secret;
    //     $this->type = $sign;
    // }


    /**
     * 发送短息
     *
     * @param [string] $phone   手机号
     * @param [string] $templateCode  模板CODE
     * @param array $templateParam   短信模板变量对应的实际值
     * @param array $accessKey       基本参数accessKeyId,accessSecret,signName
     * @return void
     */
    public static  function Send($phone, $templateCode, $templateParam, $accessKey = [])
    {
        try {

            $key = $accessKey['accessKeyId'];
            $secret = $accessKey['accessSecret'];
            $signName = $accessKey['signName'];


            AlibabaCloud::accessKeyClient($key, $secret)
                ->regionId('cn-hangzhou')
                ->asDefaultClient();

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => $signName,
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => json_encode($templateParam, JSON_UNESCAPED_UNICODE),
                    ],
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            return $e->getErrorMessage();
        } catch (ServerException $e) {
            return $e->getErrorMessage();
        }
    }
    /**
     * 查询短信
     *
     * @param [string] $phone   手机号
     * @param [type] $sendDate  短信发送日期，支持查询最近30天的记录。格式为yyyyMMdd，例如20181225
     * @param array $accessKey 基本参数accessKeyId,accessSecret
     * @return void
     */
    public static  function Query($phone, $sendDate, $accessKey = [])
    {
        try {
            $key = $accessKey['accessKeyId'];
            $secret = $accessKey['accessSecret'];
            AlibabaCloud::accessKeyClient($key, $secret)
                ->regionId('cn-hangzhou')
                ->asDefaultClient();
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('QuerySendDetails')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumber' => $phone,
                        'SendDate' => $sendDate,
                        'PageSize' => "1",
                        'CurrentPage' => "1",
                    ],
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            return $e->getErrorMessage();
        } catch (ServerException $e) {
            return $e->getErrorMessage();
        }
    }
}