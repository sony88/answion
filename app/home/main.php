<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Anwsion dev team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/


if (!defined('IN_ANWSION'))
{
	die;
}

class main extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //'black'黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		
		if ($this->user_info['permission']['visit_explore'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
			$rule_action['actions'][] = 'explore';
		}
		
		return $rule_action;
	}

	public function setup()
	{
		if (preg_match('/Opera\sMobi/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/Mobile\sSafari/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/iPhone\sOS/i', $_SERVER['HTTP_USER_AGENT']))
		{
			HTTP::redirect('/mobile/');
		}
	}

	public function index_action()
	{
		if (! $this->user_id)
		{
			$this->explore_action();
			exit;
		}
		
		//边栏可能感兴趣的人或话题
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'home/index'))
		{
			$recommend_users_topics = $this->model('module')->recommend_users_topics($this->user_id);
			
			TPL::assign('sidebar_recommend_users_topics', $recommend_users_topics);
		}
		
		//边栏热门用户
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'home/index'))
		{
			$sidebar_hot_users = $this->model('module')->sidebar_hot_users($this->user_id);
			TPL::assign('sidebar_hot_users', $sidebar_hot_users);
		}
		
		$this->crumb('首页', '/');
		
		TPL::import_css('css/main.css');
		TPL::import_js('js/index.js');
		
		if ($_GET['first_login'])
		{
			TPL::import_js(array(
				'js/LocationSelect.js', 
				'js/ajaxupload.js'
			));
		}
		
		TPL::output("home/index");
	}

	public function explore_action()
	{		
		// 导航
		if (TPL::is_output('block/content_nav_menu.tpl.htm', 'home/explore'))
		{
			$nav_menu = $this->model('menu')->get_nav_menu_list(null, true);
			
			TPL::assign('feature_ids', $nav_menu['feature_ids']);
			
			unset($nav_menu['feature_ids']);
			
			TPL::assign('content_nav_menu', $nav_menu);
		}
		
		//边栏可能感兴趣的人
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'home/explore'))
		{
			$recommend_users_topics = $this->model('module')->recommend_users_topics($this->user_id);
			TPL::assign('sidebar_recommend_users_topics', $recommend_users_topics);
		}
		
		//边栏热门用户
		if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'home/explore'))
		{
			$sidebar_hot_users = $this->model('module')->sidebar_hot_users($this->user_id, 5);
			
			TPL::assign('sidebar_hot_users', $sidebar_hot_users);
		}
		
		//边栏热门话题
		if (TPL::is_output('block/sidebar_hot_topics.tpl.htm', 'home/explore'))
		{
			$sidebar_hot_topics = $this->model('module')->sidebar_hot_topics($this->user_id, $_GET['category']);
			
			TPL::assign('sidebar_hot_topics', $sidebar_hot_topics);
		}
		
		//边栏专题
		if (TPL::is_output('block/sidebar_feature.tpl.htm', 'home/explore'))
		{
			$feature_list = $this->model('module')->feature_list();
			
			TPL::assign('feature_list', $feature_list);
		}
		
		if ($_GET['category'])
		{
			if (is_numeric($_GET['category']))
			{
				$category_info = $this->model('system')->get_category_info($_GET['category']);
			}
			else
			{
				$category_info = $this->model('system')->get_category_info_by_url_token($_GET['category']);
			}
		}
		
		if ($category_info)
		{
			TPL::assign('category_info', $category_info);
			
			$this->crumb($category_info['title'], '/explore/category-' . $category_info['id']);
			
			$meta_description = $category_info['title'];
			
			if ($category_info['description'])
			{
				$meta_description .= ' - ' . $category_info['description'];
			}
			
			TPL::set_meta('description', $meta_description);
		}
		
		// 问题
		if (TPL::is_output('block/content_question.tpl.htm', 'home/explore'))
		{	
			if (! $_GET['sort_type'])
			{
				$_GET['sort_type'] = 'new';
			}
			
			if ($_GET['sort_type'] == 'unresponsive')
			{
				$_GET['answer_count'] = '0';
			}
			
			$question_list = $this->model('question')->get_questions_list($_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $_GET['topic_id'], $category_info['id'], $_GET['answer_count'], $_GET['day']);
			
			TPL::assign('question_list', $question_list);
			TPL::assign('question_list_bit', TPL::output('question/ajax/list', false));
		}
		
		if ($this->user_id)
		{
			$this->crumb('发现', '/home/explore/');
		}
		
		TPL::import_css('css/main.css');
		TPL::import_js('js/explore.js');
		
		TPL::output("home/explore");
	}
}