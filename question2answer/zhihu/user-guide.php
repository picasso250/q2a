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

env_load(ROOT);

require_once PUB_ROOT.'/qa-include/qa-base.php';
require_once QA_INCLUDE_DIR.'qa-app-users.php';
require_once QA_INCLUDE_DIR.'qa-app-posts.php';

// require __DIR__.'/init.inc.php';
Model::$db = $db = qa_db_connection();
$log = new ErrorLog();
Model::$log = $log;

$salt = Request::GET('u');
if (!$salt) {
  die("no u");
}
$author = zhihu_user::getBySalt($salt);
if (!$author)
  die("no user");

$after_register = Request::GET('after_register');
if ($after_register) {
  $db->begin_transaction();
  zhihu_user::importByAuthor($author);
  $db->commit();
  Response::redirect("/");
  return;
}

$answer_list = zhihu_fetch::getAnswerByUsername($author['username']);

$back_url_data = ['u'=>$salt, 'after_register'=>1];
$back_url = 'zhihu/user-guide.php?'.http_build_query($back_url_data);

$page_title = '用户指引';
$_inner_tpl_ = 'user-guide.php';
include ROOT.'/view/layout.php';
