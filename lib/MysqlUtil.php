<?php

namespace lib;

use Pdo;

/**
 *
 */
class MysqlUtil
{

  public static function timestamp($time = null) {
    $format = "Y-m-d H:i:s";
    if ($time === null) {
      return date($format);
    }
    return date($format, $time);
  }
}
