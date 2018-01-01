<?php
namespace lib;

/**
 *
 */
class Model
{
  public static $db_getter;
  public static $db;
  public static function db() {
    if (self::$db) {
      return self::$db;
    }
    if (self::$db_getter) {
      $f = self::$db_getter;
      return self::$db = $f();
    }
    return null;
  }
  public static function table() {
    if (isset(static::$table)) {
      return static::$table;
    }
    $c = get_called_class();
    $arr = explode("\\", $c);
    return strtolower($arr[count($arr)-1]);
  }
  public static function pkey() {
    if (isset(static::$pkey)) {
      return static::$pkey;
    }
    return "id";
  }
  public static function sqlBuilder($as = "") {
    $sb = new SqlBuilder(self::db());
    $sb->from(self::table(), $as);
    return $sb;
  }

  // only support pdo-mysql
  public static function transaction(callable $callback) {
    $db = self::db();
    if ($db->inTransaction()) {
      return $callback();
    }
    $db->beginTransaction();
    $ret = $callback();
    if ($ret !== false) {
      $db->commit();
    }
    return $ret;
  }

  public static function add(array $data) {
    self::sqlBuilder()->insert($data);
    $db = self::db();
    if (get_class($db) == 'mysqli') {
      return $db->insert_id;
    }
    return $db->lastInsertId();
  }
  public static function editById($id, array $data) {
    return self::sqlBuilder()->where([[self::pkey(),$id]])
      ->limit(1)->update($data);
  }
  public static function delById($id) {
    return self::sqlBuilder()->where([[self::pkey(),$id]])
      ->delete(self::table());
  }
  public static function getById($id)
  {
    return self::sqlBuilder()->where([[self::pkey(),$id]])->getOne();
  }

}
