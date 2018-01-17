<?php

namespace lib;

class Response {

  public static function echoJsonOk($data = "", $msg = "OK") {
    self::echoJson([
      "code" => 0,
      "data" => $data,
      "msg" => $msg
    ]);
  }
  public static function echoJsonError($code, $msg, $data = "") {
    self::echoJson([
      "code" => $code,
      "data" => $data,
      "msg" => $msg
    ]);
  }
  public static function echoJson(array $json) {
    header("Content-Type: application/json");
    if (isset($_GET['json_pretty_format'])) {
      echo json_encode($json);
    } else {
      echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }
  }
  public function redirect($url="?")
  {
    header("Location: $url");
  }
}
