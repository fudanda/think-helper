<?php

namespace Kuiba\Util;

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
    public function handle($output, $type, $filePath, $baseFilePath, $checkFile = true, $copyType = 'copy')
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
                'createCommonModel' => [
                    'Common Model is exist',
                    'Create Common Model error',
                    'Create Common Model success:' . $filePath,
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
    public function getClassName($name, $type)
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
        return $this->getNamespace($appNamespace, $module) . '\\' . $type . '\\' . $name;
    }
    public function getPathName($name)
    {
        $name = str_replace(App::getNamespace() . '\\', '', $name);
        return Env::get('app_path') . ltrim(str_replace('\\', '/', $name), '/') . '.php';
    }
    public function getNamespace($appNamespace, $module)
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

    /**
     * Twitter的Snowflake生成全局唯一ID的PHP实现
     *
     * 可实现分布式全局唯一ID的生成
     *
     * 因为PHP不是常驻内存运行，所以无法实现原版的队列编号，采用随机数代替
     * 可以最大程度避免并发请求时生成相同的ID
     * 理论上，当MAX_RANDOM_BIT为12，也就是随机数最大值为4095时
     * 每毫秒需生成的id小于1000即可以安全的生成不冲突的id
     *
     * @author Coeus <r.anerg@gmail.com>
     */
}