<?php

namespace kuiba;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

class  MyQrCode
{
    public function getQrCode($data = null, $name = null, $logo = null)
    {
        if (is_null($data)) {
            return false;
        }
        if (is_null($name)) {
            $name = time();
        }
        $qrCode = new QrCode($data);
        //设置前景色
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        //设置背景色
        $qrCode->setBackgroundColor(['r' => 250, 'g' => 255, 'b' => 255, 'a' => 10]);
        //设置二维码大小
        $qrCode->setSize(200);
        // $qrCode->setPadding(20);

        $qrCode->setWriterByName('png');
        $qrCode->setMargin(10);
        $qrCode->setEncoding('UTF-8');
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        if (!is_null($logo)) {
            // $logo = '../public/static/group/img/logo_nav.png';
            $qrCode->setLogoPath($logo);
            $qrCode->setLogoSize(50, 50);
        }

        $qrCode->setRoundBlockSize(true);
        $qrCode->setValidateResult(false);
        $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);
        $qrcodepath = '/static/qrcode/' . $name . '.png';
        $qrCode->writeFile('../public' . $qrcodepath);
        return $qrcodepath;
    }
}