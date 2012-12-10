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
		$rule_action['rule_type'] = 'black';
		
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function setup()
	{
		$this->crumb('我的收藏', '/favorite/');
		
		TPL::import_css('css/main.css');
	}
	
	public function index_action()
	{
		if ($_GET['tag'])
		{
			$this->crumb('标签: ' . $_GET['tag'], '/favorite/tag-' . $_GET['tag']);
		}
		
		//边栏可能感兴趣的人或话题
		if (TPL::is_output('block/sidebar_recommend_users_topics.tpl.htm', 'favorite/index'))
		{
			$recommend_users_topics = $this->model('module')->recommend_users_topics($this->user_id);
			
			TPL::assign('sidebar_recommend_users_topics', $recommend_users_topics);
		}
		
		if ($action_list = $this->model('favorite')->get_tag_action_list($_GET['tag'], $this->user_id, calc_page_limit($_GET['page'], get_setting('contents_per_page'))))
		{
			foreach ($action_list AS $key => $val)
			{
				$answer_ids[] = $val['answer_info']['answer_id'];
			}
			
			TPL::assign('list', $action_list);
		}
		
		if ($answer_ids)
		{
			$favorite_items_tags = $this->model('favorite')->get_favorite_items_tags_by_answer_id($this->user_id, $answer_ids);
			
			TPL::assign('favorite_items_tags', $favorite_items_tags);
		}
		
		TPL::assign('favorite_tags', $this->model('favorite')->get_favorite_tags($this->user_id));
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/favorite/tag-' . $_GET['tag']), 
			'total_rows' => $this->model('favorite')->count_favorite_items($this->user_id, $_GET['tag']), 
			'per_page' => $this->per_page
		))->create_links());
		
		TPL::output('favorite/index');
	}
}