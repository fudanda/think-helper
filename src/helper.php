<?php

// "Kuiba\\util\\": "util",
// "Kuiba\\Baguda\\": "util/Kuiba/Baguda",
// "Kuiba\\Carlaxiaokeling\\": "util/Kuiba/Carlaxiaokeling",
// "Kuiba\\Dacang\\": "util/Kuiba/Dacang",
// "Kuiba\\Haiwenxiang\\": "util/Kuiba/Haiwenxiang",
// "Kuiba\\Leiguang\\": "util/Kuiba/Leiguang",
// "Kuiba\\Mellonmonicajia\\": "util/Kuiba/Mellonmonicajia",
// "Kuiba\\Qihengsan\\": "util/Kuiba/Qihengsan",
// "Kuiba\\Qiuluomu\\": "util/Kuiba/Qiuluomu",
// "Kuiba\\Rangu\\": "util/Kuiba/Rangu",
// "Kuiba\\Wanliang\\": "util/Kuiba/Wanliang",
// "Kuiba\\Youmingkuang\\": "util/Kuiba/Youmingkuang",
// "Kuiba\\Youruoli\\": "util/Kuiba/Youruoli"

if (!function_exists('scan_dir')) {
    /**
     * 扫描目录.
     *
     * @param  string 目录
     * @param  int 层级
     * @param  int 当前层级
     *
     * @return array
     */
    function scan_dir($dir, $depth = 0, $now = 0)
    {
        $dirs = [];
        if (!is_dir($dir) || ($now >= $depth && $depth != 0)) {
            return false;
        }
        // file_build_path($dir, '*');
        $dirArr = glob(file_build_path($dir, '*'));
        $now++;
        foreach ($dirArr as $item) {
            if (is_dir($item)) {
                $dirs[] = $item;
                $subDir = scan_dir($item, $depth, $now);
                if ($subDir) {
                    $dirs = array_merge($dirs, $subDir);
                }
            }
        }
        return $dirs;
    }
}

if (!function_exists('dirToArray')) {
    function dirToArray($dir)
    {
        $result = array();
        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }
}
if (!function_exists('copy_dir')) {
    /**
     * 复制目录.
     *
     * @param  string  $dir   目录
     * @param  string  $dest  目标目录
     *
     * @return bool
     */
    function copy_dir($dir, $dest = '')
    {
        if (!is_dir($dir)) {
            return false;
        }
        @mkdir($dest, 0777, true);
        $resources = scandir($dir);
        foreach ($resources as $item) {
            if (
                is_dir($dir . DIRECTORY_SEPARATOR . $item) && $item != '.'
                && $item != '..'
            ) {
                copy_dir(
                    $dir . DIRECTORY_SEPARATOR . $item,
                    $dest . DIRECTORY_SEPARATOR . $item
                );
            } elseif (is_file($dir . DIRECTORY_SEPARATOR . $item)) {
                copy(
                    $dir . DIRECTORY_SEPARATOR . $item,
                    $dest . DIRECTORY_SEPARATOR . $item
                );
            }
        }
        return true;
    }
}
if (!function_exists('view_path')) {
    /**
     * 获取模板具体目录.
     *
     * @return string
     */
    function view_path()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR;
    }
}
if (!function_exists('encrypt_password')) {
    /**
     * 密码加密.
     *
     * @param  string  $password  原密码
     * @param  string  $salt      盐值
     *
     * @return string
     */
    function encrypt_password($password, $salt)
    {
        $block_count = ceil(strlen($salt) / strlen($password));
        $output      = '';
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack('N', $i);
            // first iteration
            $last = $xorsum = hash_hmac('sha256', $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < strlen($last); $j++) {
                $xorsum ^= ($last = hash_hmac('sha1', $last, $password, true));
            }
            $output .= $xorsum;
        }
        return bin2hex(hash_hmac('sha512', $salt, $output, true));
    }
}
if (!function_exists('random_str')) {
    /**
     * 随机字符串.
     *
     * @param  int   $length   随机长度
     * @param  bool  $numeric  是否只取数字
     * @param  bool  $lower    是否小写
     *
     * @return string
     */
    function random_str($length = 6, $numeric = false, $lower = false)
    {
        $map
            = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $maxLength = $numeric ? 9 : 62;
        $str       = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $map[rand(0, $maxLength)];
        }
        return $lower ? strtolower($str) : $str;
    }
}
if (!function_exists('assoc_unique')) {
    function assoc_unique($arr, $key)
    {
        $tmp_arr = [];
        foreach ($arr as $k => $v) {
            if (in_array(
                $v[$key],
                $tmp_arr
            )) { //搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }
}
/**
 * 获取唯一值
 */
if (!function_exists('rand_uniqid')) {
    function rand_uniqid()
    {
        return md5(uniqid(rand()));
    }
}
if (!function_exists('ajaxReturn')) {
    function ajaxReturn($status = 0, $msg = '', $count = 0, $data = array())
    {
        $result = array(
            'code' => $status,
            'msg' => $msg,
            'count' => $count,
            'data' => $data,
            'TOKEN' => session("TOKEN"),
        );
        echo (json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}
if (!function_exists('buildTree')) {
    function buildTree($data = [], $child = true, $label = '', $parent = 0, $deep = 0, &$tree = [])
    {
        $treeParentKey = 'parent_id';
        $treeTitleColumn = 'title';
        if (empty($data)) {
            return [];
        }
        foreach ($data as $key => $val) {
            if ($val[$treeParentKey] == $parent) {
                $val['label'] = str_repeat($label, $deep) . $val[$treeTitleColumn];
                $val['deep']  = $deep;
                $val['target']  = "_self";
                if (!$child) {
                    $tree[] = $val;
                    buildTree($data, $child, $label, $val['id'], $deep + 1, $tree);
                } else {
                    $children = buildTree($data, $child, $label, $val['id'], $deep + 1);
                    if ($children) {
                        $val['child'] = $children;
                    }
                    $tree[] = $val;
                }
            }
        }
        return $tree;
    }
}
if (!function_exists('cc_format')) {
    /**
     * 一个字符串中的大写转换成 _+小写的方式
     * @param [String] $name 字符串
     * @return void
     */
    function cc_format($name)
    {
        $temp_array = array();
        for ($i = 0; $i < strlen($name); $i++) {
            $ascii_code = ord($name[$i]);
            if ($ascii_code >= 65 && $ascii_code <= 90) {
                if ($i == 0) {
                    $temp_array[] = chr($ascii_code + 32);
                } else {
                    $temp_array[] = '_' . chr($ascii_code + 32);
                }
            } else {
                $temp_array[] = $name[$i];
            }
        }
        return implode('', $temp_array);
    }
}
if (!function_exists('file_build_path')) {
    function file_build_path(...$segments)
    {
        return join(DIRECTORY_SEPARATOR, $segments);
    }
}


if (!function_exists('Throwanexception')) {
    /**
     * 抛出异常处理
     *
     * @param string    $msg  异常消息
     * @param integer   $code 异常代码 默认为0
     * @param string    $exception 异常类
     *
     * @throws Exception
     */
    function Throwanexception($msg, $code = 0, $exception = '')
    {
        throw new \Exception($msg, $code);
    }
}