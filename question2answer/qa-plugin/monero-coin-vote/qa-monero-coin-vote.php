<?php
/*
	Question2Answer by Gideon Greenspan and contributors
	http://www.question2answer.org/

	File: qa-plugin/xml-sitemap/qa-xml-sitemap.php
	Description: Page module class for XML sitemap plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

class qa_monero_coin_vote
{
	public function init_queries($table_list)
	{
		$ret = array();
		$tablename=qa_db_add_table_prefix('user_monero_spend');

		if (!in_array($tablename, $table_list)) {
			require_once QA_INCLUDE_DIR.'app/users.php';
			require_once QA_INCLUDE_DIR.'db/maxima.php';

			$ret[] = "CREATE TABLE `^user_monero_spend` (
	`userid` ".qa_get_mysql_user_column_type().",
	`monero_spend` BIGINT(20) UNSIGNED NOT NULL,
	`first_spend_time` DATETIME NOT NULL,
	`last_spend_time` DATETIME NOT NULL,
	`balance_cache` BIGINT(20) NOT NULL,
	`balance_cache_time` DATETIME NOT NULL,
	PRIMARY KEY (`userid`),
	INDEX `datetime` (`last_spend_time`),
	INDEX `userid` (`userid`),
	INDEX `monero_spent` (`monero_spend`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB";
		}
		$tablename=qa_db_add_table_prefix('user_monero_vote');
		if (!in_array($tablename, $table_list)) {
			require_once QA_INCLUDE_DIR.'app/users.php';
			require_once QA_INCLUDE_DIR.'db/maxima.php';

			$ret[] = "CREATE TABLE `^user_monero_vote` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`userid` ".qa_get_mysql_user_column_type().",
	`postid` BIGINT(20) UNSIGNED NOT NULL,
	`vote` BIGINT(20) NOT NULL,
	`create_time` DATETIME NOT NULL,
	`update_time` DATETIME NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `idx_userid_postid` (`userid`, `postid`),
	INDEX `datetime` (`create_time`),
	INDEX `postid` (`postid`),
	INDEX `userid` (`userid`),
	INDEX `monero_vote` (`vote`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB";
		}

		return $ret;
	}
	public function option_default($option)
	{
		switch ($option) {
			case 'xml_sitemap_show_questions':
			case 'xml_sitemap_show_users':
			case 'xml_sitemap_show_tag_qs':
			case 'xml_sitemap_show_category_qs':
			case 'xml_sitemap_show_categories':
				return true;
		}
	}


	public function admin_form()
	{
		require_once QA_INCLUDE_DIR.'util/sort.php';

		$saved=false;

		if (qa_clicked('xml_sitemap_save_button')) {
			qa_opt('xml_sitemap_show_questions', (int)qa_post_text('xml_sitemap_show_questions_field'));

			if (!QA_FINAL_EXTERNAL_USERS)
				qa_opt('xml_sitemap_show_users', (int)qa_post_text('xml_sitemap_show_users_field'));

			if (qa_using_tags())
				qa_opt('xml_sitemap_show_tag_qs', (int)qa_post_text('xml_sitemap_show_tag_qs_field'));

			if (qa_using_categories()) {
				qa_opt('xml_sitemap_show_category_qs', (int)qa_post_text('xml_sitemap_show_category_qs_field'));
				qa_opt('xml_sitemap_show_categories', (int)qa_post_text('xml_sitemap_show_categories_field'));
			}

			$saved=true;
		}

		$form=array(
			'ok' => $saved ? 'XML sitemap settings saved' : null,

			'fields' => array(
				'questions' => array(
					'label' => 'Include question pages',
					'type' => 'checkbox',
					'value' => (int)qa_opt('xml_sitemap_show_questions'),
					'tags' => 'name="xml_sitemap_show_questions_field"',
				),
			),

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="xml_sitemap_save_button"',
				),
			),
		);

		if (!QA_FINAL_EXTERNAL_USERS)
			$form['fields']['users']=array(
				'label' => 'Include user pages',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_users'),
				'tags' => 'name="xml_sitemap_show_users_field"',
			);

		if (qa_using_tags())
			$form['fields']['tagqs']=array(
				'label' => 'Include question list for each tag',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_tag_qs'),
				'tags' => 'name="xml_sitemap_show_tag_qs_field"',
			);

		if (qa_using_categories()) {
			$form['fields']['categoryqs']=array(
				'label' => 'Include question list for each category',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_category_qs'),
				'tags' => 'name="xml_sitemap_show_category_qs_field"',
			);

			$form['fields']['categories']=array(
				'label' => 'Include category browser',
				'type' => 'checkbox',
				'value' => (int)qa_opt('xml_sitemap_show_categories'),
				'tags' => 'name="xml_sitemap_show_categories_field"',
			);
		}

		return $form;
	}

	private function get_sign($event) {
		if (strpos($event, '_vote_up')) {
			return 1;
		} else if (strpos($event, '_vote_nil')) {
			return 0;
		} else {
			return -1;
		}
	}

	private function upsert_vote($event, $userid, $postid) {
		$sql = "INSERT INTO ^user_monero_vote
			(userid,postid,vote,create_time,update_time)
			VALUES
			($,$,$,$,$)
			ON DUPLICATE KEY UPDATE
			vote=$,update_time=$
			";
		$now = date('Y-m-d H:i:s');
		$taken = self::get_taken($event, $userid);
		$vote = $this->get_sign($event)*$taken;
		$res = qa_db_query_sub($sql,
			$userid, $postid, $taken, $now, $now,
			$vote, $now);
	}
	private function upsert_spend($event, $userid, $postid) {
		$sql = "SELECT SUM(ABS(vote)) AS s
			FROM ^user_monero_vote
			WHERE userid=$";
		$res = qa_db_query_sub($sql, $userid);
		$row = qa_db_read_one_assoc($res);
		$sql = "INSERT INTO ^user_monero_spend
			(userid,monero_spend,first_spend_time,last_spend_time,monero_vote_spend,balance_cache,balance_cache_time)
			VALUES
			($,$,$,$,$,$,$)
			ON DUPLICATE KEY UPDATE
			monero_spend=$,last_spend_time=$
			";
		$now = date('Y-m-d H:i:s');
		$res = qa_db_query_sub($sql,
			$userid, $row['s'], $now, $now, self::get_taken(), 0, $now,
			$row['s'], $now);
	}

	private function set_cache($userid, $balance) {
		$sql = "INSERT INTO ^user_monero_spend
			(userid,monero_spend,first_spend_time,last_spend_time,monero_vote_spend,balance_cache,balance_cache_time)
			VALUES
			($,$,$,$,$,$,$)
			ON DUPLICATE KEY UPDATE
			balance_cache=$,last_spend_time=$";
		$now = date('Y-m-d H:i:s');
		$res = qa_db_query_sub($sql,
			$userid, 0, $now, $now, self::get_taken(), $balance, $now,
			$balance, $now);
	}
	private function get_user_spend_col($userid, $col) {
		$sql = "SELECT $col FROM ^user_monero_spend
			WHERE userid=$ ";
		$res = qa_db_query_sub($sql, $userid);
		return (qa_db_read_one_value($res, true));
	}
	private function get_monero_vote_spend($userid) {
		return intval($this->get_user_spend_col($userid, 'monero_vote_spend'));
	}
	private function get_balance_cache($userid) {
		return intval($this->get_user_spend_col($userid, 'balance_cache'));
	}
	private function get_balance($userid) {
		static $balance;
		if ($balance) {
			return $balance;
		}
		$balance = $this->get_balance_cache($userid);
		if ($balance) return $balance;
		require_once __DIR__.'/../monero-coin/CoinHiveAPI.php';
		$coinhive = new CoinHiveAPI(qa_opt("monero_coin_secret_key"));
		$user = $coinhive->get('/user/balance', ['name' => 'u'.qa_get_logged_in_userid()]);
		if (!$user->success) {
			$balance = 0;
		} else {
			$balance = $user->balance;
		}
		$this->set_cache($userid, $balance);
		return $balance;
	}
	public static function get_taken() {
		return intval(qa_opt("monero_coin_exchange_ratio"));
	}

	public function process_event($event, $userid, $handle, $cookieid, $params)
	{
		if (strpos($event, '_vote_')) {
			// error_log("$event")
			$db = qa_db_connection();
			$db->begin_transaction();
			$balance = $this->get_balance($userid);
			$taken = self::get_taken($event, $userid);
			if ($balance >= $taken) {
				$postid = $params['postid'];
				$this->upsert_vote($event, $userid, $postid);
				if ($taken != 0)
					$this->upsert_spend($event, $userid, $postid);
			}
			$db->commit();
		}
	}
}
