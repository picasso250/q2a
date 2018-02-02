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

// $redis = new Redis();
// $redis->connect('127.0.0.1');
// Task::$redis = $redis;

// require __DIR__.'/init.inc.php';
Model::$db = $db = qa_db_connection();
$log = new ErrorLog();
Model::$log = $log;

// check user perm
if (($userid = qa_get_logged_in_userid()) == null) {
  die("plz login first");
}
$logged_in_level = qa_get_logged_in_level();
if (!($logged_in_level >= QA_USER_LEVEL_ADMIN)) {
  die("no permission");
}

$site_mail_to = Request::GET('site_mail_to');
if ($site_mail_to) {
  $author = zhihu_user::getByName($site_mail_to);
  $answer_list = zhihu_fetch::getAnswerByUsername($site_mail_to);
  include ROOT.'/view/mail_site.php';
  return;
}

if (Request::isAjax()) {
  $new_state = Request::POST("change_to_state");
  $id = Request::POST("id");
  zhihu_user::editById($id, ['state' => $new_state]);
  echo "OK";
  return;
}


// view

$cur_cate = Request::GET('state', 0);
$cate_list = [
  '0' => '未处理',
  '1' => '等待回复',
  '2' => '已转载',
  '3' => '放弃',
  '4' => '作者拒绝',
];

$limit = 100;
$where = [
  "state=$cur_cate",
];
$entry_list = zhihu_user::sqlBuilder()->where($where)->limit($limit)->getAll();
$total      = zhihu_user::sqlBuilder()->where($where)->count();

$_inner_tpl_ = 'audit.php';
include ROOT.'/view/layout.php';
