<?php

// 一些小函数，入选标准：不能只是语法糖，必须做了一些实事

function env_load($dir='.')
{
  $file = "$dir/.env";
  if (!is_file($file)) {
    echo "no .env file $file\n";
    exit(1);
  }
  if (!is_readable($file)) {
    echo ".env $file file not readable\n";
    exit(1);
  }
  $config = parse_ini_file($file);
  foreach ($config as $key => $value) {
    if (!isset($_ENV[$key])) {
      $_ENV[$key] = $value;
    }
  }
}

function autoload_dir($namespace_root, $dir_root) {
  spl_autoload_register(function ($nc) use ($namespace_root, $dir_root) {
    $prefix = "$namespace_root\\";
    if (strpos($nc, $prefix) === 0) {
      $c = substr($nc, strlen($prefix));
      require "$dir_root/$c.php";
    }
  });
}

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
function error2exception()
{
  set_error_handler("exception_error_handler");
}
