<?php

namespace fdd\utils;

use fdd\Util;
use ZipArchive;

class Zip
{


    private $zipPathName = '';

    /**
     * Undocumented function
     *
     * @param [type] $basePath 原路径
     * @param [type] $zipPath  压缩包存放路径
     * @param [type] $zipName  压缩包名称默认time()
     */
    public function __construct($basePath = null, $zipPath = null, $zipName = null)
    {
        !is_dir($zipPath) && mkdir($zipPath, 0777, true);

        $basePath = iconv("UTF-8", "GBK", $basePath);
        $zipPathName = $zipPath . $zipName;
        $zip = new ZipArchive();
        if ($dh = opendir($basePath)) {
            $zip->open($zipPathName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $res = false;
            foreach (glob($basePath . "*") as $file) {
                $zip_filename = explode('/', $file);
                $zip_filename = end($zip_filename);
                $res = $zip->addFile($file, $zip_filename);
            }

            $zip->close(); //关闭处理的zip文件
            closedir($dh);
            //删除文件夹
            Util::deldir($basePath);
            if ($res) {
                $this->zipPathName = $zipPathName;
            }
        }
    }

    public static function make($basePath = null, $zipPath = null, $zipName = null)
    {
        if (is_null($basePath)) {
            return '需要压缩的文件路径不能为空';
        }
        if (!is_dir($basePath)) {
            return '需要压缩的文件路径不存在';
        }
        if (is_null($zipPath)) {
            return '保存压缩文件路径不能为空';
        }
        if (is_null($zipPath)) {
            $zipPath = time();
        }
        $zipName = "$zipName.zip";
        return new self($basePath, $zipPath, $zipName);
    }
    public function getPath()
    {
        return $this->zipPathName;
    }


    public function zip($zipName = null, $basePath = null, $zipPath = null)
    {
        if (is_null($basePath)) {
            return '需要压缩的文件路径不能为空';
        }
        if (is_dir($basePath)) {
            return '需要压缩的文件路径不存在';
        }
        if (is_null($zipPath)) {
            return '保存压缩文件路径不能为空';
        }
        if (is_null($zipPath)) {
            $zipPath = time();
        }
    }
}
