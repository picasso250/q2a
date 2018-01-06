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
      'state' => 0,
      "comment" => "",
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
      'state' => 0,
      "comment" => "",
    ], $data);
    $sqlb = self::sqlBuilder();
    return $ok = $sqlb->insert($d);
  }
}
class zhihu_fetch extends ZhihuFetch {}

class ZhihuUser extends Model
{
  static $table = 'zhihu_user';
}
class zhihu_user extends ZhihuUser {
  static function getByName($name) {
    return self::sqlBuilder()->where([
      ['username',$name],
    ])->getOne();
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
