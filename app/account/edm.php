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

class edm extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		
		return $rule_action;
	}
	
	public function setup()
	{
		HTTP::no_cache_header();
	}

	public function mail_action()
	{
		if ($task = $this->model('edm')->get_task_info($_GET['id']))
		{
			echo str_replace('[EMAIL]', 'email@address.com', $task['message']);
		}
		else
		{
			H::redirect_msg('您所访问的资源不存在');
		}
	}
	
	public function ping_action()
	{
		$param = explode('|', $_GET['id']);
		
		if (md5(base64_decode($param[0]) . G_SECUKEY) == $param[1] AND $param[2])
		{
			$this->model('edm')->set_task_view($param[2], base64_decode($param[0]));
			
			echo 'Success';
		}
	}
}