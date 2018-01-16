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
		$tablename=qa_db_add_table_prefix('user_monero_spent');

		if (!in_array($tablename, $table_list)) {
			require_once QA_INCLUDE_DIR.'app/users.php';
			require_once QA_INCLUDE_DIR.'db/maxima.php';

			$ret[] = 'CREATE TABLE ^user_monero_spent ('.
				'last_spent_time DATETIME NOT NULL,'.
				'userid '.qa_get_mysql_user_column_type().','.
				'monero_spent BIGINT UNSIGNED,'.
				'KEY datetime (last_spent_time),'.
				'KEY userid (userid),'.
				'KEY monero_spent (monero_spent)'.
			') ENGINE=InnoDB DEFAULT CHARSET=utf8';
		}
		$tablename=qa_db_add_table_prefix('user_monero_vote');
		if (!in_array($tablename, $table_list)) {
			require_once QA_INCLUDE_DIR.'app/users.php';
			require_once QA_INCLUDE_DIR.'db/maxima.php';

			$ret[] = 'CREATE TABLE ^user_monero_vote ('.
				'datetime DATETIME NOT NULL,'.
				'userid '.qa_get_mysql_user_column_type().','.
				'postid BIGINT UNSIGNED,'.
				'monero_vote BIGINT,'.
				'KEY datetime (datetime),'.
				'KEY postid (postid),'.
				'KEY userid (userid),'.
				'KEY monero_vote (monero_vote)'.
			') ENGINE=MyISAM DEFAULT CHARSET=utf8';
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

		if (qa_clicked('monero_coin_save_button')) {
			qa_opt('monero_coin_secret_key', qa_post_text('monero_coin_secret_key'));

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
			'label' => 'Secret Key',
			'type' => 'text',
			'value' => qa_html(qa_opt("monero_coin_secret_key")),
			'tags' => 'name="monero_coin_secret_key"',
		);

		return $form;
	}


	public function suggest_requests()
	{
		return array(
			array(
				'title' => 'Monero Coin Mining',
				'request' => 'monero-coin-mining',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}


	public function match_request($request)
	{
		return preg_match('/monero-coin-mining/', $request);
	}


	public function process_request($request)
	{
		@ini_set('display_errors', 0); // we don't want to show PHP errors inside XML

		header('Content-type: text/xml; charset=utf-8');

		echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";


	//	Question pages

		if (qa_opt('xml_sitemap_show_questions')) {
			$hotstats=qa_db_read_one_assoc(qa_db_query_sub(
				"SELECT MIN(hotness) AS base, MAX(hotness)-MIN(hotness) AS spread FROM ^posts WHERE type='Q'"
			));

			$nextpostid=0;

			while (1) {
				$questions=qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT postid, title, hotness FROM ^posts WHERE postid>=# AND type='Q' ORDER BY postid LIMIT 100",
					$nextpostid
				));

				if (!count($questions))
					break;

				foreach ($questions as $question) {
					$this->sitemap_output(qa_q_request($question['postid'], $question['title']),
						0.1+0.9*($question['hotness']-$hotstats['base'])/(1+$hotstats['spread']));
					$nextpostid=max($nextpostid, $question['postid']+1);
				}
			}
		}


	//	User pages

		if ((!QA_FINAL_EXTERNAL_USERS) && qa_opt('xml_sitemap_show_users')) {
			$nextuserid=0;

			while (1) {
				$users=qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT userid, handle FROM ^users WHERE userid>=# ORDER BY userid LIMIT 100",
					$nextuserid
				));

				if (!count($users))
					break;

				foreach ($users as $user) {
					$this->sitemap_output('user/'.$user['handle'], 0.25);
					$nextuserid=max($nextuserid, $user['userid']+1);
				}
			}
		}


	//	Tag pages

		if (qa_using_tags() && qa_opt('xml_sitemap_show_tag_qs')) {
			$nextwordid=0;

			while (1) {
				$tagwords=qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT wordid, word, tagcount FROM ^words WHERE wordid>=# AND tagcount>0 ORDER BY wordid LIMIT 100",
					$nextwordid
				));

				if (!count($tagwords))
					break;

				foreach ($tagwords as $tagword) {
					$this->sitemap_output('tag/'.$tagword['word'], 0.5/(1+(1/$tagword['tagcount']))); // priority between 0.25 and 0.5 depending on tag frequency
					$nextwordid=max($nextwordid, $tagword['wordid']+1);
				}
			}
		}


	//	Question list for each category

		if (qa_using_categories() && qa_opt('xml_sitemap_show_category_qs')) {
			$nextcategoryid=0;

			while (1) {
				$categories=qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT categoryid, backpath FROM ^categories WHERE categoryid>=# AND qcount>0 ORDER BY categoryid LIMIT 2",
					$nextcategoryid
				));

				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$this->sitemap_output('questions/'.implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid=max($nextcategoryid, $category['categoryid']+1);
				}
			}
		}


	//	Pages in category browser

		if (qa_using_categories() && qa_opt('xml_sitemap_show_categories')) {
			$this->sitemap_output('categories', 0.5);

			$nextcategoryid=0;

			while (1) { // only find categories with a child
				$categories=qa_db_read_all_assoc(qa_db_query_sub(
					"SELECT parent.categoryid, parent.backpath FROM ^categories AS parent ".
					"JOIN ^categories AS child ON child.parentid=parent.categoryid WHERE parent.categoryid>=# GROUP BY parent.categoryid LIMIT 100",
					$nextcategoryid
				));

				if (!count($categories))
					break;

				foreach ($categories as $category) {
					$this->sitemap_output('categories/'.implode('/', array_reverse(explode('/', $category['backpath']))), 0.5);
					$nextcategoryid=max($nextcategoryid, $category['categoryid']+1);
				}
			}
		}

		echo "</urlset>\n";

		return null;
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
