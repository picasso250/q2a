<?php

use lib\Model;
use lib\MysqlUtil;
use lib\Curl;

require (__DIR__)."/init.inc.php";
Curl::$cache_dir = dirname(__DIR__).'/data/http_get';

$coid = '20430715';
if (isset($argv[1])) {
    $coid = $argv[1];
}
$base_url = 'https://www.zhihu.com';
$url = "$base_url/collection/$coid";
echo "fetch $url\n";
list($code, $content) = Curl::get($url);
// $code = 200; $content = file_get_contents(__DIR__.'/cache/'.urlencode($url));
// echo "$code\t$content\n";
if ($code == 404) {
    echo "没有这个收藏 $coid\n";
    exit(1);
}

Model::$db = $db = qa_db_connection();

phpQuery::newDocumentHtml($content);
$n = get_page_num();
// $n = 1;
echo "$n pages\n";
if ($n == 0) {
  echo "no page\n";
  exit(1);
}
$answer_list = [];
for ($i=1; $i <= $n; $i++) {
  $purl = "$url?page=$i";
  echo "$purl\n";
  list($code, $content) = Curl::get($purl);
  if ($code != 200) {
    echo "bad http code $code\n";
    exit(1);
  }
  phpQuery::newDocumentHtml($content);
  echo count(pq('textarea.content')),PHP_EOL;
  pq('.post-content')->each(function($e) {
    echo "skip ","\tUrl: ",pq($e)->attr("data-entry-url"),PHP_EOL;
    // echo html_entity_decode(pq($e)->html());
  });
  // answers
  $al = pq('.zm-item-fav')->map(function ($e) {
    $irt = pq($e)->find('.zm-item-rich-text');
    $r = new stdClass();
    if ($irt->size()) {
        $author_name = $irt->attr('data-author-name');
        if ($author_name) {
          $entry_url = $irt->attr("data-entry-url");
          if (preg_match("#/question/(\d+)/answer/(\d+)#", $entry_url, $m)) {
            $qid = $m[1];
            $aid = $m[2];
            if (!ZhihuFetch::getAnswerOne([ ['aid',$aid], ['qid',$qid] ])) {
              $html = html_entity_decode(pq($e)->find('textarea')->eq(0)->html());
              echo "Author:",$author_name,"\tUrl: ",$entry_url,PHP_EOL;
              $r->author_name = $author_name;
              $r->entry_url = $entry_url;
              $r->html = $html;
              $r->qid = $qid;
              $r->aid = $aid;
              $uhref = pq($e)->find('.author-link')->attr('href');
              $r->uhref = $uhref;
              $adl = pq($e)->find('.answer-date-link')->text();
              $r->date = preg_replace('/[^-\d]/', '', $adl);
              return $r;
            }
          }
        }
    }
    return $r;
  })->get();
  $answer_list = array_merge($answer_list, $al);
}

$entry_content_list = [];
$f = fopen("collection_".$coid.".html", "w");
fwrite($f, "<body>\n");
foreach ($answer_list as $answer) {
  if (isset($answer->author_name)) {
    $author_name = $answer->author_name;
    $entry_url = $answer->entry_url;
    $html = $answer->html;
    $qid = $answer->qid;
    $aid = $answer->aid;
    $entry_content_list[] = $html;
    echo "save html for $entry_url ...\n";
    list($title, $detail) = get_question($qid);
    $zq = ZhihuFetch::getQuestionOne([ ['qid',$qid] ]);
    if (!$zq) {
      $entry_content_list[] = $detail;

      $ok = $db->begin_transaction();
      if (!$ok) {
        echo "begin_transaction fail\n";
        exit(1);
      }

      $pqid = Post::addQuestion([
        'title' => $title,
        'content' => preg_replace(IMG_ROOT_URL_OLD, IMG_ROOT_URL, $detail),
      ]);
      echo "add Q=$pqid\n";

      $ok = ZhihuFetch::addQuestion([
        'postid' => $pqid,
        'title' => $title,
        'detail' => $detail,
        'qid' => $qid,
        "username" => "",
      ]);
      if (!$ok) {
        echo "addQuestion sql error\n";
        echo ZhihuFetch::$_sqlb->sql,"\n";
        var_dump(ZhihuFetch::$_sqlb->stmt);
        exit(1);
      }
      $zqid = $db->insert_id;
      echo "save question $zqid => $pqid\n";
      $db->commit();

    } else {
      $pqid = $zq['postid'];
      echo "get Q=$pqid\n";
    }
    // insert user and answer
    $ok = $db->begin_transaction();
    if (!$ok) {
      echo "begin_transaction fail\n";
      exit(1);
    }
    if (preg_match("#/people/(.+)#", $answer->uhref, $m)) {
      $username = $m[1];
      $zu = zhihu_user::sqlBuilder()->where([
        ['username', $username]
      ])->getOne();
      if ($zu) {
        echo "already $username\n";
        $userid = $zu['userid'];
      } else {
        echo "push user $username\n";
        $userid = User::add([
          'handle' => $author_name,
        ]);
        zhihu_user::sqlBuilder()->insert([
          'userid' => $userid,
          'username' => $username,
          'showname' => $author_name,
          'fetch_time' => MysqlUtil::timestamp(),
        ]);
      }
    }

    $paid = 0;

    $zaid = ZhihuFetch::addAnswer([
      'postid' => $paid,
      'detail' => $html,
      'qid' => $qid,
      'aid' => $aid,
      'edit_time' => $answer->date,
      'state' => 0,
      'username' => $username,
    ]);
    $zaid = $db->insert_id;
    echo "save answer $zaid => $paid\n";

    $db->commit();

    fwrite($f, "<strong>$author_name</strong> <a href='$base_url$entry_url'>$base_url$entry_url</a><br>\n");
    fwrite($f, "<div><h3>$title</h3>$detail</div><hr>\n");
    fwrite($f, "$html\n<hr>\n");
  }
}
fclose($f);
echo "OK\n";

function get_page_num() {
  $arr = pq('.border-pager a')->map(function ($e) {
    $href = pq($e)->attr('href');
    $a = explode('=', $href);
    if (count($a) == 2) {
      return intval($a[1]);
    }
    return 0;
  })->get();
  rsort($arr);
  return count($arr) > 0 ? $arr[0] : 0;
}

function get_question($qid) {
  $url = BASE_URL.'/question/'.$qid;
  echo "curl $url\n";
  list($code, $content) = Curl::get($url);
  if ($code == 28) {
    echo "retry\n";
    sleep(20);
    list($code, $content) = Curl::get($url);
  }
  if ($code != 200) {
    echo "bad code $code $content\n";
    exit(1);
  }
  phpQuery::newDocumentHtml($content);
  $h1 = pq('h1');
  $h1->find('*')->remove();
  $title = $h1->text();
  echo "$title\n";
  $data_state = json_decode(html_entity_decode(pq('#data')->attr('data-state')),true);
  $detail = $data_state['entities']['questions'][$qid]['detail'];
  return [$title, $detail];
}
