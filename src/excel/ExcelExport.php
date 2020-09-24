<?php

namespace fdd\excel;

use Exception;
use fdd\Snowflake;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use fdd\Util;
use fdd\utils\Zip;


/**
 * excel 导入-导出
 */
class ExcelExport
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
        'xlsx' => [Xlsx::class, 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;'],
        'xls' => [Xls::class, 'Content-Type:application/vnd.ms-excel;charset=utf-8;'],
        'csv' => [Csv::class, 'Content-type:text/csv;charset=utf-8;'],
        'html' => [Html::class, 'Content-Type:text/html;charset=utf-8;'],
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
     * excel文件路径
     * @var string
     */
    protected $filePath = '';

    /**
     * excel文件路径
     * @var string
     */
    protected $limit = 0;

    public function __construct($list = [],  $header = [])
    {
        $this->list    = $list;
        $this->header = $header;
        $this->initialize();
    }
    /**
     * 初始化
     */
    protected function initialize()
    {
    }
    /**
     * 静态化
     * @param  array  $list   数据
     * @param  array  $header 表头
     */
    public static function make($list = [],  $header = [])
    {
        return new self($list, $header);
    }

    /**
     * 生成文件
     * @return $this
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
        $this->filePath = $filename;
        return $this;
    }


    /**
     * 下载
     *
     * @return mixed
     */
    public function download()
    {
        //判断文件
        if (!is_file($this->filePath)) {
            $this->error = "{$this->filePath}-文件不存在";
            return false;
        }
        //下载
        Util::download($this->filePath, $this->delete);
    }


    /**
     * 压缩
     * @param string $zipdir 压缩文件保存目录
     * @param string $tmpdir 缓存文件目录
     * @param string $limit  每个excel数据数量
     * @return $this
     */
    public function compressed($zipdir = '', $tmpdir = '', $limit = 2000)
    {
        $this->compressed = true;
        $this->zipdir = $zipdir;
        $this->tmpdir = $tmpdir;
        $this->limit = $limit;
        return $this;
    }


    /**
     * 生成excel
     *
     * @return false|string false-失败 否则返回excel路径
     */
    public function processing()
    {
        // try {
        $ext = $this->ext;
        $name = $this->name ?: Snowflake::nextId(1);
        $suffix = $this->suffix;
        $exceldir = $this->exceldir;
        $tmpdir = $this->tmpdir;
        $zipdir = $this->zipdir;

        if (!$this->compressed) {
            $spreadsheet = $this->spreadsheet();
            $writer = new $suffix[$ext][0]($spreadsheet);
            $filePath = "{$exceldir}{$name}.{$ext}";
            $res = $writer->save($filePath);
            $this->release();
            return $filePath;
        }

        $spreadsheet = $this->spreadsheetArr();
        $random_dir = time() . rand(1000, 9999);
        $tmpdir = $tmpdir . $random_dir . '/';
        foreach ($spreadsheet as $key => $item) {
            $writer = writer::make($this->ext, $item)->getWriter();
            $file_path = $tmpdir . $name . '_' . ($key + 1) . '.' . $this->ext;
            !is_dir($tmpdir) && mkdir($tmpdir, 0777, true);
            $writer->save($file_path);
        }

        $filePath = Zip::make($tmpdir, $zipdir, $name)->getPath();
        return  $filePath;
        // } catch (\Throwable $th) {
        //     $this->error = $th->getMessage();
        //     return false;
        // }
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

        foreach (Util::cursor($list) as $i => $item) {
            $span = 1;
            foreach (Util::cursor($header) as $key => $value) {
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
     * 返回sheet数组
     * @access public
     * @return array;
     */
    public function spreadsheetArr()
    {
        // 清除之前的错误输出
        ob_end_clean();
        ob_start();

        $list = $this->list;
        $header = $this->header;
        $limit = $this->limit;

        $chunk_data = array_chunk($list, $limit);
        for ($i = 0; $i < count($chunk_data); $i++) {
            $column = 2;
            $new_spreadsheet = 'spreadsheet' . $i;
            $new_spreadsheet = new Spreadsheet();
            //设置文档信息
            $new_spreadsheet->getProperties()
                ->setCreator("srmkj") //作者
                ->setLastModifiedBy("srmkj") //最后修改者
                ->setTitle("") //标题
                ->setSubject("") //副标题
                ->setDescription("") //描述
                ->setKeywords("") //关键字
                ->setCategory(""); //分类
            $sheet = $new_spreadsheet->getActiveSheet();
            // 写入头部
            $hk = 1;
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
            $buffer = $chunk_data[$i];

            foreach ($buffer as $i => $item) {
                $span = 1;
                foreach (Util::cursor($header) as $key => $value) {
                    // 解析字段
                    $realData = self::formatting($header[$key], trim(self::formattingField($item, $value[1])), $item);
                    // 写入excel
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($span) . $column, $realData);
                    $span++;
                }
                $column++;
                unset($buffer[$k]);
            }
            $spreadsheet_arr[$i] = $new_spreadsheet;
            unset($new_spreadsheet);
        }
        return $spreadsheet_arr;
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
        foreach (Util::cursor($list) as $k => $item) {
            $span = 1;
            foreach (Util::cursor($header) as $key => $value) {
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
     * Get 文件后缀
     *
     * @return  string
     */
    public function getExt()
    {
        return $this->ext;
    }

    /**
     * Get 文件路径
     *
     * @return  string
     */
    public function getFilePath()
    {
        return $this->filePath;
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
        $this->name = $name ?: Snowflake::nextId(1);

        return $this;
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
        $this->ext = $ext ?: $this->ext;

        return $this;
    }



    /**
     * 下载后是否删除
     *
     * @param  bool  $delete  下载后是否删除 默认是
     *
     * @return 
     */
    public function delete(bool $delete)
    {
        $this->delete = $delete;

        return $this;
    }
}
