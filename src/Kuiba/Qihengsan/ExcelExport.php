<?php

namespace Kuiba\Qihengsan;

use \ZipArchive;

// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExport
{
    //字段对应的标题
    private $title = [];
    //文件名
    private $filename = '';
    //字段值过滤器
    private $filter = [];

    //存储文件的临时目录
    private $stodir = '../download/tmp/';
    private $destTmp = '../download/tmp/';
    private $zipFile = '';

    /**
     * 测试方法 返回数据
     *
     * @return void
     */
    public static function test()
    {
        return 'success';
    }

    /**
     * 指定临时存储路径
     * 请确定这个路径有读写权限
     */
    public function tmpdir(string $dir)
    {
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        $this->stodir = $dir;
        return $this;
    }

    /**
     * 生成 excel 数据表文件
     * @param  array  $data 要导出的数据
     * @return bool
     */
    public function excel($data = array(), $name = 1, $head, $keys = [])
    {

        set_time_limit(0);
        header("Content-type: text/html; charset=utf-8");

        $count = count($head); //计算表头数量

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        for ($i = 65; $i < $count + 65; $i++) { //数字转字母从65开始，循环设置表头：
            $sheet->setCellValue(strtoupper(chr($i)) . '1', $head[$i - 65]);
        }

        /*--------------开始从数据库提取信息插入Excel表中------------------*/

        foreach ($data as $key => $item) {

            for ($i = 65; $i < $count + 65; $i++) { //数字转字母从65开始：
                $sheet->setCellValue(strtoupper(chr($i)) . ($key + 2), $item[$keys[$i - 65]]);
                $spreadsheet->getActiveSheet()->getColumnDimension(strtoupper(chr($i)))->setWidth(20); //自动列宽
            }
        }

        $filename = $this->filename ? $this->filename : date('Y_m_d');
        $filePath = $this->stodir . $filename . "($name)" . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        //删除清空：
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * 打包好zip文件并导出
     * @param  [type] $filename [description]
     * @return [type]           [description]
     */
    public function fileload()
    {
        //去除地址后面的/
        $newPath = substr($this->stodir, 0, -1);

        $zipname = $newPath . '.zip';

        $zipObj = new ZipArchive();
        if ($zipObj->open($zipname, ZipArchive::CREATE) === true) {
            $res = false;
            foreach (glob($this->stodir . "*") as $file) {
                $res = $zipObj->addFile($file);
            }
            $zipObj->close();
            if ($res) {
                header("Cache-Control: max-age=0");
                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment;filename =" . $zipname);
                header('Content-Type: application/zip');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($zipname));

                @readfile($zipname); //输出文件;
                //清理临时目录和文件
                $this->deldir($this->stodir);
                @unlink($zipname);
                ob_flush();
                flush();
            } else {
                $this->deldir($this->stodir);
                ob_flush();
                flush();
                die('暂无文件可下载！');
            }
        } else {
            $this->deldir($this->stodir);
            ob_flush();
            flush();
            die('文件压缩失败！');
        }
        exit();
    }

    /**
     * 清理目录，删除指定目录下所有内容及自身文件夹
     * @param  [type] $dir [description]
     * @return [type]       [description]
     */
    public function deldir($dir = null)
    {
        $dir = is_null($dir) ? $this->destTmp : $dir;
        if (is_dir($dir)) {
            foreach (glob($dir . '*') as $file) {
                if (is_dir($file)) {
                    $this->deldir($file);
                    @rmdir($file);
                } else {
                    @unlink($file);
                }
            }
            @rmdir($dir);
        }
    }

    /**
     * 设置标题
     * @param array $title 标题参数为字段名对应标题名称的键值对数组
     * @return obj this
     */
    public function title($title)
    {
        if ($title && is_array($title)) {
            $this->title = $title;
        }
        return $this;
    }

    /**
     * 设置导出的文件名
     * @param string $filename 文件名
     * @return obj this
     */
    public function filename($filename)
    {
        $this->filename = date('Y_m_d') . (string) $filename;
        if (!is_dir($this->stodir . $this->filename)) {
            mkdir($this->stodir . $this->filename, 0777, true);
        }
        $this->stodir .= $this->filename . '/';
        return $this;
    }

    /**
     * 设置字段过滤器
     * @param array $filter 文件名
     * @return obj this
     */
    public function filter($filter)
    {
        $this->filter = (array) $filter;
        return $this;
    }

    /**
     * 确保标题字段名和数据字段名一致,并且排序也一致
     * @param  array $keys  要显示的字段名数组
     * @return array 包含所有要显示的字段名的标题数组
     */
    protected function titleColumn(array $keys)
    {
        $title = $this->title;
        if ($title && is_array($title)) {
            $titleData = [];
            foreach ($keys as $v) {
                if (isset($title[$v])) {
                    $titleData[$v] = $title[$v];
                    unset($title[$v]);
                }
            }
            unset($keys);
            return $titleData;
        }
        return $keys;
    }

    public function outdata($name = '测试表', $data = [], $head = [], $keys = [])
    {
        $count = count($head); //计算表头数量

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        for ($i = 65; $i < $count + 65; $i++) { //数字转字母从65开始，循环设置表头：
            $sheet->setCellValue(strtoupper(chr($i)) . '1', $head[$i - 65]);
        }

        /*--------------开始从数据库提取信息插入Excel表中------------------*/

        foreach ($data as $key => $item) {

            for ($i = 65; $i < $count + 65; $i++) { //数字转字母从65开始：
                $sheet->setCellValue(strtoupper(chr($i)) . ($key + 2), $item[$keys[$i - 65]]);
                $spreadsheet->getActiveSheet()->getColumnDimension(strtoupper(chr($i)))->setWidth(20); //自动列宽
            }
        }

        return $this->exportExcel($spreadsheet, 'xlsx', $name);
    }
    public function exportExcel($spreadsheet, $format = 'xls', $savename = 'export')
    {
        // if (!$spreadsheet) return false;
        // if ($format == 'xls') {
        //   //输出Excel03版本
        //   header('Content-Type:application/vnd.ms-excel');
        //   $class = "\PhpOffice\PhpSpreadsheet\Writer\Xls";
        // } elseif ($format == 'xlsx') {
        //   //输出07Excel版本
        //   header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //   $class = "\PhpOffice\PhpSpreadsheet\Writer\Xlsx";
        // }
        // $filename = $this->filename ? $this->filename : date('Y_m_d');
        // $filePath = $this->stodir . $filename . $savename . '.xlsx';
        // //输出名称
        // header('Content-Disposition: attachment;filename="' . $filename  . $savename . '.' . $format . '"');
        // //禁止缓存
        // header('Cache-Control: max-age=0');
        // $writer = new $class($spreadsheet);

        // $writer->save($filePath);

        // readfile($filePath);
        // unlink($filePath);
        $name = $savename . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($name);
        header('Location:' . 'http://' . $_SERVER['HTTP_HOST'] . '/lxtx/admin/' . $name);
    }

    public static function exportTheExcel($expTitle, $expCellName, $expTableData)
    {
        set_time_limit(0);
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
        $fileName = $expTitle . date('_YmdHis'); //or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //合并单元格
        $objPHPExcel->getActiveSheet()->setTitle($expTitle);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '导出时间:' . date('Y-m-d H:i:s'));
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setWidth(22);
        }

        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {

                if (empty($expCellName[$j][2])) {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
                } else if ($expCellName[$j][2] == 'format_time') {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), date('Y-m-d H:i:s', $expTableData[$i][$expCellName[$j][0]]));
                } else if ($expCellName[$j][2] == 'string') {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), "'" . $expTableData[$i][$expCellName[$j][0]] . "'");
                }
            }
        }
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$fileName.xls");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public static function  exportTheExcelZip($expTitle, $expCellName, $expTableData, $i = 1)
    {
        set_time_limit(0);
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
        $fileName = $expTitle . date('_YmdHis') . $i; //or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        $objPHPExcel = new \PHPExcel();
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //合并单元格
        $objPHPExcel->getActiveSheet()->setTitle($expTitle);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '导出时间:' . date('Y-m-d H:i:s'));
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setWidth(22);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                if (empty($expCellName[$j][2])) {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
                } else if ($expCellName[$j][2] == 'format_time') {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), date('Y-m-d H:i:s', $expTableData[$i][$expCellName[$j][0]]));
                } else if ($expCellName[$j][2] == 'string') {
                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), "'" . $expTableData[$i][$expCellName[$j][0]] . "'");
                }
            }
        }

        /** 保存Excel 2007格式文件，保存路径为当前路径 */
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        if (!is_dir($this->destTmp)) {
            mkdir($this->destTmp, 0777, true);
        }
        $zipFile = $this->destTmp . $fileName . '.xlsx';
        $objWriter->save($zipFile);
    }

    public function excelZip()
    {
        set_time_limit(0);
        /** 生成压缩文件 */
        $archive = new \PclZip('../download/archive.zip');
        $v_list = $archive->create(
            $this->destTmp, // 路径的名字
            PCLZIP_OPT_REMOVE_PATH,
            $this->destTmp,
            PCLZIP_OPT_ADD_PATH,
            ''
        );
        $this->deldirs();
        $name = 'archive.zip';
        header('Location:' . 'http://' . $_SERVER['HTTP_HOST'] . '/download/' . $name);
    }

    public function deldirs()
    {
        set_time_limit(0);
        $dir = $this->destTmp;
        //删除目录下的文件：
        $dh = opendir($dir);

        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;

                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->deldirs($fullpath);
                    rmdir($fullpath); //直接删除
                }
            }
        }
        closedir($dh);
    }
}