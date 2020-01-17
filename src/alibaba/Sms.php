<?php

namespace Kuiba\alibaba;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Sms
{
    public static  function SendSms($accessKeyId, $accessSecret, array $query)
    {
        try {
            empty($query) && Throwanexception('无效参数');
            AlibabaCloud::accessKeyClient($accessKeyId, $accessSecret)
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
                    'query' => $query
                ])
                ->request();
            return $result->toArray();
        } catch (ClientException $e) {
            $err_msg['Message'] = $e->getErrorMessage();
            return $err_msg;
        } catch (ServerException $e) {
            $err_msg['Message'] = $e->getErrorMessage();
            return $err_msg;
        }
    }
}