<?php

namespace fdd;

use think\facade\App;
use think\facade\Config;
use think\facade\Env;

class Util
{
    public function fileManager()
    {
        $filePath = file_build_path(env('app_path'), '..', 'public', 'static', 'images');
        $file_arr = array();
        if (is_dir($filePath)) {
            //打开
            if ($dh = @opendir($filePath)) {
                //读取
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $file_arr[] = $file;
                    }
                }
                //关闭
                closedir($dh);
            }
        }
        $this->initfileManager($file_arr);
        return $file_arr;
    }
    public function initfileManager($file_arr)
    {
        $filePath = file_build_path(env('app_path'), '..', 'public', 'static', 'admin', 'api', 'fileManager.json');
        $data['code'] = 0;
        $data['msg'] = '成功！';
        $data['count'] = count($file_arr);
        $data['data'] = $file_arr;
        $json_string = json_encode($data, JSON_UNESCAPED_UNICODE);
        // 写入文件
        file_put_contents($filePath, $json_string);
    }
    /**
     * 文件复制公共方法
     *
     * @param mixed  $output
     * @param string $type
     * @param string $filePath
     * @param string $baseFilePath
     * @param string $copyType
     * @return void
     */
    public static function handle($output, $type, $filePath, $baseFilePath, $checkFile = true, $copyType = 'copy')
    {
        try {
            $config = [
                'createConfig' => [
                    'Config is exist',
                    'Create Config error',
                    'Create Config success:' . $filePath,
                ],
                'createMigrate' => [
                    'database migrate is exist',
                    'Create database migrate error',
                    'Create database migrate success:' . $filePath,
                ],
                'createResources' => [
                    'Resources is exist',
                    'Create Resources error',
                    'Create Resources success:' . $filePath,
                ],
                'createCommon' => [
                    'Common Model is exist',
                    'Create Common Model error',
                    'Create Common Model success:' . $filePath,
                ],
                'createController' => [
                    'Controller is exist',
                    'Create Controller error',
                    'Create Controller success:' . $filePath,
                ],
                'createRoute' => [
                    'Router is exist',
                    'Create Router error',
                    'Create Router success:' . $filePath,
                ],
                'createWebpackmix' => [
                    'Webpackmix is exist',
                    'Create Webpackmix error',
                    'Create Webpackmix success:' . $filePath,
                ],
                'createBabelrc' => [
                    'Babelrc is exist',
                    'Create Babelrc error',
                    'Create Babelrc success:' . $filePath,
                ],

            ];
            //判断是否有该方法
            !array_key_exists($type, $config) && Throwanexception($type . '方法不存在');
            //判断文件是否已存在
            $exist = ($copyType == 'copy') ? is_file($filePath) : is_dir($filePath);
            $exist && Throwanexception($config[$type][0]);
            //复制文件
            $copy_res = true;
            $checkFile && $copy_res = $copyType($baseFilePath, $filePath);
            //判断是否复制成功
            !$copy_res && Throwanexception($config[$type][1]);
            //返回成功信息
            $output->writeln($config[$type][2]);
        } catch (\exception $e) {
            $output->writeln($e->getMessage());
        }
    }
    public static function getClassName($name, $type)
    {
        $appNamespace = App::getNamespace();
        if (strpos($name, $appNamespace . '\\') !== false) {
            return $name;
        }
        $module = null;
        if (Config::get('app_multi_module')) {
            $module = 'common';
            strpos($name, '/') && list($module, $name) = explode('/', $name, 2);
            $name = ucfirst($name);
        }
        strpos($name, '/') &&  $name = ucfirst(str_replace('/', '\\', $name));
        return self::getNamespace($appNamespace, $module) . '\\' . $type . '\\' . $name;
    }
    public static function getPathName($name)
    {
        $name = str_replace(App::getNamespace() . '\\', '', $name);
        return Env::get('app_path') . ltrim(str_replace('\\', '/', $name), '/') . '.php';
    }
    public static function getNamespace($appNamespace, $module)
    {
        return $module ? ($appNamespace . '\\' . $module) : $appNamespace;
    }
    public function getEnv($key, $default = null)
    {
        $value = getenv($key);
        if (is_null($default)) {
            return $value;
        }
        return false === $value ? $default : $value;
    }
    public function test()
    {
        return  str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . "/";
    }


    public static function  download($filename, $delete)
    {
        //获取文件的扩展名
        $allowDownExt = array('rar', 'zip', 'png', 'txt', 'mp4', 'html', 'xlsx', 'xls', 'csv');
        //获取文件信息
        $fileExt = pathinfo($filename);
        //检测文件类型是否允许下载
        if (!in_array($fileExt['extension'], $allowDownExt)) {
            return false;
        }
        //设置脚本的最大执行时间，设置为0则无时间限制
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        //通过header()发送头信息
        //因为不知道文件是什么类型的，告诉浏览器输出的是字节流
        header('content-type:application/octet-stream');
        //告诉浏览器返回的文件大小类型是字节
        header('Accept-Ranges:bytes');
        //获得文件大小
        $filesize = filesize($filename); //(此方法无法获取到远程文件大小)
        // $header_array = get_headers($filename, true);
        // $filesize = $header_array['Content-Length'];

        //告诉浏览器返回的文件大小
        header('Accept-Length:' . $filesize);
        //告诉浏览器文件作为附件处理并且设定最终下载完成的文件名称
        header('content-disposition:attachment;filename=' . basename($filename));
        //针对大文件，规定每次读取文件的字节数为4096字节，直接输出数据
        $read_buffer = 4096;
        $handle = fopen($filename, 'rb');
        //总的缓冲的字节数
        $sum_buffer = 0;
        //只要没到文件尾，就一直读取
        while (!feof($handle) && $sum_buffer < $filesize) {
            echo fread($handle, $read_buffer);
            $sum_buffer += $read_buffer;
        }

        //关闭句柄
        fclose($handle);
        $delete && unlink($filename);
        exit;
    }

    /**
     * 生成器
     */
    public static function cursor($list)
    {
        foreach ($list as $key => $value) {
            !is_null($value) && yield $value;
        }
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
                        self::deldir("$dirName/$item");
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
