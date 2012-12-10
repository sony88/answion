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

	function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();
		return $rule_action;
	}

	public function setup()
	{
		header('Content-type: text/xml; charset=UTF-8');
		
		date_default_timezone_set('UTC');
	}

	public function index_action()
	{
		if ($_GET['topic'])
		{
			$list = $this->model('question')->get_question_list_by_topic_ids($_GET['topic'], 20);
		}
		else
		{
			$list = $this->model('question')->search_questions_list(false, array('category_id' => $_GET['category'], 'per_page' => 20));
		}
		
		TPL::assign('list', $list);
		
		TPL::output('global/feed');
	}
}