<?php

namespace kuiba;

use Exception;
use kuiba\Snowflake;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;
use kuiba\Util;

/**
 * excel 导入-导出
 */
class Excel
{

    /**
     * 错误信息
     * @var string
     */
    private $error = '';

    /**
     * 配置参数
     * @var array
     */
    protected $config = [];

    /**
     * 允许的扩展名称
     * @var array
     */
    protected  $suffix = [
        'xlsx' => ['PhpOffice\PhpSpreadsheet\Writer\Xlsx', 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;'],
        'xls' => ['PhpOffice\PhpSpreadsheet\Writer\Xls', 'Content-Type:application/vnd.ms-excel;charset=utf-8;'],
        'csv' => ['PhpOffice\PhpSpreadsheet\Writer\Csv', 'Content-type:text/csv;charset=utf-8;'],
        'html' => ['PhpOffice\PhpSpreadsheet\Writer\Html', 'Content-Type:text/html;charset=utf-8;'],
    ];

    protected $contentType = '';

    /**
     * 传入表格数据
     * @var array
     */
    protected $list = [];

    /**
     * 传入表头数据
     * @var array
     */
    protected $header = [];

    /**
     * 配置存储文件的目录
     * @var string
     */
    protected $exceldir = './static/excel/';

    /**
     * 配置存储文件的临时目录
     * @var string
     */
    protected $tmpdir = './static/excel/tmp/';

    /**
     * 配置存储压缩文件的目录
     * @var string
     */
    protected $zipdir = './static/excel/zip/';

    /**
     * 文件名
     * @var string
     */
    protected $name;

    /**
     * 文件后缀
     * @var string
     */
    protected $ext = 'xlsx';


    /**
     * 下载后是否删除
     * 
     * @var string
     */
    protected $delete = true;

    /**
     * 是否压缩
     * @var string
     */
    protected $compressed = false;

    /**
     * 构造方法
     * @access public
     */
    public function __construct(array $list = [], array $header = [], $name = null, $ext = null)
    {
        $this->list    = $list;
        $this->header = $header;
        $this->name = $name ? $name : Snowflake::nextId(1);
        $this->ext =  $ext ?  $ext : $this->ext;
    }

    /**
     * 
     * @access public
     * @method bool isDelete(bool $delete = true)  是否删除生成的Excel文件
     * @method string setName(string $name)  设置文件名称
     * @method string setExt(string $ext)  设置文件拓展名
     * @method bool isDelete(bool $delete = true)  是否删除生成的Excel文件
     * @method void export()  导出
     * 
     * 
     * @param  array        $list    数据
     * @param  array        $header  表头
     * @param  string       $name   文件名
     * @param  string       $ext    扩展名
     * @return false|File   false-失败 否则返回文件路径或文件流
     */
    public static function make(array $list = [], array $header = [], string $name = null, string $ext = null)
    {
        return new self($list, $header, $name, $ext);
    }

    /**
     * 导出
     * 
     * @access public
     */
    public function export()
    {
        // 检测目录
        if (false === $this->check()) {
            return false;
        }
        /**
         * 生成文件
         */
        $filename = $this->processing();
        if (false === $filename) {
            $this->error = 'excel文件,创建失败';
            return false;
        }
        /**
         * 返回文件路径
         */
        return $filename;
    }

    /**
     * 导出直接下载
     */
    public function download()
    {
        // 检测目录
        if (false === $this->check()) {
            return false;
        }
        /**
         * 生成文件
         */
        $filename = $this->processing();
        if (false === $filename) {
            $this->error = 'excel文件,创建失败';
            return false;
        }
        /**
         * 下载
         */
        return Util::download($filename, $this->delete);
    }


    /**
     * 生成excel
     *
     * @return false|string false-失败 否则返回excel路径
     */
    public function processing()
    {
        try {
            $spreadsheet = $this->spreadsheet();

            $ext = $this->ext;
            $name = $this->name;
            $suffix = $this->suffix;
            $exceldir = $this->exceldir;

            $writer = new $suffix[$ext][0]($spreadsheet);
            $filename = "{$exceldir}{$name}.{$ext}";
            $res = $writer->save($filename);
            $this->release();
            return $filename;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }


    /**
     * 返回sheet
     * @access public
     * @return PhpOffice\PhpSpreadsheet\Spreadsheet;
     */
    public function spreadsheet()
    {
        // 清除之前的错误输出
        ob_end_clean();
        ob_start();

        $list = $this->list;
        $header = $this->header;

        $spreadsheet = new Spreadsheet();
        //设置文档信息
        $spreadsheet->getProperties()
            ->setCreator("srmkj") //作者
            ->setLastModifiedBy("srmkj") //最后修改者
            ->setTitle("") //标题
            ->setSubject("") //副标题
            ->setDescription("") //描述
            ->setKeywords("") //关键字
            ->setCategory(""); //分类
        $sheet = $spreadsheet->getActiveSheet();

        $hk = 1;
        $column = 2;

        $header = $this->header;
        foreach ($header as $k => $v) {
            //表格坐标 如 A1
            $coordinate = Coordinate::stringFromColumnIndex($hk) . '1';
            $sheet->setCellValue($coordinate, $v[0]);
            //设置样式
            $sheet->getStyle($coordinate)->getFont()->setBold(true);
            //设置列宽
            //$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($hk))->setAutoSize(true);
            //解决中文自动列宽无效问题
            $setWidth = strlen($v[0]) + 3;
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($hk))->setWidth($setWidth);
            //设置行高
            // $sheet->getDefaultRowDimension(1)->setRowHeight(15);
            $hk += 1;
        }

        foreach ($this->cursor($list) as $k => $item) {
            $span = 1;
            foreach ($this->cursor($header) as $key => $value) {
                // 解析字段
                $realData = self::formatting($header[$key], trim(self::formattingField($item, $value[1])), $item);
                // 写入excel
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($span) . $column, $realData);
                $span++;
            }
            $column++;
        }

        return $spreadsheet;
    }

    /**
     * 返回sheetHeader
     * @param PhpOffice\PhpSpreadsheet\Spreadsheet $sheet
     * @access public
     * @return PhpOffice\PhpSpreadsheet\Spreadsheet;
     */
    public function sheetHeader($sheet)
    {
        $hk = 1;
        $header = $this->header;
        foreach ($header as $k => $v) {
            //表格坐标 如 A1
            $coordinate = Coordinate::stringFromColumnIndex($hk) . '1';
            $sheet->setCellValue($coordinate, $v[0]);
            //设置样式
            $sheet->getStyle($coordinate)->getFont()->setBold(true);
            //设置列宽
            //$sheet->getColumnDimension(Coordinate::stringFromColumnIndex($hk))->setAutoSize(true);
            //解决中文自动列宽无效问题
            $setWidth = strlen($v[0]) + 3;
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($hk))->setWidth($setWidth);
            //设置行高
            // $sheet->getDefaultRowDimension(1)->setRowHeight(15);
            $hk += 1;
        }
        return $sheet;
    }
    /**
     * 返回sheetData
     * @param PhpOffice\PhpSpreadsheet\Spreadsheet $sheet
     * @access public
     * @return PhpOffice\PhpSpreadsheet\Spreadsheet;
     */
    public function sheetList($sheet)
    {
        $list = $this->list;
        $header = $this->header;
        $column = 2;
        foreach ($this->cursor($list) as $k => $item) {
            $span = 1;
            foreach ($this->cursor($header) as $key => $value) {
                // 解析字段
                $realData = self::formatting($header[$key], trim(self::formattingField($item, $value[1])), $item);
                // 写入excel
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($span) . $column, $realData);
                $span++;
            }
            $column++;
        }
        return $sheet;
    }

    /**
     * 设置文件的命名规则
     * @access public
     * @param  string   $rule    文件命名规则
     * @return $this
     */
    public function rule($rule)
    {
        $this->rule = $rule;

        return $this;
    }


    /**
     * 检测所有
     * @access public
     * @param  string   $ext  后缀
     * @return bool
     */
    public function check()
    {
        // 检测合法性
        if (empty($this->list)) {
            $this->error = '数据为空';
            return false;
        }
        // 检测合法性
        if (empty($this->header)) {
            $this->error = '表头为空';
            return false;
        }
        // 检测后缀名合法性
        if (false === $this->checkExt($this->ext)) {
            $this->error = '后缀名不合法';
            return false;
        }
        // 检测目录
        if (false === $this->checkPath(dirname($this->exceldir))) {
            $this->error = ['目录 {:path} 创建失败', ['path' => $this->exceldir]];
            return false;
        }
        return true;
    }

    /**
     * 检测文件后缀
     * @access public
     * @param  string   $ext  后缀
     * @return bool
     */
    public function checkExt($ext)
    {
        $suffix = $this->suffix;
        if (!array_key_exists($ext, $suffix)) {
            $this->error = 'extensions is not allowed';
            return false;
        }
        return true;
    }

    /**
     * 检查目录是否可写
     * @access protected
     * @param  string   $path    目录
     * @return boolean
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }

        if (mkdir($path, 0755, true)) {
            return true;
        }

        return false;
    }

    /**
     * 格式化内容
     *
     * @param array $array 头部规则
     * @return false|mixed|null|string 内容值
     */
    protected static function formatting(array $array, $value, $row)
    {
        !isset($array[2]) && $array[2] = 'text';
        switch ($array[2]) {
                // 文本
            case 'text':
                return $value;
                break;
                // 日期
            case 'date':
                return !empty($value) ? date($array[3], $value) : null;
                break;
                // 选择框
            case 'selectd':
                return $array[3][$value] ?? null;
                break;
                // 匿名函数
            case 'function':
                return isset($array[3]) ? call_user_func($array[3], $row) : null;
                break;
                // 默认
            default:
                break;
        }
        return null;
    }
    /**
     * 解析字段
     *
     * @param $row
     * @param $field
     * @return mixed
     */
    protected static function formattingField($row, $field)
    {
        $newField = explode('.', $field);
        if (count($newField) == 1) {
            return $row[$field];
        }
        foreach ($newField as $item) {
            if (isset($row[$item])) {
                $row = $row[$item];
            } else {
                break;
            }
        }
        return is_array($row) ? false : $row;
    }
    /**
     * 生成器
     */
    protected function cursor($list)
    {
        foreach ($list as $key => $value) {
            yield $value;
        }
    }
    /**
     * 释放内存
     */
    protected function release()
    {
        /* 释放内存 */
        $spreadsheet = $this->spreadsheet();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        ob_end_flush();
    }

    /**
     * Get 错误信息
     *
     * @return  string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get 文件名
     *
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set 文件名
     *
     * @param  string  $name  文件名
     *
     * @return  self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get 文件后缀
     *
     * @return  string
     */
    public function getExt()
    {
        return $this->ext;
    }

    /**
     * Set 文件后缀
     *
     * @param  string  $ext  文件后缀
     *
     * @return  self
     */
    public function setExt(string $ext)
    {
        $this->ext = $ext;

        return $this;
    }

    /**
     * 下载后是否删除
     *
     * @param  bool  $delete  下载后是否删除 默认是
     *
     * @return 
     */
    public function isDelete(bool $delete)
    {
        $this->delete = $delete;

        return $this;
    }
}
