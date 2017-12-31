<?php
namespace Lib;
/**
 *
 */
class Router
{
  private static $_rule = [];
  public static function add($reg, callable $func, $method = "GET")
  {
    self::$_rule[$reg][$method] = $func;
  }
  public static function run()
  {
    $url = self::get_url();
    foreach (self::$_rule as $reg => $arr) {
      foreach ($arr as $method => $func) {
        if ($_SERVER["REQUEST_METHOD"] == $method && preg_match($reg, $url, $m)) {
          $func($m);
          return true;
        }
      }
    }
    return false;
  }
  private static function get_url() {
    $uri = $_SERVER["REQUEST_URI"];
    $a = explode("?", $uri);
    return $a[0];
  }
}
