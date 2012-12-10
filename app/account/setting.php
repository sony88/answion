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

class setting extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	function setup()
	{
		$this->crumb('设置', '/account/setting/');
		
		TPL::import_css(array(
			'css/main.css'
		));
	}

	function index_action()
	{
		$this->profile_action();
	}

	function profile_action()
	{		
		if ($this->user_info['birthday'] != 0)
		{
			TPL::assign('birthday_y_s', date('Y', $this->user_info['birthday']));
			TPL::assign('birthday_m_s', date('m', $this->user_info['birthday']));
			TPL::assign('birthday_d_s', date('d', $this->user_info['birthday']));
		}
				
		for ($i = date('Y'); $i > 1900; $i --)
		{
			$years[$i] = $i;
		}
		
		TPL::assign('birthday_y', $years);
		
		// 月符值
		TPL::assign('birthday_m', array(
			0 => '', 
			1 => 1, 
			2 => 2, 
			3 => 3, 
			4 => 4, 
			5 => 5, 
			6 => 6, 
			7 => 7, 
			8 => 8, 
			9 => 9, 
			10 => 10, 
			11 => 11, 
			12 => 12
		));
		
		for ($tmp_i = 1; $tmp_i <= 31; $tmp_i ++)
		{
			$day_array[$tmp_i] = $tmp_i;
		}
		
		TPL::assign('birthday_d', $day_array);
		
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		
		TPL::assign('education_experience_list', $this->model('education')->get_education_experience_list($this->user_id));
		
		$jobs_list = $this->model('work')->get_jobs_list();
		
		if ($work_experience_list = $this->model('work')->get_work_experience_list($this->user_id))
		{
			foreach ($work_experience_list as $key => $val)
			{
				$work_experience_list[$key]['job_name'] = $jobs_list[$val['job_id']];
			}
		}
		
		TPL::assign('work_experience_list', $work_experience_list);
		
		$this->crumb('基本资料', '/account/setting/profile/');
		
		TPL::import_js(array(
			'js/ajaxupload.js', 
			'js/LocationSelect.js'
		));
		
		TPL::output('account/setting/profile');
	}
	
	function privacy_action()
	{
		TPL::assign('email_setting', $this->model('account')->get_email_setting_by_uid($this->user_id));
		TPL::assign('notification_setting', $this->model('account')->get_notification_setting_by_uid($this->user_id));
		TPL::assign('notify_actions', $this->model('notify')->notify_action_details);
		
		$this->crumb('隐私/提醒', '/account/setting/privacy');
		
		TPL::output('account/setting/privacy');
	}
		
	function openid_action()
	{
		$sina_weibo = $this->model('openid_weibo')->get_users_sina_by_uid($this->user_id);
		$qq_weibo = $this->model('openid_qq_weibo')->get_users_qq_by_uid($this->user_id);
		$qq = $this->model('openid_qq')->get_user_info_by_uid($this->user_id);
		
		TPL::assign('sina_weibo', $sina_weibo);
		TPL::assign('qq_weibo', $qq_weibo);
		TPL::assign('qq', $qq);
		
		$this->crumb('账号绑定', '/account/setting/openid/');
		
		TPL::output("account/setting/openid");
	}
	
	function integral_action()
	{
		$this->crumb('我的积分', '/account/setting/integral/');
		
		TPL::output("account/setting/integral");
	}
	
	function security_action()
	{
		$this->crumb('安全设置', '/account/setting/security/');
		
		TPL::output("account/setting/security");
	}
}
