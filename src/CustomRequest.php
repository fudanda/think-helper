<?php

namespace fdd;

use ArrayAccess;

/*
 * 自定义请求对象
 */

class CustomRequest implements ArrayAccess
{
  private $container = array();
  public function offsetSet($offset, $value)
  {
    if (is_null($offset)) {
      $this->container[] = $value;
    } else {
      $this->container[$offset] = $value;
    }
  }

  public function offsetExists($offset)
  {
    return isset($this->container[$offset]);
  }

  public function offsetUnset($offset)
  {
    unset($this->container[$offset]);
  }

  public function offsetGet($offset)
  {
    return isset($this->container[$offset]) ? $this->container[$offset] : null;
  }

  /**
   * 请求对象身份标识
   * @var string
   */
  private $requestId = '';
  //请求数据
  private $params = [];
  //返回数据
  private $callback = [];

  public function __construct($param = [])
  {
    $this->container = array_merge($param, $this->container);
  }
  public static function make($param = [])
  {
    $request = new self($param);
    foreach ($param as $key => $value) {
      $request->$key = $value;
      $request->params[$key] = $value;
    }
    return $request;
  }
  public function setParam($name, $value)
  {
    $this->params[$name] = $value;
    return $this;
  }
  public function setCallback($value = [])
  {
    $this->callback = array_merge($value, $this->callback);
    return $this;
  }
  public function allow($arg = [])
  {
    $params = $this->params;
    foreach ($params as $key => $value) {
      if (!in_array($key, $arg)) {
        unset($params[$key]);
      }
    }
    return $params;
  }

  public function getParam()
  {
    return $this->params;
  }
  /**
   * 魔术方法 设置私有属性
   * @param string $name  属性名称
   * @param string $value 属性值
   */
  public function __set($name = '', $value = '')
  {
    $this->$name = $value;
  }

  /**
   * 魔术方法 获取私有属性
   * @param string $name  属性名称
   */
  public function __get($name = '')
  {
    return $this->$name;
  }
}
