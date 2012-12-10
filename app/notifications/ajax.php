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

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	var $per_page = 10;
	
	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		return $rule_action;
	}

	function setup()
	{
		HTTP::no_cache_header();
		
		if (get_setting('notifications_per_page'))
		{
			$this->per_page = get_setting('notifications_per_page');
		}
	}
	
	public function list_action()
	{	
		if(!$_GET['per_page'])
		{
			$_GET['per_page'] = $this->per_page;
		}
		
		$list = $this->model('notify')->list_notification($this->user_id, $_GET['flag'], intval($_GET['page']) * intval($_GET['per_page']) . ', ' . intval($_GET['per_page']));
		
		if (empty($list) && $this->user_info['notification_unread'] != 0)
		{
			$this->model('account')->increase_user_statistics(account_class::NOTIFICATION_UNREAD, 0, $this->user_id);
		}

		TPL::assign('flag', $_GET['flag']);
		TPL::assign('list', $list);
		
		if ($_GET['template'] == 'header_list')
		{
			TPL::output("notifications/ajax/header_list");
		}
		else 
		{
			TPL::output("notifications/ajax/list");
		}
	}
	
	public function read_notification_action()
	{
		$notification_id = intval($_GET['notification_id']);
		$read_type = intval($_GET['read_type']);
		
		//单条阅读
		if ($read_type == 1)
		{
			$this->model('notify')->read_notification($notification_id);
		}
		//全部阅读
		else if ($read_type == 0)
		{
			$this->model('notify')->read_all();
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, "标记已读成功"));
	}

}