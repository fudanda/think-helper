<?php

namespace fdd\excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use fdd\utils\Zip;

/**
 * EXCEL写入
 */
class Writer
{
    /**
     * 表格写入对象
     * @var object
     */
    private $writer = '';

    /**
     * 内容类型模板
     *
     * @var string
     */

    private $type = '';

    private $contentTypeArr = [
        'xlsx' => 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;',
        'xls'  => 'Content-Type:application/vnd.ms-excel;charset=utf-8;',
        'csv'  => 'Content-type:text/csv;charset=utf-8;',
        'html' => 'Content-Type:text/html;charset=utf-8;'
    ];
    private $contentType = '';


    /**
     * 是否压缩
     * @var string
     */
    protected $iszip;

    /**
     * 静态化
     *
     * @param string $type 文件类型
     * @param $spreadsheet 表格
     * @return _self
     */
    public function __construct($type = '', $spreadsheet)
    {
        $type = strtolower($type);
        switch ($type) {
            case 'xlsx':
                $this->writer = new Xlsx($spreadsheet);
                $this->contentType = $this->contentTypeArr[$type];
                break;
            case 'xls':
                $this->writer = new Xls($spreadsheet);
                $this->contentType = $this->contentTypeArr[$type];
                break;
            case 'csv':
                $this->writer = new Csv($spreadsheet);
                $this->contentType = $this->contentTypeArr[$type];
                break;
            case 'html':
                $this->writer = new Html($spreadsheet);
                $this->contentType = $this->contentTypeArr[$type];
                break;
            default:
                throw new \Exception("$type is not supported", 400);
                break;
        }
        $this->type = $type;
    }

    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * 静态化
     *
     * @param string $type 文件类型
     * @param $spreadsheet 表格
     * @return _self
     */
    public static function make($type = '', $spreadsheet)
    {
        return new self($type, $spreadsheet);
    }

    public function zip($iszip = false)
    {
        $this->iszip = $iszip;
        return $this;
    }

    /**
     * 下载
     *
     * @param string $name 文件名称
     * @return void
     */
    public function play($name = null)
    {
        if (is_null($name)) {
            return '文件名称不能为空';
        }
        if (!$this->iszip) {
            header($this->contentType);
            header("Content-Disposition: inline;filename=\"{$name}\"");
            header('Cache-Control: max-age=0');
            $this->writer->save('php://output');
            exit();
        } else {
            //获取压缩文件路径
            $filePath = Zip::make($this->tempdir, $this->zipdir, $name);

            if (is_file($filePath)) {
                header("Cache-Control: max-age=0");
                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment;filename =" . $name . '.zip');
                header('Content-Type: application/zip');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($filePath));
                @readfile($filePath); //输出文件;
                //清理临时目录和文件
                @unlink($filePath);
                ob_flush();
                flush();
            } else {
                ob_flush();
                flush();
                Throwanexception('暂无文件可下载！');
            }
        }
    }
}
