<?php

use lib\Model;
use lib\MysqlUtil;
use lib\Task;
use lib\Request;
use lib\Response;
use lib\ErrorLog;

define('PUB_ROOT', dirname(__DIR__));
define('ROOT', dirname(PUB_ROOT));

require ROOT."/vendor/autoload.php";
require ROOT."/lib/autoload.php";
require (ROOT)."/model.php";

require_once PUB_ROOT.'/qa-include/qa-base.php';
require_once QA_INCLUDE_DIR.'qa-app-users.php';
require_once QA_INCLUDE_DIR.'qa-app-posts.php';

// require __DIR__.'/init.inc.php';
Model::$db = $db = qa_db_connection();
$log = new ErrorLog();
Model::$log = $log;


if (Request::isAjax()) {
  $new_state = Request::POST("change_to_state");
  $id = Request::POST("id");
  zhihu_user::editById($id, ['state' => $new_state]);
  echo "OK";
  return;
}
$salt = Request::GET('u');
if (!$salt) {
  die("no u");
}
$author = zhihu_user::getBySalt($salt);
if (!$author)
  die("no user");

$_inner_tpl_ = 'user-guide.php';
include ROOT.'/view/layout.php';
