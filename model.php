<?php

use lib\Model;
use lib\MysqlUtil;

class Post extends Model
{
  static $table = 'qa_posts';
  static $pkey = 'postid';
  static function addQuestion($data)
  {
    $type = 'Q'; // question
    $parentid = null; // does not follow another answer
    $title = $data['title'];
    $content = $data['content'];
    $format = 'html'; // plain text
    $categoryid = null; // assume no category
    $tags = array();
    $userid = 3; // zhihu_fetch
    return qa_post_create($type, $parentid, $title, $content, $format, $categoryid, $tags, $userid);
  }
  static function addAnswer($data)
  {
    $type = 'A';
    $parentid = $data['parentid'];
    $title = '';
    $content = $data['content'];
    $format = 'html';
    $categoryid = null; // assume no category
    $tags = array();
    $userid = $data['userid'];
    $postid = qa_post_create($type, $parentid, $title, $content, $format, $categoryid, $tags, $userid);
    // updated time
    $ok = self::editById($postid, $data);
    if (!$ok) {
      echo self::db()->errno,"\t",self::db()->error,PHP_EOL;
      exit(1);
    }
    return $postid;
  }
}

class ZhihuFetch extends Model
{
  const TQ = 1;
  const TA = 2;

  static $_sqlb;
  static $table = 'zhihu_fetch';
  static function getAnswerOne($where)
  {
    $w = array_merge([ ['type', zhihu_fetch::TA] ], $where);
    return ZhihuFetch::sqlBuilder()->where($w)->getOne();
  }
  static function getQuestionOne($where)
  {
    $w = array_merge([ ['type', zhihu_fetch::TQ] ], $where);
    return ZhihuFetch::sqlBuilder()->where($w)->getOne();
  }
  static function getQ_byId($qid)
  {
    return ZhihuFetch::getQuestionOne([['qid',$qid]]);
  }
  static function addQuestion($data)
  {
    $d = array_merge([
      'type' => self::TQ,
      'aid' => 0,
      'fetch_time' => MysqlUtil::timestamp(),
      'edit_time' => MysqlUtil::timestamp(),
    ], $data);
    self::$_sqlb = self::sqlBuilder();
    return $ok = self::$_sqlb->insert($d);
  }
  static function addAnswer($data)
  {
    $d = array_merge([
      'type' => self::TA,
      'title' => "",
      'fetch_time' => MysqlUtil::timestamp(),
    ], $data);
    $sqlb = self::sqlBuilder();
    return $ok = $sqlb->insert($d);
  }
}
class zhihu_fetch extends ZhihuFetch {
  const STATE_NOT_PROC = 0;
  const STATE_WAIT_REPLY = 1;
  const STATE_HAVE_REPUB = 2;
  const STATE_ABANDON = 3;
  const STATE_AUTHOR_REFUSE = 4;
  
  static function countAnswerByUsername($username) {
    $where = [
      "type=".zhihu_fetch::TA,
      "username=$username",
    ];
    return zhihu_fetch::sqlBuilder()->where($where)->count();
  }
  static function getAnswerByUsername($username) {
    $where = [
      "type=".zhihu_fetch::TA,
      "username=$username",
    ];
    return zhihu_fetch::sqlBuilder()->where($where)->getAll();
  }
  static function getQuestionByZhihuId($id) {
    $where = [
      "qid=$id",
    ];
    return zhihu_fetch::sqlBuilder()->where($where)->getOne();
  }
}

class zhihu_user extends Model {
  static $table = 'zhihu_user';
  static function getByName($name) {
    return self::sqlBuilder()->where([
      ['username',$name],
    ])->getOne();
  }
  static function getBySalt($salt) {
    return self::sqlBuilder()->where([
      ['salt',$salt],
    ])->getOne();
  }
  public static function countByCate($cate) {
    return self::sqlBuilder()->where(["state=$cate"])->count();
  }
}

class User extends Model
{
  static $table = 'qa_users';
  public static function add(array $data)
  {
    $d = array_merge([
      'created' => MysqlUtil::timestamp(),
      'createip' => 0,
      'email' => '',
      'level' => 0,
      'loggedin' => MysqlUtil::timestamp(),
      'loginip' => 0,
    ], $data);
    return parent::add($d);
  }
}
