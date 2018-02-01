<?php

namespace lib;

use BadMethodCallException;
use InvalidArgumentException;

/**
 *
 */
class ErrorLog
{

  public function __call($name, $args) {
    if (in_array($name, ["DEBUG", "INFO", "ERROR", "FATAL", "TRACE", "WARN"])) {
      $this->_do_log($name, $args);
      return $this;
    }
    throw new BadMethodCallException("no method $name");
  }
  private function _do_log($name, $args) {
    $msg = \call_user_func_array('sprintf', $args);
    \error_log(sprintf("%s %s", $name, $msg));
  }

}
