<?php

namespace Lib;

class Service {
  private static $_pool = [];
  private static $_data = [];

  public static function get($name) {
    if (isset(self::$_data[$name])) {
      return self::$_data[$name];
    }
    if (isset(self::$_pool[$name])) {
      $f = self::$_pool[$name];
      self::set($name, $v=$f());
      return $v;
    }
    return null;
  }
  public static function set($name, $value) {
    self::$_data[$name] = $value;
  }

  public static function register($name, $value) {
    if (is_callable($value)) {
      self::register_func($name, $value);
    } else {
      self::set($name, $value);
    }
  }
  public static function register_func($name, callable $c) {
    self::$_pool[$name] = $c;
  }
}
