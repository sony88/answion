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

class find_password extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['guest'] = array();
		$rule_action['user'] = array();
		
		return $rule_action;
	}

	function setup()
	{
		$this->crumb('找回密码', '/account/find_password/');
	}

	function index_action()
	{
		TPL::import_css('css/login.css');
		
		TPL::output('account/find_password/index');
	}
	
	function process_success_action()
	{
		$email = $_SESSION['find_password'];

		TPL::import_css('css/login.css');
		
		TPL::assign('email', $email);
		TPL::assign('common_email', H::get_common_email($email));
		
		TPL::output("account/find_password/process_success");
	}
	
	function modify_action()
	{
		$active_code = $_GET['key'];
		
		$active_code_row = $this->model('active')->get_active_code_row($active_code, 11);
		
		if (!$active_code_row)
		{
			H::redirect_msg('链接已失效，请返回首页', '/');
		}
		
		if ($active_code_row['active_time'] || $active_code_row['active_ip'] || $active_code_row['active_expire'])
		{
			H::redirect_msg('链接已失效，请返回首页', '/');
		}
		
		TPL::import_css('css/login.css');
		
		TPL::output("account/find_password/modify");
	}
}