<?php
namespace lib;
/**
 *
 */
class EnvConfig
{

  public static function load($root)
  {
    $ENV = "PRD";
    $env_file = "$root/ENV";
    if (file_exists($env_file)) {
      $ENV = trim(file_get_contents($env_file));
    }
    $env_config_file = "$root/ENV.$ENV.json";
    if (!file_exists($env_config_file)) {
      die("no env_config_file for $ENV");
    }
    $env_config = json_decode(file_get_contents($env_config_file), true);
    $main = json_decode(file_get_contents("$root/main.json"), true);
    return array_merge($main, $env_config);
  }
}
