<?php

namespace Lib;

class Request {

  public static function get($name, $default = "") {
    if (isset($_GET[$name])) {
      return trim($_GET[$name]);
    }
    return $default;
  }
  public static function post($name, $default = "") {
    if (isset($_POST[$name])) {
      return trim($_POST[$name]);
    }
    return $default;
  }

}
