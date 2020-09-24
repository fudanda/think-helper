<?php

namespace fdd\designpattern\responsibilitychain;

use fdd\CustomRequest;

//示例方法
class Start extends Handler
{
  private $error_msg = '错误'; //错误信息
  private $error_code = 404; //状态码
  private $model = ''; //数据模型

  private $yield = false; //协程

  public function __construct($model)
  {
    $this->model = $model;
  }
  public static function make($model = [])
  {
    return new self($model);
  }
  /**
   * 校验方法
   *
   * @param Request $request 请求对象
   */
  public function Check(CustomRequest $request)
  {
    if ($this->yield) {
      yield true;
    } else {
      return true;
    }
  }

  /**
   * 开启协程
   *
   * @param bool $yield
   */
  public function yield($yield = true)
  {
    $this->yield = $yield;
    return $this;
  }
}
