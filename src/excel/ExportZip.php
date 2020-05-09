<?php

namespace kuiba;

use ZipArchive;

class ExportZip
{

    /**
     * 压缩文件导出
     *
     * @param [type] $basePath 原路径
     * @param [type] $zipPath   压缩包存放路径
     * @param [type] $zipName   压缩包名称
     * @return void
     */
    public function zip($zipName = null, $basePath = null, $zipPath = null)
    {
        $result = null;
        if (is_null($basePath)) {
            return 1;
        }

        is_null($zipPath) && $zipPath = './static/zip/';
        !is_dir($zipPath) && @mkdir($zipPath, 0755, true);
        is_null($zipName) && $zipName = time() . '.zip';

        $basePath = iconv("UTF-8", "GBK", $basePath);
        $zipName = $zipName . '.zip';
        $zipPathName = $zipPath . $zipName;
        $zip = new ZipArchive();
        $fileArr = [];
        $fileNum = 0;
        if (is_dir($basePath)) {
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
                if ($res) {
                    header("Cache-Control: max-age=0");
                    header("Content-Description: File Transfer");
                    header("Content-Disposition: attachment;filename =" . $zipName . '.zip');
                    header('Content-Type: application/zip');
                    header('Content-Transfer-Encoding: binary');
                    header('Content-Length: ' . filesize($zipPathName));
                    @readfile($zipPathName); //输出文件;
                    //清理临时目录和文件
                    @unlink($zipPathName);
                    ob_flush();
                    flush();
                } else {
                    ob_flush();
                    flush();
                    Throwanexception('暂无文件可下载！');
                }
            }
        }
        return 2;
    }
}