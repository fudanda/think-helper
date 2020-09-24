<?php

namespace fdd\excel;

use fdd\Snowflake;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * excel 导入
 */
class ExcelImport
{

    /**
     * 下载后是否删除
     */
    protected $delete = true;

    //文件路径
    protected $filePath = '';

    //列对应字段名称
    protected $cellKey = [];

    //开始读取行数
    protected $startRow = 2;

    public function __construct($filePath, $cellKey, $startRow)
    {
        $this->filePath    = $filePath;
        $this->cellKey = $cellKey;
        $this->startRow = $startRow;
        $this->initialize();
    }
    /**
     * 初始化
     */
    protected function initialize()
    {
    }

    /**
     * Undocumented function
     *
     * @param [type] $filePath 文件路径
     * @param [type] $cellKey 列对应字段名称
     * @param integer $startRow 开始读取行数 默认2
     */
    public static function make($filePath, $cellKey, $startRow = 2)
    {
        return new self($filePath, $cellKey, $startRow);
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

    /**
     * 返回处理后的数组
     * @return  array
     */
    public function import()
    {

        $filePath = $this->filePath;
        $cellKey = $this->cellKey;
        $startRow = $this->startRow;

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        // $reader->setReadDataOnly(true);
        if (!$reader->canRead($filePath)) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            // setReadDataOnly Set read data only 只读单元格的数据，不格式化 e.g. 读时间会变成一个数据等
            // $reader->setReadDataOnly(true);
            if (!$reader->canRead($filePath)) {
                throw new \Exception('不能读取Excel');
            }
        }
        $spreadsheet = $reader->load($filePath);
        $sheetCount = $spreadsheet->getSheetCount(); // 获取sheet的数量
        // 获取所有的sheet表格数据
        $excleDatas = [];
        $img_arr = [];

        //读取图片
        foreach ($spreadsheet->getActiveSheet()->getDrawingCollection() as $key => $drawing) {
            if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                $zipReader = fopen($drawing->getPath(), 'r');
                $imageContents = '';
                while (!feof($zipReader)) {
                    $imageContents .= fread($zipReader, 1024);
                }
                fclose($zipReader);
                $exception_arr = [
                    'jpeg' => 'jpg',
                    'gif' => 'gif',
                    'png' => 'png',
                ];
                $extension = $exception_arr[strtolower($drawing->getExtension())];
                $filename = Snowflake::nextId(1) . '.' . $extension;
                $filepath = '/static/uploads/' . date('Ymd', time()) . '/';
                $file = $filepath . $filename;
                file_put_contents('./' . $file, $imageContents);
                $coor = $drawing->getCoordinates();
                $img_arr[$coor] = $file;
            }
        }
        $currentSheet = $spreadsheet->getActiveSheet(); // 读取excel文件中的第一个工作表
        $allColumn = $currentSheet->getHighestColumn(); // 取得最大的列号
        $allColumn = Coordinate::columnIndexFromString($allColumn); // 由列名转为列数('AB'->28)
        $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行

        $arr = [];
        for ($currentRow = $startRow; $currentRow <= $allRow; $currentRow++) {
            //从第1列开始输出
            for ($currentColumn = 1; $currentColumn <= $allColumn; $currentColumn++) {
                $key = Coordinate::stringFromColumnIndex($currentColumn);
                $coor = $key . $currentRow;
                if (array_key_exists($key, $cellKey)) {

                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $arr[$currentRow][$cellKey[$key]] = trim($val);
                    array_key_exists($coor, $img_arr) && $arr[$currentRow][$cellKey[$key]] = $img_arr[$coor];
                } else {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $arr[$currentRow][] = trim($val);
                }
            }
            if (empty($arr[$currentRow])) {
                break;
            }
        }

        $this->delete && unlink($filePath);

        return $arr;
    }
}
