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
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		

		if ($this->user_info['permission']['visit_feature'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'] = array(
				'index'
			);
		}
		
		return $rule_action;
	}

	public function setup()
	{
		$this->crumb('专题', '/feature/');
	}

	public function index_action()
	{
		if (is_numeric($_GET['id']))
		{
			if (! $feature_info = $this->model('feature')->get_feature_by_id($_GET['id']))
			{
				H::redirect_msg('专题不存在', '/');
			}
		}
		else if (! $feature_info = $this->model('feature')->get_feature_by_url_token($_GET['id']))
		{
			H::redirect_msg('专题不存在', '/');
		}
		
		if ($feature_info['url_token'] != $_GET['id'])
		{
			HTTP::redirect('/feature/' . $feature_info['url_token']);
		}
		
		if (! $topic_list = $this->model('feature')->get_topics_by_feature_id($feature_info['id']))
		{
			H::redirect_msg('专题下必须包含 1 个以上话题', '/');
		}
		
		foreach ($topic_list as $key => $val)
		{
			$topic_ids[] = $val['topic_id'];
		}
		
		$topic_focus = $this->model('topic')->has_focus_topics($this->user_id, $topic_ids);
	
		foreach ($topic_list as $key => $val)
		{
			$topic_list[$key]['has_focus'] = $topic_focus[$val['topic_id']];
		}
		
		$this->crumb($feature_info['title'], '/feature/' . $feature_info['url_token']);
		
		TPL::import_css('css/main.css');
		
		TPL::assign('sidebar_hot_topics', $topic_list);
		TPL::assign('feature_info', $feature_info);
		
		$nav_menu = $this->model('menu')->get_nav_menu_list(null, true);
		
		if (is_array($nav_menu['feature_ids']) && in_array($feature_info['id'], $nav_menu['feature_ids']))
		{
			// 导航
			if (TPL::is_output('block/content_nav_menu.tpl.htm', 'home/explore'))
			{
				unset($nav_menu['feature_ids']);
				TPL::assign('content_nav_menu', $nav_menu);
			}
			
			// 边栏热门用户
			if (TPL::is_output('block/sidebar_hot_users.tpl.htm', 'home/explore'))
			{
				$sidebar_hot_users = $this->model('module')->sidebar_hot_users($this->user_id, 5);
				
				TPL::assign('sidebar_hot_users', $sidebar_hot_users);
			}
			
			// 边栏专题
			if (TPL::is_output('block/sidebar_feature.tpl.htm', 'home/explore'))
			{
				$feature_list = $this->model('module')->feature_list();
				TPL::assign('feature_list', $feature_list);
			}
			
			if (TPL::is_output('block/content_question.tpl.htm', 'home/explore'))
			{
				if ($feature_info['id'])
				{
					$_GET['topic_id'] = $this->model('feature')->get_topics_by_feature_id($feature_info['id'], false, false);
				}
				
				if (! $_GET['sort_type'])
				{
					$_GET['sort_type'] = 'new';
				}
			
				if ($_GET['sort_type'] == 'unresponsive')
				{
					$_GET['answer_count'] = '0';
				}
				
				$question_list = $this->model('question')->get_questions_list($_GET['page'], get_setting('contents_per_page'), $_GET['sort_type'], $_GET['topic_id'], $_GET['category'], $_GET['answer_count'], $_GET['day']);
				
				TPL::assign('question_list', $question_list);
				TPL::assign('question_list_bit', TPL::output('question/ajax/list', false));
			}
			
			TPL::import_js('js/explore.js');
			
			TPL::output('home/explore');
		}
		else
		{			
			TPL::import_js('js/feature.js');
			
			TPL::output('feature/detail');
		}
	
	}
}