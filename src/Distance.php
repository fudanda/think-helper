<?php

namespace Kuiba;

class Distance
{

    protected $coord1 = [];
    protected $coord2 = [];

    public function  __construct($coord1 = [], $coord2 = [])
    {
        $this->coord1 = $coord1;
        $this->coord2 = $coord2;
    }

    public static function make($coord1 = [], $coord2 = [])
    {
        return new self($coord1, $coord2);
    }

    /**
     * 根据经纬度算距离，返回结果单位是米/m，先纬度，后经度
     * @param $coord1
     * @param $coord2
     * @return float|int
     */
    public function Distance($coord1 = [], $coord2 = [])
    {
        $coord1 = !empty($coord1) ? $coord1 : $this->coord1;
        $coord2 = !empty($coord2) ? $coord2 : $this->coord2;
        $lat1 = $coord1[0];
        $lng1 = $coord1[1];
        $lat2 = $coord2[0];
        $lng2 = $coord2[1];

        $EARTH_RADIUS = 6370.856; //地球平均半径
        $radLat1 = $this->rad($lat1);
        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s * 10000) / 10000 * 1000;

        return $s;
    }
    private function rad($d)
    {
        return $d * M_PI / 180.0;
    }
}
