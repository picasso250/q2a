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

class qa_monero_coin
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
	`monero_vote_spend` BIGINT(20) NOT NULL,
	`balance_cache` BIGINT(20) NOT NULL,
	`balance_cache_time` DATETIME NOT NULL,
	PRIMARY KEY (`userid`),
	INDEX `datetime` (`last_spend_time`),
	INDEX `userid` (`userid`),
	INDEX `monero_spent` (`monero_spend`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
";
		}

		return $ret;
	}
	public function option_default($option)
	{
		switch ($option) {
			case 'xml_sitemap_show_categories':
				return true;
		}
	}


	public function admin_form()
	{
		require_once QA_INCLUDE_DIR.'util/sort.php';

		$saved=false;

		if (qa_clicked('monero_coin_save_button')) {
			qa_opt('monero_coin_site_key', qa_post_text('monero_coin_site_key'));
			qa_opt('monero_coin_secret_key', qa_post_text('monero_coin_secret_key'));
			qa_opt('monero_coin_exchange_ratio', intval(qa_post_text('monero_coin_exchange_ratio')));

			$saved=true;
		}

		$form=array(
			'ok' => $saved ? 'Monero Coin settings saved' : null,

			'buttons' => array(
				array(
					'label' => 'Save Changes',
					'tags' => 'name="monero_coin_save_button"',
				),
			),
		);

		$form['fields']['site_key']=array(
			'label' => 'Site Key (public)',
			'type' => 'text',
			'value' => qa_html(qa_opt("monero_coin_site_key")),
			'tags' => 'name="monero_coin_site_key"',
		);
		$form['fields']['secret_key']=array(
			'label' => 'Secret Key (private)',
			'type' => 'text',
			'value' => qa_html(qa_opt("monero_coin_secret_key")),
			'tags' => 'name="monero_coin_secret_key"',
		);
		$form['fields']['exchange_ratio']=array(
			'label' => 'Exchange Ratio (1 Vote = x Hash)',
			'type' => 'text',
			'value' => intval(qa_opt("monero_coin_exchange_ratio")),
			'tags' => 'name="monero_coin_exchange_ratio"',
		);

		return $form;
	}


	public function suggest_requests()
	{
		return array(
			array(
				'title' => 'Monero Coin',
				'request' => 'monero-coin-mining',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}


	public function match_request($request)
	{
		return preg_match('/monero-coin-mining/', $request);
	}

	public static function get_taken($event, $userid) {
		return intval(qa_opt("monero_coin_exchange_ratio"));
	}
	private function ensure_user_spend_row($userid) {
		$sql = "INSERT INTO ^user_monero_spend
			(userid,monero_spend,first_spend_time,last_spend_time,monero_vote_spend,balance_cache,balance_cache_time)
			VALUES
			($,$,$,$,$,$,$)
			ON DUPLICATE KEY UPDATE
			userid=$";
		$now = date('Y-m-d H:i:s');
		$res = qa_db_query_sub($sql,
			$userid, 0, $now, $now, self::get_taken('', $userid), 0, $now,
			$userid);
	}
	private function update_user_spend($userid, $data) {
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
	public function process_request($request)
	{
		$qa_content=qa_content_prepare();

		$qa_content['title']=qa_lang_html('monero_coin/page_title');
		require_once QA_INCLUDE_DIR.'qa-app-users.php';

		if (qa_get_logged_in_userid() === null) {

			$qa_content['error']='you are not login! please login first!';
			$qa_content['custom']='<script src="https://authedmine.com/lib/simple-ui.min.js" async></script>
<div class="coinhive-miner"
	style="width: 256px; height: 310px"
	data-key="'.qa_opt("monero_coin_site_key").'">
	<em>Loading...</em>
</div>';

		} else {
			$us = $this->ensure_user_spend_row(qa_get_logged_in_userid());
			$error_vote_spend = '';
			$error_throttle = '';
			if (qa_post_text('my_monero_vote_spend')) {
				$data = array();
				$my_monero_vote_spend = intval(qa_post_text('my_monero_vote_spend'));
				if (!($my_monero_vote_spend >= 0)) {
					$error_vote_spend = "vote spend must great than or equal to 0";
				} else {
					$data['monero_vote_spend'] = $my_monero_vote_spend;
				}
				if ($data)
					$this->update_user_spend(qa_get_logged_in_userid(), $data);
			}
			$us = $this->get_user_spend(qa_get_logged_in_userid());
			$qa_content['form'] = array(
				'tags' => 'method="post" action="'.qa_self_html().'"',
	
				'style' => 'wide',
	
				'ok' => qa_post_text('my_monero_vote_spend') ? 'Saved!' : null,
	
				'title' => 'Monero Settings',
	
				'fields' => array(
					'my_monero_vote_spend' => array(
						'label' => 'Hash Spent each time you vote(N * '.intval(qa_opt("monero_coin_exchange_ratio")).')',
						'tags' => 'name="my_monero_vote_spend"',
						'value' => qa_html($us['monero_vote_spend']),
						'error' => qa_html($error_vote_spend),
					),
				),
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'name="user_monero_coin_save_button"',
					),
				),
			);
			$user = $this->get_user_balance();
			if (!$user->success) {
				$qa_content['error']='you have not start mining yet, plz start it, wait 1min and fresh this page!';
			}
			ob_start();
			include __DIR__.'/qa-page-mining.php';
			$str = ob_get_clean();
			$qa_content['custom'] = $str;
		}

		return $qa_content;
	}

	private function get_user_balance() {
		require_once __DIR__.'/CoinHiveAPI.php';
		$coinhive = new CoinHiveAPI(qa_opt("monero_coin_secret_key"));
		$user = $coinhive->get('/user/balance', ['name' => 'u'.qa_get_logged_in_userid()]);
		return $user;
	}
	private function get_user_spend($userid) {
		$sql = "SELECT * FROM ^user_monero_spend
			WHERE userid=$ ";
		$res = qa_db_query_sub($sql, $userid);
		return (qa_db_read_one_assoc($res, true));
	}
	/**
	 * @deprecated This function will become private in Q2A 1.8. It is specific to this plugin and
	 * should not be used by outside code.
	 */
	public function sitemap_output($request, $priority)
	{
		echo "\t<url>\n".
			"\t\t<loc>".qa_xml(qa_path($request, null, qa_opt('site_url')))."</loc>\n".
			"\t\t<priority>".max(0, min(1.0, $priority))."</priority>\n".
			"\t</url>\n";
	}
}
