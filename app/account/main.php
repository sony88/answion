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
		HTTP::no_cache_header();
	}
	
	public function index_action()
	{
		HTTP::redirect('/account/setting/');
	}
	
	public function captcha_action()
	{
		$core_captcha = load_class('core_captcha');
		
		$core_captcha->setCode(array(
			'characters' => 'A-H,J-X,3-9', 
			'length' => 4, 
			'deflect' => true, 
			'multicolor' => false
		));
		
		$core_captcha->setMolestation(array(
			'type' => 'both', 
			'density' => 'fewness'
		));
		
		$core_captcha->setImage(array(
			'type' => 'png', 
			'width' => 120, 
			'height' => 40
		));
		
		$config_font = array(
			'space' => 5,
			'size' => 28,
			'left' => rand(5, 15), 
			'top' => rand(25, 35), 
		);
		
		$dir_handle = opendir(AWS_PATH . 'core/fonts/');
		
		while (($file = readdir($dir_handle)) !== false)
		{
		    if ($file != '.' AND $file != '..')
		    {
		    	if (strstr(strtolower($file), '.ttf'))
		    	{
		    		$config_font['file'][] = AWS_PATH . 'core/fonts/' . $file;
		    	}
		   	}
		 }
		 
		closedir($dir_handle);
		
		$core_captcha->setFont($config_font);
		
		$core_captcha->setBgColor(array(
			'r' => rand(230, 255), 
			'g' => rand(230, 255), 
			'b' => rand(230, 255)
		));
		
		$core_captcha->paint();
	}
	
	function logout_action($return_url = null)
	{
		$this->model('online')->logout();	// 在线列表退出
		$this->model('account')->setcookie_logout();	// 清除 COOKIE
		$this->model('account')->setsession_logout();	// 清除 Session
		
		if (admin_session_class::get_admin_uid())
		{
			$this->model('account')->admin_logout();
		}
		
		if ($_GET['return_url'])
		{
			$url = strip_tags(urldecode($_GET['return_url']));
		}
		else if (! $return_url)
		{
			$url = '/';
		}
		else
		{
			$url = $return_url;
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if ($uc_uid = $this->model('ucenter')->is_uc_user($this->user_info['email']))
			{
				$sync_code = $this->model('ucenter')->sync_logout($uc_uid);
			}
			
			H::redirect_msg('您已退出站点, 现在将以游客身份进入站点, 请稍候...' . $sync_code, $url);
		}
		else
		{
			HTTP::redirect($url);
		}
	}

	function login_action()
	{
		$url = base64_decode($_GET['url']);
		
		if ($this->user_id)
		{
			if ($url)
			{
				header('Location: ' . $url); 
			}
			else
			{
				HTTP::redirect('/');
			}
		}
		
		$this->crumb('用户登录', '/account/login/');
		
		TPL::import_css('css/login.css');
		
		// md5 password...
		if (get_setting('ucenter_enabled') != 'Y')
		{
			TPL::import_js('js/md5.js');
		}
		
		TPL::assign('r_uname', HTTP::get_cookie('r_uname'));
		
		TPL::assign('return_url', strip_tags($_SERVER['HTTP_REFERER']));
		
		TPL::output("account/login");
	}
	
	function register_action()
	{
		if ($this->user_id && $_GET['invite_question_id'])
		{
			$invite_question_id = intval($_GET['invite_question_id']);
			
			if ($invite_question_id)
			{
				HTTP::redirect('/question/' . $invite_question_id);
			}
		}
		
		if (! $this->user_id)
		{
			if ($_GET['fromuid'])
			{
				HTTP::set_cookie('fromuid', $_GET['fromuid']);
			}
			
			if ($_GET['fromemail'])
			{
				HTTP::set_cookie('fromemail', $_GET['fromemail']);
			}
		}

		$icode = trim($_GET['icode']);
		
		if (get_setting('invite_reg_only') == 'Y' && !$icode)
		{
			H::redirect_msg('本站只能通过邀请注册', '/');
		}
		
		if ($icode)
		{
			//检验失败
			if ($this->model('invitation')->check_code_available($icode))
			{
				TPL::assign('icode', $icode);
			}
			else
			{
				H::redirect_msg('邀请码无效或已经使用，请使用新的邀请码', '/');
			}
		}
		
		$this->crumb('注册', '/account/register/');

		TPL::import_css('css/login.css');
		
		TPL::output("account/register");
	}
	
	public function sync_login_action()
	{
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if ($uc_uid = $this->model('ucenter')->is_uc_user($this->user_info['email']))
			{
				$sync_code = $this->model('ucenter')->sync_login($uc_uid);
			}
		}
		
		if ($_GET['url'])
		{
			$url = base64_decode($_GET['url']);
		}
		
		if ((strstr($url, '://') AND !strstr($url, get_setting('base_url'))) OR !$url)
		{
			$url = '/';
		}
		
		H::redirect_msg('欢迎回来: ' . $this->user_info['user_name'] . ', 正在带您进入站点...' . $sync_code, $url);
	}
	
	function valid_email_action()
	{
		$email = $_SESSION['valid_email'];
		
		if (!$email)
		{
			HTTP::redirect('/');
		}
		
		//判断邮箱是否已验证
		$users = $this->model('account')->get_user_info_by_email($email);
		
		if (!$users)
		{
			HTTP::redirect('/');
		}
		
		if ($users['valid_email'])
		{
			H::redirect_msg('邮箱已通过验证，请重新登录', '/account/login/');
		}
		
		$common_email = H::get_common_email($email);

		TPL::assign('email', $email);
		
		TPL::assign('common_email', $common_email);
		
		$this->crumb('验证您的帐号邮箱', '/account/valid_email/');

		TPL::import_css('css/login.css');
		
		TPL::output("account/valid_email");
	}
	
	function valid_email_active_action()
	{
		$active_code = $_GET['key'];
		
		$active_code_row = $this->model('active')->get_active_code_row($active_code, 21);
		
		if (!$active_code_row || ($active_code_row['active_expire'] == '1'))
		{
			H::redirect_msg('链接已失效，请使用最新的验证邮件。', '/');
		}
		
		if ($active_code_row['active_time'] || $active_code_row['active_ip'] || $active_code_row['active_expire'])
		{
			H::redirect_msg('邮箱已通过验证，请返回登录', '/account/login/');
		}
		
		$users = $this->model('account')->get_user_info_by_uid($active_code_row['uid']);
		
		if ($users['valid_email'])
		{
			H::redirect_msg('帐户已激活, 请返回登录', '/account/login/');
		}
		
		$this->crumb('帐户激活');
		
		TPL::assign('active_code', $active_code);
		
		TPL::assign('email', $users['email']);

		TPL::import_css('css/login.css');
		
		TPL::import_js('js/register.js');
		
		TPL::output('account/valid_email_active');
	}
}