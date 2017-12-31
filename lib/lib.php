<?php

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
