<?php

namespace Kuiba\Qihengsan;

use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use ZipArchive;

/**
 * excel 导入-导出
 */
class ExcelExportV2
{
    //存储文件的临时目录
    public static $tmpdir = './static/excel/tmp/';
    public static $zipdir = './static/excel/zip/';


    /**
     * 导出Excel
     *
     * @param array $list  导出数据
     * @param array $header 表头
     * @param string $filename 文件名默认为当前时间戳
     * @param string $suffix   文件后缀默认xlsx (xlsx,xls,csv,html)
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function export($list = [], $header = [], $filename = '', $suffix = 'xlsx')
    {
        try {
            !$filename && $filename = time();
            $spreadsheet = self::processing($list, $header, $filename, $suffix);

            $suffix_arr = [
                'xlsx' => ['PhpOffice\PhpSpreadsheet\Writer\Xlsx', 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;'],
                'xls'  => ['PhpOffice\PhpSpreadsheet\Writer\Xls', 'Content-Type:application/vnd.ms-excel;charset=utf-8;'],
                'csv'  => ['PhpOffice\PhpSpreadsheet\Writer\Csv', 'Content-type:text/csv;charset=utf-8;'],
                'html' => ['PhpOffice\PhpSpreadsheet\Writer\Html', 'Content-Type:text/html;charset=utf-8;']
            ];
            !array_key_exists($suffix, $suffix_arr) && self::Throwanexception('后缀名格式不存在!');

            $writer = new $suffix_arr[$suffix][0]($spreadsheet);
            // 直接输出下载
            header($suffix_arr[$suffix][1]);
            header("Content-Disposition: inline;filename=\"{$filename}.{$suffix}\"");
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit();
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 数据处理
     *
     * @param array $list
     * @param array $header
     * @param string $filename
     * @param string $suffix
     * @param string $type 操作类型【1直接下载】【2压缩】 默认1
     * @param string $limit 压缩每个excel的数据条数 默认2000
     * @return void
     */
    public static function processing($list, $header, $filename, $suffix, $type = 1, $limit = 2000)
    {
        !is_array($list) && self::Throwanexception('数据不能为空！');
        !is_array($header) && self::Throwanexception('表头不能为空！');
        // 清除之前的错误输出
        ob_end_clean();
        ob_start();
        // 开始写入内容
        if ($type == 1) {

            $spreadsheet = new Spreadsheet();
            //设置文档信息
            $spreadsheet->getProperties()
                ->setCreator("srmkj")    //作者
                ->setLastModifiedBy("srmkj") //最后修改者
                ->setTitle("")  //标题
                ->setSubject("") //副标题
                ->setDescription("")  //描述
                ->setKeywords("") //关键字
                ->setCategory(""); //分类
            $sheet = $spreadsheet->getActiveSheet();
            // 写入头部
            $hk = 1;
            foreach ($header as $k => $v) {
                //表格坐标 如 A1
                $coordinate = Coordinate::stringFromColumnIndex($hk) . '1';
                $sheet->setCellValue($coordinate, $v[0]);
                //设置样式
                $sheet->getStyle($coordinate)->getFont()->setBold(true);
                //设置列宽
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($hk))->setAutoSize(true);
                //setWidth(30)
                //设置行高
                // $sheet->getDefaultRowDimension(1)->setRowHeight(15);
                $hk += 1;
            }

            $column = 2;
            $size = ceil(count($list) / 500);
            for ($i = 0; $i < $size; $i++) {
                $buffer = array_slice($list, $i * 500, 500);
                foreach ($buffer as $k => $row) {
                    $span = 1;
                    foreach ($header as $key => $value) {
                        // 解析字段
                        $realData = self::formatting($header[$key], trim(self::formattingField($row, $value[1])), $row);
                        // 写入excel
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($span) . $column, $realData);
                        $span++;
                    }
                    $column++;
                    unset($buffer[$k]);
                }
            }
            return $spreadsheet;
        } {
            $spreadsheet_arr = [];
            $chunk_data = array_chunk($list, $limit);
            for ($i = 0; $i < count($chunk_data); $i++) {
                $column = 2;
                $new_spreadsheet = 'spreadsheet' . $i;
                $new_spreadsheet = new Spreadsheet();
                //设置文档信息
                $new_spreadsheet->getProperties()
                    ->setCreator("srmkj")    //作者
                    ->setLastModifiedBy("srmkj") //最后修改者
                    ->setTitle("")  //标题
                    ->setSubject("") //副标题
                    ->setDescription("")  //描述
                    ->setKeywords("") //关键字
                    ->setCategory(""); //分类
                $sheet =  $new_spreadsheet->getActiveSheet();
                // 写入头部
                $hk = 1;
                foreach ($header as $k => $v) {
                    //表格坐标 如 A1
                    $coordinate = Coordinate::stringFromColumnIndex($hk) . '1';
                    $sheet->setCellValue($coordinate, $v[0]);
                    //设置样式
                    $sheet->getStyle($coordinate)->getFont()->setBold(true);
                    //设置列宽
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($hk))->setAutoSize(true);
                    //setWidth(30)
                    //设置行高
                    // $sheet->getDefaultRowDimension(1)->setRowHeight(15);
                    $hk += 1;
                }
                $buffer = $chunk_data[$i];
                foreach ($buffer as $k => $row) {
                    $span = 1;
                    foreach ($header as $key => $value) {
                        // 解析字段
                        $realData = self::formatting($header[$key], trim(self::formattingField($row, $value[1])), $row);
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
    }

    /**
     * 导出Excel 压缩包
     *
     * @param array $list  导出数据
     * @param array $header 表头
     * @param string $filename 文件名默认为当前时间戳
     * @param string $suffix   文件后缀默认xlsx (xlsx,xls,csv,html)
     * @param string $limit     每个文件数据默认 2000
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportZip($list = [], $header = [], $filename = '', $suffix = 'xlsx', $limit = 2000)
    {

        try {
            !$filename && $filename = time();
            $spreadsheet = self::processing($list, $header, $filename, $suffix, 2, $limit);
            $suffix_arr = [
                'xlsx' => ['PhpOffice\PhpSpreadsheet\Writer\Xlsx', 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8;'],
                'xls'  => ['PhpOffice\PhpSpreadsheet\Writer\Xls', 'Content-Type:application/vnd.ms-excel;charset=utf-8;'],
                'csv'  => ['PhpOffice\PhpSpreadsheet\Writer\Csv', 'Content-type:text/csv;charset=utf-8;'],
                'html' => ['PhpOffice\PhpSpreadsheet\Writer\Html', 'Content-Type:text/html;charset=utf-8;']
            ];
            !array_key_exists($suffix, $suffix_arr) && self::Throwanexception('后缀名格式不存在!');
            $random_dir = time() . rand(1000, 9999);
            $tmpdir = self::$tmpdir . $random_dir . '/';
            foreach ($spreadsheet as $key => $item) {
                $writer = new $suffix_arr[$suffix][0]($item);
                $file_path = $tmpdir . $filename . '_' . ($key + 1) . '.' . $suffix;
                !is_dir($tmpdir) && mkdir($tmpdir, 0777, true);
                $writer->save($file_path);
            }
            $zip_path = self::$zipdir . '/' . $filename . '.zip';
            $zip_dir = self::$zipdir;
            !is_dir($zip_dir) && mkdir($zip_dir, 0777, true);
            $zipObj = new ZipArchive();
            if ($zipObj->open($zip_path, ZipArchive::CREATE) === true) {
                $res = false;
                foreach (glob($tmpdir . "*") as $file) {
                    $zip_filename = explode('/', $file);
                    $zip_filename = end($zip_filename);
                    $res = $zipObj->addFile($file, $zip_filename);
                }
                $zipObj->close();
                if ($res) {
                    header("Cache-Control: max-age=0");
                    header("Content-Description: File Transfer");
                    header("Content-Disposition: attachment;filename =" . $filename . '.zip');
                    header('Content-Type: application/zip');
                    header('Content-Transfer-Encoding: binary');
                    header('Content-Length: ' . filesize($zip_path));
                    @readfile($zip_path); //输出文件;
                    //清理临时目录和文件
                    self::deldir($tmpdir);
                    @unlink($zip_path);
                    ob_flush();
                    flush();
                } else {
                    self::deldir($tmpdir);
                    ob_flush();
                    flush();
                    Throwanexception('暂无文件可下载！');
                }
            } else {
                self::deldir($tmpdir);
                ob_flush();
                flush();
                Throwanexception('文件压缩失败！');
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /**
     * 导入
     *
     * @param $filePath 文件路径
     * @param int $startRow 开始行数默认1
     * @return array|mixed
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function import($filePath, $startRow = 1)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        if (!$reader->canRead($filePath)) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            // setReadDataOnly Set read data only 只读单元格的数据，不格式化 e.g. 读时间会变成一个数据等
            $reader->setReadDataOnly(true);
            if (!$reader->canRead($filePath)) {
                throw new Exception('不能读取Excel');
            }
        }
        $spreadsheet = $reader->load($filePath);
        $sheetCount = $spreadsheet->getSheetCount(); // 获取sheet的数量
        // 获取所有的sheet表格数据
        $excleDatas = [];
        $emptyRowNum = 0;
        for ($i = 0; $i < $sheetCount; $i++) {
            $currentSheet = $spreadsheet->getSheet($i); // 读取excel文件中的第一个工作表
            $allColumn = $currentSheet->getHighestColumn(); // 取得最大的列号
            $allColumn = Coordinate::columnIndexFromString($allColumn); // 由列名转为列数('AB'->28)
            $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行
            $arr = [];
            for ($currentRow = $startRow; $currentRow <= $allRow; $currentRow++) {
                // 从第1列开始输出
                for ($currentColumn = 1; $currentColumn <= $allColumn; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $arr[$currentRow][] = trim($val);
                }
                // $arr[$currentRow] = array_filter($arr[$currentRow]);
                // 统计连续空行
                if (empty($arr[$currentRow]) && $emptyRowNum <= 50) {
                    $emptyRowNum++;
                } else {
                    $emptyRowNum = 0;
                }
                // 防止坑队友的同事在excel里面弄出很多的空行，陷入很漫长的循环中，设置如果连续超过50个空行就退出循环，返回结果
                // 连续50行数据为空，不再读取后面行的数据，防止读满内存
                if ($emptyRowNum > 50) {
                    break;
                }
            }
            $excleDatas[$i] = $arr; // 多个sheet的数组的集合
        }
        // 这里我只需要用到第一个sheet的数据，所以只返回了第一个sheet的数据
        $returnData = $excleDatas ? array_shift($excleDatas) : [];
        // 第一行数据就是空的，为了保留其原始数据，第一行数据就不做array_fiter操作；
        $returnData = $returnData && isset($returnData[$startRow]) && !empty($returnData[$startRow])  ? array_filter($returnData) : $returnData;
        return $returnData;
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
            case  'date':
                return !empty($value) ? date($array[3], $value) : null;
                break;
                // 选择框
            case  'selectd':
                return  $array[3][$value] ?? null;
                break;
                // 匿名函数
            case  'function':
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
     * 抛出异常处理
     *
     * @param string    $msg  异常消息
     * @param integer   $code 异常代码 默认为0
     * @param string    $exception 异常类
     *
     * @throws Exception
     */
    public static function Throwanexception($msg, $code = 0, $exception = '')
    {
        throw new Exception($msg, $code);
    }
    /**
     * 删除目录下的文件
     *
     * @return void
     */
    public static function deldir($dirName = null)
    {
        if ($handle = opendir($dirName)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dirName/$item")) {
                        $this->deldir("$dirName/$item");
                    } else {
                        unlink("$dirName/$item");
                    }
                }
            }
            closedir($handle);
            rmdir($dirName);
        }
    }
}