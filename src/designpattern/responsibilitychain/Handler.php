<?php
//责任链
namespace fdd\designpattern\responsibilitychain;

use fdd\CustomRequest;

/**
 * handler抽象类
 */
abstract class Handler
{
  /**
   * 下一个hanler对象
   * @var [type]
   */
  private $_nextHandler;

  private $yield = false; //协程

  /**
   * 返回的数据
   * @var
   */
  private $data = [];

  /**
   * 校验方法
   *
   * @param
   */
  abstract public function Check(CustomRequest $request);


  /**
   * 设置责任链上的下一个对象
   *
   * @param Handler $handler
   */
  public function setNext(Handler $handler)
  {

    $this->_nextHandler = $handler;
    return $handler;
  }

  /**
   * 启动
   *
   * @param Handler $handler
   */
  public function start(CustomRequest $request)
  {
    $this->check($request);
    // 调用下一个对象
    if (!empty($this->_nextHandler)) {
      $this->_nextHandler->start($request);
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

  public function setData($data = [])
  {
    $this->data = array_merge($this->data, $data);

    return $this;
  }
  public function getData()
  {
    return $this->data;
  }
}
