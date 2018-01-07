<?php

namespace lib;

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
  public static function isAjax() {
    return isset($_GET['ajax'])
      || (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
      && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
  }

}
