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

class qq extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
	
	}

	function binding_weibo_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			H::redirect_msg('本站不允许绑定腾讯微博', '/');
		}
		
		$this->model('openid_qq_weibo')->init(get_js_url('/account/qq/callback_weibo/'));
	}

	function callback_weibo_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			H::redirect_msg('本站不允许绑定腾讯微博', '/');
		}
		
		Services_Tencent_OpenSDK_Tencent_Weibo::init(get_setting('qq_app_key'), get_setting('qq_app_secret'));
		
		if (Services_Tencent_OpenSDK_Tencent_Weibo::getAccessToken($_GET['oauth_verifier']) and $uinfo = Services_Tencent_OpenSDK_Tencent_Weibo::call('user/info'))
		{
			if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
			{
				$this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
			}

			$this->model('openid_qq_weibo')->bind_account($uinfo, get_js_url('/account/setting/openid/'), $this->user_id);
		}
		else
		{
			H::redirect_msg('与微博通信出错, 请重新登录.', '/account/setting/openid/');
		}
	}

	function del_bind_weibo_action()
	{
		$this->model('openid_qq_weibo')->del_users_by_uid($this->user_id);
		
		HTTP::redirect('/account/setting/openid/');
	}

	function binding_qq_action()
	{
		if (get_setting('qq_login_enabled') != 'Y')
		{
			H::redirect_msg('本站不允许绑定 QQ 帐号', '/');
		}
		
		$call_back = get_js_url('/account/qq/callback_qq/');
		
		HTTP::redirect($this->model('openid_qq')->get_code_url($call_back));
	}

	function callback_qq_action()
	{
		if (get_setting('qq_login_enabled') != 'Y')
		{
			H::redirect_msg('本站不允许绑定 QQ 帐号', '/');
		}
		
		$code = $_GET['code'];
		
		if (! $code)
		{
			H::redirect_msg('与QQ通信出错, 请重新登录.', "/account/login/");
		}
		
		if($_SESSION['qq_login_code'] != $code)
		{
			if (! $access_token = $this->model('openid_qq')->request_access_token($code, get_js_url('/account/openid/qq_login_callback/')))
			{
				H::redirect_msg('与QQ通信出错, 请重新登录.', "/account/login/");
			}
			
			$_SESSION['qq_login_code'] = $code;
			$_SESSION['qq_access_token'] = $access_token;
		}
		
		if ($_SESSION['qq_access_token'] && $uinfo = $this->model('openid_qq')->request_user_info_by_token($_SESSION['qq_access_token']))
		{
			if (!$this->model('integral')->fetch_log($this->user_id, 'BIND_OPENID'))
			{
				$this->model('integral')->process($this->user_id, 'BIND_OPENID', round((get_setting('integral_system_config_profile') * 0.2)), '绑定 OPEN ID');
			}
			
			$this->model('openid_qq')->bind_account($uinfo, get_js_url('/account/setting/openid/'), $this->user_id);
		}
		else
		{
			H::redirect_msg('与QQ通信出错, 请重新登录.', "/account/login/");
		}
	}
	
	function del_bind_qq_action()
	{
		$this->model('openid_qq')->del_user_by_uid($this->user_id);
		
		HTTP::redirect('/account/setting/openid/');
	}
}
	