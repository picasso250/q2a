<?php

namespace Lib;

use stdClass;

class Task {

  static $redis;
  static $queue_key = 'task_queue';
  static $queue_result_key = 'task_queue_result';
  static $result_expire_time = 3*24*3600;
  public static function add() {
    $queue_result_key = self::$queue_result_key;
    $redis = self::$redis;
    $expire_time = self::$result_expire_time;
    $args = func_get_args();
    $task_id = uniqid();
    $push_time = time();
    $task = [
      'task_id' => $task_id,
      'push_time' => $push_time,
      'push_time_str' => date('c', $push_time),
      'args' => $args,
      'state' => 'waiting',
    ];
    self::$redis->rPush(self::$queue_key, json_encode($task));
    $redis->setEx("$queue_result_key:$task_id", $expire_time, json_encode($task));
    return $task_id;
  }
  public static function get($task_id) {
    $queue_result_key = self::$queue_result_key;
    $r = self::$redis->get("$queue_result_key:$task_id");
    if ($r) return json_decode($r);
    return $r;
  }
  public static function edit($task_id, $data) {
    $queue_result_key = self::$queue_result_key;
    $r = self::$redis->get("$queue_result_key:$task_id");
    if ($r) {
      $j = json_decode($r);
    } else {
      $j = new stdClass();
    }
    foreach ($data as $key => $value) {
      $j->$key = $value;
    }
    self::$redis->setEx("$queue_result_key:$task_id", self::$result_expire_time, json_encode($j));
    return $r;
  }
  public static function runBackground() {
    $queue_key = self::$queue_key;
    $queue_result_key = self::$queue_result_key;
    $redis = self::$redis;
    for (;;) {
      echo $redis->llen($queue_key)," tasks remain\n";
      $a = $redis->blPop($queue_key, 100);
      if (!$a) {
        continue;
      }
      list($_, $j) = $a;
      $task = json_decode($j);
      $task_id = $task->task_id;
      $args = $task->args;
      $func_name = array_shift($args);
      $res = $task;
      if (is_array($func_name)) {
        list($class_name, $method) = $func_name;
        if (class_exists($class_name)) {
          $t = new $class_name();
          $t->task_id = $task_id;
          if (method_exists($t, $method)) {
            self::_beforeTask($task_id);
            $result = call_user_func_array([$t, $method], $args);
            self::_afterTask($task_id, $result);
          } else {
            $redis->lPush($queue_key, $j);
          }
        } else {
          $redis->lPush($queue_key, $j);
        }
      } else if (function_exists($func_name)) {
        echo "do $func_name\n";
        self::_beforeTask($task_id);
        $result = call_user_func_array($func_name, $args);
        self::_afterTask($task_id, $result);
      } else {
        $redis->lPush($queue_key, $j);
      }
    }
  }
  public static function _beforeTask($task_id) {
    $data = [
      "state" => 'doing',
      "start_time" => time(),
      "start_time_str" => date('c'),
    ];
    self::edit($task_id, $data);
  }
  public static function _afterTask($task_id, $result) {
    $data = [
      "state" => 'done',
      "done_time" => time(),
      "done_time_str" => date('c'),
      "result" => $result,
    ];
    self::edit($task_id, $data);
  }
  public static function clearAll() {
    $queue_key = self::$queue_key;
    $queue_result_key = self::$queue_result_key;
    $redis = self::$redis;
    for (;$a = $redis->lPop($queue_key);) {
      echo "clear $a\n";
    }
  }

}
