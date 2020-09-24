## 使用 [返回](../README.md)
`use Kuiba\Qihengsan\ExcelExportV2;`
## 导出
```php
    $list   = [
        [
            "title"   => "你是什么垃圾？",
            "type"    => 1,
            "content" => "喵喵喵？？？",
            "img"     => "/static/uploads/20190928\77871c95d3f86e6f4f5b7fb3655355be.jpg",
            "create_time" => time(),
        ]
    ];  //数据
    $header = [
        ['标题', 'title', 'text'],
        ['内容', 'content', 'text'], // 规则不填默认text
        ['类型', 'type', 'selectd', [1 => '新闻', 2 => '刊物']],
        ['图片链接', 'img', 'function', function ($model) {
            return  'www.myadmin' . $model['msg_img'];
        }],
        ['创建时间', 'create_time', 'data', 'Y-m-d'],
    ];//表头
    $fileName = time();  //文件名 默认当前时间戳
    $suffix   = 'xlsx';  //后缀名 默认-xlsx-(xlsx/xls/html/csv)
    $limit   = 2000;  //后缀名 默认-xlsx-(xlsx/xls/html/csv)
    //普通导出并下载
    return ExcelExport::make($list, $header)->export()->download();
    //压缩导出并下载
    return  ExcelExport::make($list, $header)
                ->compressed('./static/excel/zip/', './static/excel/tmp/')
                ->export()->download();
    //可选属性
    setName();//设置文件名称
    setExt();//设置文件格式 
    //获取文件相对地址
    ExcelExport::make($list, $header)->export()->getFilePath();   
    //获取zip文件相对地址
    ExcelExport::make($list, $header)->compressed()->export()->getFilePath(); 
```
## 导入
```php
    $filePath   = './static/1.xlsx'; //文件路径
    $cellKey = [
        'A' => 'title',
        'B' => 'type',
        'C' => 'content',
        'D' => 'img',
        'E' => 'create_time',
    ]; //列对应字段
    $startRow = 2;   //开始读取的行数 默认 1
    $data = ExcelImport::make($filePath, $cellKey, $startRow)->delete(false)->import();
    var_dump($data);
```