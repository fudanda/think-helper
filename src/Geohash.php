<?php

namespace Kuiba;

/**
 * Geohash 距离排序
 */
class Geohash
{
    private $BASE32 = "0123456789bcdefghjkmnpqrstuvwxyz";
    private $BASE32MAP = [];

    private $MAX_LATITUDE   = 90;
    private $MIN_LATITUDE   = -90;
    private $MAX_LONGITUDE  = 180;
    private $MIN_LONGITUDE  = -180;


    private $MinLat = '';
    private $MaxLat  = '';
    private $MinLng = '';
    private $MaxLng = '';
    private $bits   = '';
    private $hash   = '';

    function __construct()
    {
        for ($i = 0; $i < 32; $i++) {
            $this->BASE32MAP[substr($this->BASE32, $i, 1)] = str_pad(decbin($i), 5, "0", STR_PAD_LEFT);
        }
    }
    public static function make()
    {
        return new self();
    }
    public function Width()
    {
        return $this->MaxLng - $this->MinLng;
    }
    public function Height()
    {
        return $this->MaxLat - $this->MinLat;
    }
    // geohash length	width	    height
    // 1	            5,009.4km	4,992.6km
    // 2	            1,252.3km	624.1km
    // 3	            156.5km	    156km
    // 4	            39.1km	    19.5km
    // 5	            4.9km	    4.9km
    // 6	            1.2km	    609.4m
    // 7	            152.9m	    152.4m
    // 8	            38.2m	    19m
    // 9	            4.8m	    4.8m
    // 10	            1.2m	    59.5cm
    // 11	            14.9cm	    14.9cm
    // 12	            3.7cm	    1.9cm


    public function Encode($latitude, $longitude, $precision)
    {
        $this->MinLat = $this->MIN_LATITUDE;
        $this->MaxLat = $this->MAX_LATITUDE;
        $this->MinLng = $this->MIN_LONGITUDE;
        $this->MaxLng = $this->MAX_LONGITUDE;
        $latmid = 0;
        $latmid = 0;
        list($bit, $length, $isEvent) = ['', 0, true];

        for ($i = $length; $i < $precision; $i++) {

            //纬度
            $latmid = ($this->MinLat + $this->MaxLat) / 2;

            if ($latmid < $latitude) {

                $bit .= 1;

                $this->MinLat = $latmid;
            } else {
                $this->MaxLat = $latmid;
                $bit .= 0;
            }

            $isEvent = !$isEvent;

            //经度
            $lngmid = ($this->MinLng + $this->MaxLng) / 2;

            if ($lngmid < $longitude) {
                $bit .= 1;
                $this->MinLng = $lngmid;
            } else {
                $this->MaxLng = $lngmid;
                $bit .= 0;
            }
        }
        //转换为hash
        $hash = '';
        for ($i = 0; $i < strlen($bit); $i += 5) {
            $n = bindec(substr($bit, $i, 5));
            $hash = $hash . $this->BASE32[$n];
        }
        $this->hash = $hash;
        $this->bits = $bit;

        return $hash;
    }


    public function Decode($hash)
    {
        $binary = "";
        $hl = strlen($hash);
        for ($i = 0; $i < $hl; $i++) {
            $binary .= $this->BASE32MAP[substr($hash, $i, 1)];
        }

        $bl = strlen($binary);
        $blat = "";
        $blong = "";
        for ($i = 0; $i < $bl; $i++) {
            if ($i % 2) {
                $blong = $blong . substr($binary, $i, 1);
            } else {
                $blat = $blat . substr($binary, $i, 1);
            }
        }
        $lat = $this->binDecode($blat, -90, 90);
        $long = $this->binDecode($blong, -180, 180);

        return array($lat, $long);
    }
    public function Neighbors($hash)
    {

        // 上下左右
        // $geohashUp        = $this->Encode((b.MinLat+b.MaxLat)/2+b.Height(), (b.MinLng+b.MaxLng)/2, precision)
        // $geohashDown      = $this->Encode((b.MinLat+b.MaxLat)/2-b.Height(), (b.MinLng+b.MaxLng)/2, precision)
        // $geohashLeft      = $this->Encode((b.MinLat+b.MaxLat)/2, (b.MinLng+b.MaxLng)/2-b.Width(), precision)
        // $geohashRight     = $this->Encode((b.MinLat+b.MaxLat)/2, (b.MinLng+b.MaxLng)/2+b.Width(), precision)
        // $geohashLeftUp    = $this->Encode((b.MinLat+b.MaxLat)/2+b.Height(), (b.MinLng+b.MaxLng)/2-b.Width(), precision)
        // $geohashLeftDown  = $this->Encode((b.MinLat+b.MaxLat)/2-b.Height(), (b.MinLng+b.MaxLng)/2-b.Width(), precision)
        // $geohashRightUp   = $this->Encode((b.MinLat+b.MaxLat)/2+b.Height(), (b.MinLng+b.MaxLng)/2+b.Width(), precision)
        // $geohashRightDown = $this->Encode((b.MinLat+b.MaxLat)/2-b.Height(), (b.MinLng+b.MaxLng)/2+b.Width(), precision)

    }

    private function binDecode($binary, $min, $max)
    {
        $mid = ($min + $max) / 2;
        if (strlen($binary) == 0) {
            return $mid;
        }
        $bit = substr($binary, 0, 1);
        $binary = substr($binary, 1);
        if ($bit == 1) {
            return $this->binDecode($binary, $mid, $max);
        } else {
            return $this->binDecode($binary, $min, $mid);
        }
    }

    private function binEncode($number, $min, $max, $bitcount)
    {
        if ($bitcount == 0) {
            return "";
        }
        $mid = ($min + $max) / 2;
        if ($number > $mid) {
            return "1" . $this->binEncode($number, $mid, $max, $bitcount - 1);
        } else {
            return "0" . $this->binEncode($number, $min, $mid, $bitcount - 1);
        }
    }

    // private function calcError($bits, $min, $max)
    // {
    //     $err = ($max - $min) / 2;
    //     while ($bits--) {
    //         $err /= 2;
    //     }
    //     return $err;
    // }

    // private function precision($number)
    // {
    //     $precision = 0;
    //     $pt = strpos($number, '.');
    //     if ($pt !== false) {
    //         $precision = - (strlen($number) - $pt - 1);
    //     }
    //     return pow(10, $precision) / 2;
    // }
}
