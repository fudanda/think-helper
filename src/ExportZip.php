<?php

namespace Kuiba;

use ZipArchive;

class ExportZip
{

    public function zip($basePath = null, $zipName = null)
    {
        $basePath = '../public/static/useruploads/13-HD1575976950/';
        $basePath = '活动图片';
        $zip = new ZipArchive();
        $fileArr = [];
        $fileNum = 0;
        if (is_dir($basePath)) {
            if ($dh = opendir($basePath)) {
                $zip->open($zipName, ZipArchive::CREATE);
                while (($file = readdir($dh)) !== false) {
                    if (in_array($file, ['.', '..',])) continue; //无效文件，重来
                    $file = iconv('gbk', 'utf-8', $file);
                    $extension = strchr($file, '.');
                    rename(iconv('UTF-8', 'GBK', $basePath . '\\' . $file), iconv('UTF-8', 'GBK', $basePath . '\\' . $fileNum . $extension));
                    $zip->addFile($basePath . '\\' . $fileNum . $extension, $fileNum . $extension);
                    $zip->renameName($fileNum . $extension, $file);
                    $fileArr[$fileNum . $extension] = $file;
                    $fileNum++;
                }
                $zip->close();
                closedir($dh);
                foreach ($fileArr as $k => $v) {
                    rename(iconv('UTF-8', 'GBK', $basePath . '\\' . $k), iconv('UTF-8', 'GBK', $basePath . '\\' . $v));
                }
            }
        }
    }
}