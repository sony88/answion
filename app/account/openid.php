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

class openid extends AWS_CONTROLLER
{

	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
		HTTP::no_cache_header();
	}

	function index_action()
	{
		HTTP::redirect('/account/login/');
	}

	function sina_action()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		unset($_SESSION['sina_profile']);
		unset($_SESSION['sina_token']);
		
		$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));
		
		HTTP::redirect($oauth->getAuthorizeURL(get_js_url('/account/openid/sina_callback/')));
	}

	function sina_callback_action()
	{
		if (get_setting('sina_weibo_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		if ($this->is_post() and $_SESSION['sina_profile'] and ! $_SESSION['sina_token']['error'])
		{
			if (get_setting('invite_reg_only') == 'Y')
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "本站只能通过邀请注册"));
			}
			
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "请输入真实姓名"));
			}
			else if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "真实姓名已经存在"));
			}
			else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, $check_rs));
			}
			else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "真实姓名中包含敏感词或系统保留字"));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), - 1, "E-Mail 已经被使用, 或格式不正确"));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'userPassword'
				), - 1, "密码长度不符合规则"));
			}
			
			if (! $_POST['agreement_chk'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "请选择同意用户协议中的条款"));
			}
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
				
				if (is_array($result))
				{
					$uid = $result['user_info']['uid'];
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, $result));
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
			}
			
			if ($uid)
			{
				//$this->model('openid_weibo')->bind_account($_SESSION['sina_profile'], null, $uid, $_SESSION['sina_keys']['oauth_token'], $_SESSION['sina_keys']['oauth_token_secret'], true);
				$this->model('openid_weibo')->bind_account($_SESSION['sina_profile'], null, $uid, true);
				
				H::ajax_json_output(AWS_APP::RSM(null, 1, '微博绑定成功'));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, 1, "与微博通信出错 (Register), 请重新登录."));
			}
		}
		else
		{
			//if ($_GET['oauth_verifier'] AND ($_GET['oauth_token'] == $_SESSION['sina_keys']['oauth_token'] AND !$_SESSION['sina_keys']['last_key'] AND !$_SESSION['sina_profile']) OR $_SESSION['sina_keys']['last_key']['error'])
			if ($_GET['code'] and (! $_SESSION['sina_token'] or $_SESSION['sina_token']['error']))
			{
				//$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'), $_SESSION['sina_keys']['oauth_token'], $_SESSION['sina_keys']['oauth_token_secret']);
				//$_SESSION['sina_keys']['last_key'] = $oauth->getAccessToken($_GET['oauth_verifier']);
				

				$oauth = new Services_Weibo_WeiboOAuth(get_setting('sina_akey'), get_setting('sina_skey'));
				
				$_SESSION['sina_token'] = $oauth->getAccessToken('code', array(
					'code' => $_GET['code'], 
					'redirect_uri' => get_js_url('/account/openid/sina_callback/')
				));
				
				//$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $_SESSION['sina_keys']['last_key']['oauth_token'], $_SESSION['sina_keys']['last_key']['oauth_token_secret']);
				$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $_SESSION['sina_token']['access_token']);
				
				//$sina_profile = $client->verify_credentials();
				

				$uid_get = $client->get_uid();
				$sina_profile = $client->show_user_by_id($uid_get['uid']);
				
				if ($sina_profile['error'])
				{
					H::redirect_msg('与微博通信出错 (' . $sina_profile['error'] . '), 请重新登录.', "/account/login/");
				}
				
				$_SESSION['sina_profile'] = $sina_profile;
			}
			
			if (! $_SESSION['sina_profile'])
			{
				H::redirect_msg('与微博通信出错, 请重新登录.', "/account/login/");
			}
			
			if ($sina_user = $this->model('openid_weibo')->get_users_sina_by_id($_SESSION['sina_profile']['id']))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($sina_user['uid']);
				
				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
				
				$this->model('openid_weibo')->update_token($sina_user['id'], $_SESSION['sina_token']);
				
				unset($_SESSION['sina_profile']);
				unset($_SESSION['sina_token']);
				
				if (get_setting('ucenter_enabled') == 'Y')
				{
					HTTP::redirect('/account/sync_login/');
				}
				else
				{
					HTTP::redirect('/');
				}
			}
			else
			{
				if ($this->user_id)
				{
					$this->model('openid_weibo')->bind_account($_SESSION['sina_profile'], '/', $this->user_id);
				}
				else
				{
					if (get_setting('invite_reg_only') == 'Y')
					{
						H::redirect_msg('本站只能通过邀请注册', get_setting('base_url'));
					}
					else
					{
						$this->crumb('完善资料', '/account/login/');
						
						TPL::assign('user_name', $_SESSION['sina_profile']['screen_name']);
						
						TPL::import_css('css/login.css');
						
						TPL::output("account/openid/callback");
					}
				}
			}
		}
	}

	function qq_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		$this->model('openid_qq_weibo')->init(get_js_url('/account/openid/qq_callback/'), get_setting('qq_app_key'), get_setting('qq_app_secret'));
	}

	function qq_callback_action()
	{
		if (get_setting('qq_t_enabled') != 'Y')
		{
			HTTP::redirect('/account/login/');
		}
		
		Services_Tencent_OpenSDK_Tencent_Weibo::init(get_setting('qq_app_key'), get_setting('qq_app_secret'));
		
		if (! Services_Tencent_OpenSDK_Tencent_Weibo::getAccessToken($_GET['oauth_verifier']) or ! $uinfo = Services_Tencent_OpenSDK_Tencent_Weibo::call('user/info'))
		{
			H::redirect_msg('与微博通信出错, 请重新登录.', "/account/login/");
		}
		
		if ($_POST['email'])
		{
			if (get_setting('invite_reg_only') == 'Y')
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "本站只能通过邀请注册"));
			}
			
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "请输入真实姓名"));
			}
			else if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "真实姓名已经存在"));
			}
			else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, $check_rs));
			}
			else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "真实姓名中包含敏感词或系统保留字"));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), - 1, "E-Mail 已经被使用, 或格式不正确"));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'userPassword'
				), - 1, "密码长度不符合规则"));
			}
			
			if (! $_POST['agreement_chk'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "请选择同意用户协议中的条款"));
			}
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
				
				if (is_array($result))
				{
					$uid = $result['user_info']['uid'];
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, $result));
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
			}
			
			if ($uid)
			{			
				$this->model('openid_qq_weibo')->bind_account($uinfo, null, $uid, true);
				
				H::ajax_json_output(AWS_APP::RSM(null, 1, '微博绑定成功'));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", "与微博通信出错 (Register), 请重新登录."));
			}
		}
		else
		{
			if ($qq_user = $this->model('openid_qq_weibo')->get_users_qq_by_name($uinfo['data']['name']))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($qq_user['uid']);
				
				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
				
				$this->model('openid_qq_weibo')->update_token($qq_user['name'], $_SESSION[Services_Tencent_OpenSDK_Tencent_Weibo::ACCESS_TOKEN], $_SESSION[Services_Tencent_OpenSDK_Tencent_Weibo::OAUTH_TOKEN_SECRET]);
				
				if (get_setting('ucenter_enabled') == 'Y')
				{
					HTTP::redirect('/account/sync_login/');
				}
				else
				{
					HTTP::redirect('/');
				}
			}
			else
			{
				if ($this->user_id)
				{
					$this->model('openid_qq_weibo')->bind_account($uinfo, '/', $this->user_id);
				}
				else
				{
					if (get_setting('invite_reg_only') == 'Y')
					{
						H::redirect_msg('本站只能通过邀请注册', get_setting('base_url'));
					}
					else
					{
						$this->crumb('完善资料', '/account/login/');
						
						TPL::assign('user_name', $uinfo['data']['name']);
						
						TPL::import_css('css/login.css');
						
						TPL::output("account/openid/callback");
					}
				}
			}
		}
	}

	public function qq_login_action()
	{
		$call_back = get_js_url('/account/openid/qq_login_callback/');
		
		HTTP::redirect($this->model('openid_qq')->get_code_url($call_back));
	}

	public function qq_login_callback_action()
	{
		$code = $_GET['code'];
		
		if (! $code)
		{
			H::redirect_msg('与QQ通信出错, 请重新登录.', "/account/login/");
		}
		
		if($_SESSION['qq_login_code'] != $code)
		{
			if(!$access_token = $this->model('openid_qq')->request_access_token($code, get_js_url('/account/openid/qq_login_callback/')))
			{
				H::redirect_msg('与QQ通信出错, 请重新登录.', "/account/login/");
			}
			
			$_SESSION['qq_login_code'] = $code;
			$_SESSION['qq_access_token'] = $access_token;
		}
		
		if (! $_SESSION['qq_access_token'] or ! $uinfo = $this->model('openid_qq')->request_user_info_by_token($_SESSION['qq_access_token']))
		{
			H::redirect_msg('与QQ通信出错, 请重新登录.', "/account/login/");
		}
		
		if ($_POST['email'])
		{
			if (get_setting('invite_reg_only') == 'Y')
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "本站只能通过邀请注册"));
			}
			
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "请输入真实姓名"));
			}
			else if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "真实姓名已经存在"));
			}
			else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, $check_rs));
			}
			else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), - 1, "真实姓名中包含敏感词或系统保留字"));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), - 1, "E-Mail 已经被使用, 或格式不正确"));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'userPassword'
				), - 1, "密码长度不符合规则"));
			}
			
			if (! $_POST['agreement_chk'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "请选择同意用户协议中的条款"));
			}
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
				
				if (is_array($result))
				{
					$uid = $result['user_info']['uid'];
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, - 1, $result));
				}
			}
			else
			{
				$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true);
			}
			
			if ($uid)
			{
				$this->model('openid_qq')->bind_account($uinfo, null, $uid, true);
				
				H::ajax_json_output(AWS_APP::RSM(null, 1, '微博绑定成功'));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", "与QQ通信出错 (Register), 请重新登录."));
			}
		}
		else
		{
			if ($qq_user = $this->model('openid_qq')->get_user_info_by_open_id($uinfo['openid']))
			{
				$user_info = $this->model('account')->get_user_info_by_uid($qq_user['uid']);
				
				HTTP::set_cookie('_user_login', get_login_cookie_hash($user_info['user_name'], $user_info['password'], $user_info['salt'], $user_info['uid'], false));
				
				$this->model('openid_qq')->update_token($qq_user['name'], $_SESSION['qq_access_token']);
				
				if (get_setting('ucenter_enabled') == 'Y')
				{
					HTTP::redirect('/account/sync_login/');
				}
				else
				{
					HTTP::redirect('/');
				}
			}
			else
			{
				if ($this->user_id)
				{
					$this->model('openid_qq')->bind_account($uinfo, '/', $this->user_id);
				}
				else
				{
					if (get_setting('invite_reg_only') == 'Y')
					{
						H::redirect_msg('本站只能通过邀请注册', get_setting('base_url'));
					}
					else
					{
						$this->crumb('完善资料', '/account/login/');
						
						TPL::import_css('css/login.css');
						
						TPL::assign('user_name', $uinfo['nickname']);
						
						TPL::output("account/openid/callback");
					}
				}
			}
		}
	}
}