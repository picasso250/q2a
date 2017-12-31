<?php

namespace Lib;

class DirAutoLoad {

  public static function loadDir($namespace_root, $dir_root) {
    spl_autoload_register(function ($nc) use ($namespace_root, $dir_root) {
      $prefix = "$namespace_root\\";
      if (strpos($nc, $prefix) === 0) {
        $c = substr($nc, strlen($prefix));
        require "$dir_root/$c.php";
      }
    });
  }

}
