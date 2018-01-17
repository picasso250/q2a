<?php

function _monero_get_user_spend_col($userid, $col) {
  $sql = "SELECT $col FROM ^user_monero_spend
    WHERE userid=$ ";
  $res = qa_db_query_sub($sql, $userid);
  return (qa_db_read_one_value($res, true));
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
  $max = intval(_monero_get_user_spend_col($userid, 'monero_vote_spend'));
  $vote *= $max;
  $vote=(int)min(1*$max, max(-1*$max, $vote));
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