<?php

function _monero_get_user_spend($userid) {
  $sql = "SELECT * FROM ^user_monero_spend
    WHERE userid=$ ";
  $res = qa_db_query_sub($sql, $userid);
  return (qa_db_read_one_assoc($res, true));
}
function _monero_get_user_spend_col($userid, $col) {
  $sql = "SELECT $col FROM ^user_monero_spend
    WHERE userid=$ ";
  $res = qa_db_query_sub($sql, $userid);
  return (qa_db_read_one_value($res, true));
}

function _monero_update_user_spend($userid, $data) {
  $sets = array();
  foreach ($data as $key => $value) {
    $sets[] = "`$key`=$";
  }
  $setss = implode(',', $sets);
  $sql = "UPDATE ^user_monero_spend SET $setss
    WHERE userid=$";
  $args = array_values($data);
  array_unshift($args, $sql);
  array_push($args, $userid);
  call_user_func_array('qa_db_query_sub', $args);
}

function _monero_get_user_balance_with_cache($userid, $us = null) {
  $cache_time = 30;
  if (!$us)
    $us = _monero_get_user_spend($userid);
  $balance_cache_time = $us['balance_cache_time'];
  $now = time();
  if (strtotime($balance_cache_time)+$cache_time < $now) {
    require_once QA_PLUGIN_DIR.'/monero-coin/CoinHiveAPI.php';
    $coinhive = new CoinHiveAPI(qa_opt("monero_coin_secret_key"));
    $user = $coinhive->get('/user/balance', ['name' => 'u'.$userid]);
    $balance = 0;
    if ($user->success) {
      $balance = $user->balance;
    }
    $data = [
      'balance_cache' => $balance,
      'balance_cache_time' => date('Y-m-d H:i:s', $now),
    ];
    _monero_update_user_spend($userid, $data);
    return $balance;
  } else {
    return $us['balance_cache'];
  }
}

function qa_vote_set($post, $userid, $handle, $cookieid, $vote)
/*
Actually set (application level) the $vote (-1/0/1) by $userid (with $handle and $cookieid) on $postid.
Handles user points, recounting and event reports as appropriate.
*/
{

  require_once QA_INCLUDE_DIR.'db/points.php';
  require_once QA_INCLUDE_DIR.'db/hotness.php';
  require_once QA_INCLUDE_DIR.'db/votes.php';
  require_once QA_INCLUDE_DIR.'db/post-create.php';
  require_once QA_INCLUDE_DIR.'app/limits.php';

  // === customize begin ===
  $us = _monero_get_user_spend($userid);
  $monero_vote_spend = intval($us['monero_vote_spend']);
  $spend = $monero_vote_spend * intval(qa_opt('monero_coin_exchange_ratio'));
  $balance = _monero_get_user_balance_with_cache($userid, $us);
  if ($spend + $us['monero_spend'] > $balance) {
    echo "QA_AJAX_RESPONSE\n0\nNot Enough balance";
    exit;
  }
  $vote *= $monero_vote_spend;
  $vote=(int)min(1*$monero_vote_spend, max(-1*$monero_vote_spend, $vote));
  $oldvote=(int)qa_db_uservote_get($post['postid'], $userid);

  $postid = $post['postid'];
  qa_db_query_sub(
    'INSERT INTO ^uservotes (postid, userid, vote, flag) VALUES (#, #, #, 0) ON DUPLICATE KEY UPDATE vote=#',
    $postid, $userid, $vote, $vote
  );
  // === customize end ===
  qa_db_post_recount_votes($post['postid']);

  $postisanswer=($post['basetype']=='A');

  if ($postisanswer) {
    qa_db_post_acount_update($post['parentid']);
    qa_db_unupaqcount_update();
  }

  $columns=array();

  if ( ($vote>0) || ($oldvote>0) )
    $columns[]=$postisanswer ? 'aupvotes' : 'qupvotes';

  if ( ($vote<0) || ($oldvote<0) )
    $columns[]=$postisanswer ? 'adownvotes' : 'qdownvotes';

  qa_db_points_update_ifuser($userid, $columns);

  qa_db_points_update_ifuser($post['userid'], array($postisanswer ? 'avoteds' : 'qvoteds', 'upvoteds', 'downvoteds'));

  if ($post['basetype']=='Q')
    qa_db_hotness_update($post['postid']);

  if ($vote<0)
    $event=$postisanswer ? 'a_vote_down' : 'q_vote_down';
  elseif ($vote>0)
    $event=$postisanswer ? 'a_vote_up' : 'q_vote_up';
  else
    $event=$postisanswer ? 'a_vote_nil' : 'q_vote_nil';

  qa_report_event($event, $userid, $handle, $cookieid, array(
    'postid' => $post['postid'],
    'userid' => $post['userid'],
    'vote' => $vote,
    'oldvote' => $oldvote,
  ));
}