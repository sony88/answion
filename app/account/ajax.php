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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		$rule_action['actions'] = array(
			'check_username',
			'check_email',
			'register_process',
			'login_process',
			'register_agreement',
			'send_valid_mail',
			'valid_email_active',
			'request_find_password',
			'find_password_modify'
		);
		
		return $rule_action;
	}
	
	function setup()
	{
		HTTP::no_cache_header();
	}
	
	function check_username_action()
	{
		if ($this->model('account')->check_username_char($_GET['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '用户名不符合规则'));
		}
		
		if ($this->model('account')->check_username_sensitive_words($_GET['username']) || $this->model('account')->check_username($_GET['username']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '用户名已被注册'));
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '用户名可以使用'));
	}
	
	function check_email_action()
	{
		if (!$_GET['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '邮箱不能为空'));
		}
		
		if ($this->model('account')->check_email($_GET['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '邮箱已被使用'));
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '邮箱可以使用'));
	}
	
	function register_process_action()
	{
		if ($_POST['invite_question_id'])
		{
			$objetc_id = $_POST['invite_question_id'];
		}
		
		if (HTTP::get_cookie('fromuid'))
		{
			$fromuid = HTTP::get_cookie('fromuid');
		}
		
		$icode = trim($_POST['icode']);
		
		if (get_setting('invite_reg_only') == 'Y' && !$icode)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, "本站只能通过邀请注册"));
		}
		
		if ($icode)
		{
			$invitation = $this->model('invitation')->check_code_available($icode);
			
			if ($invitation && ($_POST['email'] == $invitation['invitation_email']))
			{
				$email_valid = true;
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'icode'
				), -1, "邀请码出错"));
			}	
		}
		else if ($fromuid > 0)
		{
			if (HTTP::get_cookie('fromemail'))
			{
				$fromemail_hash = H::decode_hash(base64_decode(HTTP::get_cookie('fromemail')));
				
				if ($fromemail_hash['email'] == $_POST['email'])
				{
					$email_valid = true;
				}
			}
		}
		
		//检查用户名
		if (trim($_POST['user_name']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'user_name'
			), -1, "请输入真实姓名"));
		}
		else if ($this->model('account')->check_username($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'user_name'
			), -1, "真实姓名已经存在"));
		}
		else if ($check_rs = $this->model('account')->check_username_char($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'user_name'
			), -1, '用户名输入有误'));
		}
		else if ($this->model('account')->check_username_sensitive_words($_POST['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'user_name'
			), -1, "真实姓名中包含敏感词或系统保留字"));
		}
		
		if ($this->model('account')->check_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'email'
			), -1, "E-Mail 已经被使用, 或格式不正确"));
		}
		
		if (strlen($_POST['password']) < 6 OR strlen($_POST['password']) > 16)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'userPassword'
			), -1, "密码长度不符合规则"));
		}
		
		if (! $_POST['agreement_chk'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, "请选择同意用户协议中的条款"));
		}
		
		// 检查验证码
		if (!($_POST['fromuid'] || $_POST['icode']) && (!core_captcha::validate($_POST['seccode_verify'], false)) && (get_setting('register_seccode') == 'Y'))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'seccode_verify'
			), -1, '请填写正确的验证码'));
		}
		
		core_captcha::clear();

		if ($fromuid > 0)
		{
			// 有来源的用户无邀请码
			$follow_users = $this->model('account')->get_user_info_by_uid($fromuid);
		}
		else
		{
			$follow_users = $this->model('invitation')->get_invitation_by_code($icode);
		}
		
		if ($follow_users)
		{
			$follow_uid = $follow_users['uid'];
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			$result = $this->model('ucenter')->register($_POST['user_name'], $_POST['password'], $_POST['email'], $email_valid);
			
			if (is_array($result))
			{				
				$uid = $result['user_info']['uid'];
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
			}
		}
		else
		{
			$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], $email_valid);
		}
		
		if ($uid)
		{
			$this->model("account")->setcookie_logout(); // 清除COOKIE
			$this->model("account")->setsession_logout(); // 清除session;
			
			// 发送邀请问答站内信
			if ($objetc_id and $follow_users)
			{
				$url = get_js_url('/question/' . $_POST['invite_question_id']);
					
				$title = $follow_users['user_name'] . ' 邀请你来回复问题';
				$content = $follow_users['user_name'] . "  邀请你来回复问题: " . $url . " \r\n\r\n 邀请你来回复问题期待您的回复";
				
				$this->model('message')->send_message($follow_uid, $uid, $title, $content, 0, 0);
			}
			
			// 互为关注
			if ($follow_uid)
			{
				$this->model('follow')->user_follow_add($uid, $follow_uid);
				$this->model('follow')->user_follow_add($follow_uid, $uid);
				
				$this->model('integral')->process($follow_uid, 'INVITE', get_setting('integral_system_config_invite'), '邀请注册: ' . $_POST['user_name'], $follow_uid);
			}

			if ($email_valid || (get_setting('register_email_reqire') == 'N'))	//邮箱已通过验证 或 不需要邮箱验证则进入网站
			{
				$user_info = $this->model('account')->get_user_info_by_uid($uid);
				
				$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);
						
				if ($icode)
				{
					$this->model('invitation')->invitation_code_active($icode, time(), fetch_ip(), $uid);
				}

				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/home/first_login-TRUE')
				), 1, null)); // 返回数据
			}
			else
			{
				$_SESSION['valid_email'] = $_POST['email'];
				
				$this->model('active')->new_valid_email($uid);
								
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/account/valid_email/')
				), 1, null));
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '用户注册失败, 请联系管理员')); //返回数据			
		}
	}
	
	function login_process_action()
	{
		$user_name = trim($_POST['user_name']);
		$password = $_POST['password'];
		
		if ($_GET['tips_id'])
		{
			$tips_id = $_GET['tips_id'];
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if (!$user_info = $this->model('ucenter')->login($user_name, $password))
			{
				$user_info = $this->model('account')->check_login($user_name, $password);
			}
		}
		else
		{
			$user_info = $this->model('account')->check_login($user_name, $password);
		}
		
		if (! $user_info)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => $tips_id
			), -1, '请输入正确的帐号或密码'));
		}
		else
		{			
			if ($user_info['forbidden'] == 1)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'tips_id' => $tips_id
				), -1, '抱歉, 你的账号已经被禁止登录'));
			}
			
			if (get_setting('site_close') == 'Y' && $user_info['group_id'] != 1)
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'tips_id' => $tips_id,
				), -1, get_setting('close_notice')));
			}
			
			// !注: 来路检测后面不能再放报错提示
			if (!valid_post_hash($_POST['post_hash']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'tips_id' => $tips_id,
				), -1, '表单来路不正确, 请刷新页面重试'));
			}

			if (!$user_info['valid_email'] && (get_setting('register_email_reqire') == 'Y'))
			{
				$_SESSION['valid_email'] = $user_info['email'];
				
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/account/valid_email/')
				), 1, null));
			}
			
			if ($_POST['net_auto_login'])
			{
				$expire = 60 * 60 * 24 * 360;
			}

			$this->model('account')->update_user_last_login($user_info['uid']);
			$this->model('account')->setcookie_logout();
			
			// 默认记住用户名
			HTTP::set_cookie('r_uname', $user_name, time() + 60 * 60 * 24 * 30);
			
			$this->model('account')->setcookie_login($user_info['uid'], $user_name, $password, $user_info['salt'], $expire);
			
			if ($user_info['is_first_login'] AND !$_GET['is_mobile'])
			{
				$url = get_js_url('/home/first_login-TRUE');
			}
			
			if ($_POST['return_url'] AND !strstr($_POST['return_url'], '/logout'))
			{
				$url = strip_tags($_POST['return_url']);
				
				if ($_GET['is_mobile'] AND !strstr($_POST['return_url'], '/mobile'))
				{
					unset($url);
				}
			}
			
			if (!$url AND $_GET['is_mobile'])
			{
				$url = get_js_url('/mobile/');
			}
			
			if (get_setting('ucenter_enabled') == 'Y')
			{
				$url = get_js_url('/account/sync_login/url-' . @base64_encode($url));
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
			), 1, null));
		}
	}
	
	public function register_agreement_action()
	{
		H::ajax_json_output(AWS_APP::RSM(null, 1, '<div style="width: 790px; height: 320px; overflow:auto;">' . nl2br(get_setting('register_agreement')) . '</div>'));
	}
	
	public function get_weibo_bind_status_action()
	{
		if (get_setting('sina_weibo_enabled') == 'Y')
		{
			if ($sina_weibo = $this->model("sina_weibo")->get_users_sina_by_uid($this->user_id))
			{
				$data['sina_weibo']['name'] = $sina_weibo['name'];
			}
		}
		
		if (get_setting('qq_t_enabled') == 'Y')
		{
			if ($qq_weibo = $this->model("qq_weibo")->get_users_qq_by_uid($this->user_id))
			{
				$data['qq_weibo']['name'] = $qq_weibo['name'];
			}
		}
		
		if (get_setting('qq_login_enabled') == 'Y')
		{
			if ($qq = $this->model("qq")->get_user_info_by_uid($this->user_id))
			{
				$data['qq']['name'] = $qq['nick'];
			}
		}
		
		$data['sina_weibo']['enabled'] = get_setting('sina_weibo_enabled');
		$data['qq_weibo']['enabled'] = get_setting('qq_t_enabled');
		$data['qq']['enabled'] = get_setting('qq_login_enabled');
		
		H::ajax_json_output(AWS_APP::RSM($data, 1, null));
	}

	function welcome_message_template_action()
	{
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		
		TPL::output('account/ajax/welcome_message_template');
	}
	
	function welcome_get_topics_action()
	{
		if ($topics_list = $this->model('topic')->get_topic_list(null, 8, false, 'rand()'))
		{
			foreach ($topics_list as $key => $topic)
			{
				$topics_list[$key]['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic['topic_id']);
			}
		}
		TPL::assign('topics_list', $topics_list);
		
		TPL::output('account/ajax/welcome_get_topics');
	}

	function welcome_get_users_action()
	{
		if ($welcome_recommend_users = trim(rtrim(get_setting('welcome_recommend_users'), ',')))
		{
			$welcome_recommend_users = explode(',', $welcome_recommend_users);
			
			$users_list = $this->model('account')->fetch_all('users', "user_name IN('" . implode("','", $welcome_recommend_users) . "')", 'RAND()', 6);
		}
		
		if(!$users_list)
		{
			$users_list = $this->model('account')->get_activity_random_users(6);
		}
		
		if ($users_list)
		{
			foreach ($users_list as $key => $val)
			{
				$users_list[$key]['follow_check'] = $this->model('follow')->user_follow_check($this->user_id, $val['uid']);
			}
		}
		
		TPL::assign('users_list', $users_list);
		
		TPL::output('account/ajax/welcome_get_users');
	}

	function clean_first_login_action()
	{
		$this->model('account')->clean_first_login($this->user_id);
		
		die('success');
	}

	/**
	 * 微博分享
	 */
	function openid_push_action()
	{
		if (! $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), '-1', '请先登录'));
		}
		
		if (! $_POST['push_message'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), '-1', '请输入分享内容'));
		}
		
		if (! $_POST['push_qqweibo'] and ! $_POST['push_sina'] and ! $_POST['push_qzone'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), '-1', '请选择要分享的微博'));
		}
		
		if ($_POST['push_qqweibo'] AND get_setting('qq_t_enabled') == 'Y')
		{
			if ($openid_info = $this->model('openid_qq_weibo')->get_users_qq_by_uid($this->user_id))
			{
				Services_Tencent_OpenSDK_Tencent_Weibo::init(get_setting('qq_app_key'), get_setting('qq_app_secret'));
				
				$result = Services_Tencent_OpenSDK_Tencent_Weibo::call('t/add', array(
					'content' => htmlspecialchars($_POST['push_message'])
				), 'POST');
				
				if ($result['errcode'] == -3180)
				{
					H::ajax_json_output(AWS_APP::RSM(array(
						'tips_id' => 'share_send'
					), '-1', "QQ 微博认证已过期, 请重新绑定"));
				}
				else if ($result['errcode'])
				{
					H::ajax_json_output(AWS_APP::RSM(array(
						'tips_id' => 'share_send'
					), '-1', "QQ 微博发送失败，错误代码: {$result['errcode']}, 信息：{$result['msg']}"));
				}
			}
		}
		
		if ($_POST['push_qzone'] AND get_setting('qq_login_enabled') == 'Y')
		{
 			if ($openid_info = $this->model('openid_qq')->get_user_info_by_uid($this->user_id))
			{
				$model_type = $_POST['model_type'];
				$target_id= $_POST['target_id'];


				switch ($_POST['model_type'])
				{
					case 'answer' :
						$a_info = $this->model('answer')->get_answer_by_id($_POST['target_id']);
						$q_info = $this->model('question')->get_question_info_by_id($a_info['question_id']);
						$link_title = $q_info['question_content'];
						$url = get_js_url('/question/' . $a_info['question_id'] . '?fromuid-' . $this->user_id . '__item_id-' . $_POST['target_id'] . '#answer_' . $_POST['target_id']);
						break;
				
					case 'question' :
						$q_info = $this->model('question')->get_question_info_by_id($_POST['target_id']);
						$link_title = $q_info['question_content'];
						$url = get_js_url('/question/' . $q_info['question_id'] . '?fromuid-' . $this->user_id);
						break;
				
					case 'topic' :
						$t_info = $this->model('topic')->get_topic_by_id($_POST['target_id']);
						$link_title = $t_info['topic_title'];
						$url = get_js_url('/topic/' . $t_info['topic_id'] . '?fromuid-' . $this->user_id);
						break;
				
					default :
				}
				
				$result = $this->model('openid_qq')->add_share($openid_info['access_token'], $openid_info['name'], $link_title, $url, null, htmlspecialchars($_POST['push_message']));
				
				if ($result['errcode'] == 100014)
				{
					H::ajax_json_output(AWS_APP::RSM(array(
						'tips_id' => 'share_send'
					), '-1', "QQ 登录认证已过期, 请重新登录"));
				}
				else if ($result['errcode'])
				{
					H::ajax_json_output(AWS_APP::RSM(array(
						'tips_id' => 'share_send'
					), '-1', "QQ 空间分享发送失败，错误代码: {$result['errcode']}, 信息：{$result['msg']}"));
				}
			}
		}
		
		if ($_POST['push_sina'] AND get_setting('sina_weibo_enabled') == 'Y')
		{
			if ($openid_info = $this->model('openid_weibo')->get_users_sina_by_uid($this->user_id))
			{
				//$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $openid_info['oauth_token'], $openid_info['oauth_token_secret']);
				$client = new Services_Weibo_WeiboClient(get_setting('sina_akey'), get_setting('sina_skey'), $openid_info['access_token']);
				
				$result = $client->update(htmlspecialchars($_POST['push_message'])); //发送微博
				
				if ($result['error_code'] == 21327)
				{
					H::ajax_json_output(AWS_APP::RSM(array(
						'tips_id' => 'share_send'
					), '-1', "新浪微博认证已过期, 请重新绑定."));
				}
				else if ($result['error_code'])
				{
					H::ajax_json_output(AWS_APP::RSM(array(
						'tips_id' => 'share_send'
					), '-1', "新浪微博发送失败，错误：{$result['error_code']}: {$result['error']}"));
				}
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '分享发送成功'));
	}
	
	public function delete_draft_action()
	{
		if (!$_POST['item_id'] OR !$_POST['type'])
		{
			die;
		}
		
		$this->model('draft')->delete_draft($_POST['item_id'], $_POST['type'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '草稿删除成功'));
	}
	
	public function save_draft_action()
	{
		if (!$_GET['item_id'] OR !$_GET['type'] OR !$_POST)
		{
			die;
		}
		
		$this->model('draft')->save_draft($_GET['item_id'], $_GET['type'], $this->user_id, $_POST);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '已保存草稿，' . date('Y-m-d H:i:s', time())));
	}
	
	function send_valid_mail_action()
	{
		if (!$this->user_id)
		{
			if ( H::valid_email($_SESSION['valid_email']))
			{
				$this->user_info = $this->model('account')->get_user_info_by_email($_SESSION['valid_email']);
				$this->user_id = $this->user_info['uid'];
			}
		}
		
		if (! H::valid_email($this->user_info['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '没有设置 E-mail'));
		}
		
		if ($this->user_info['valid_email'] == 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "邮箱已经认证"));
		}
		
		if ($this->model('active')->new_valid_email($this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, '邮件发送成功'));
		}
	}
	
	function valid_email_active_action()
	{
		if (!$active_data = $this->model('active')->get_active_code_row($_POST['active_code'], 21))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/'),
			), 1, '激活失败, 链接无效'));
		}
		
		if ($active_data['active_time'] || $active_data['active_ip'] || $active_data['active_expire'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
			), 1, '帐户已激活, 请返回登录'));
		}

		if (!$user_info = $this->model('account')->get_user_info_by_uid($active_data['uid']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
			), -1, '激活失败, 链接已无效'));
		}
		
		if ($user_info['valid_email'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
			), 1, '帐户已通过邮箱验证，请返回登录'));
		}
		
		if ($user_id = $this->model('active')->active_code_active($_POST['active_code'], 21))
		{
			if ($_SESSION['valid_email'])
			{
				unset($_SESSION['valid_email']);
			}
			
			$this->model('account')->update_users_fields(array(
				'valid_email' => 1,
			), $active_data['uid']);
			
			if ($user_info['group_id'] == 3)
			{
				$this->model('account')->update_users_fields(array(
					'group_id' => 4,
				), $active_data['uid']);
			}
			
			//帐户激活成功，切换为登录状态跳转至首页
			$this->model('account')->setsession_logout();
			$this->model('account')->setcookie_logout();
			
			$this->model('account')->update_user_last_login($user_info['uid']);
				
			HTTP::set_cookie('r_uname', $user_info['email'], time() + 60 * 60 * 24 * 30);
				
			$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $user_info['password'], $user_info['salt'], null, false);
			
			$this->model('account')->welcome_message($user_info['uid'], $user_info['user_name'], $user_info['email']);
			
			$url = $user_info['is_first_login'] ? '/first_login-TRUE' : '/';
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url($url)
			), 1, null));
		}
	}
	
	function request_find_password_action()
	{
		if (!H::valid_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => "email"
			), "-1", "请填写正确的邮箱地址"));
		}		

		if (!$user_info = $this->model('account')->get_user_info_by_email($_POST['email']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => "email"
			), "-1", "邮箱地址错误或帐号不存在。"));
		}
		
		if (!core_captcha::validate($_POST['seccode_verify'], false))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => "seccode_verify"
			), "-1", "请填写正确的验证码"));
		}
		
		core_captcha::clear();
		
		$this->model('active')->new_find_password($user_info['uid']);
		
		$_SESSION['find_password'] = $_POST['email'];
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/account/find_password/process_success/')
		), 1, null));
	}
	
	function find_password_modify_action()
	{
		if (!core_captcha::validate($_POST['seccode_verify'], false))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => "seccode_verify"
			), "-1", "请填写正确的验证码"));
		}
		
		core_captcha::clear();
		
		$active_data = $this->model('active')->get_active_code_row($_POST['active_code'], 11);
		
		if ($active_data)
		{
			if ($active_data['active_time'] || $active_data['active_ip'] || $active_data['active_expire'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, 1, "链接已失效，请重新找回密码"));
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, "链接已失效，请重新找回密码"));
		}
		
		if (empty($_POST['password']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => "password"
			), "-1", "密码不能为空"));
		}

		//检查验证码
		if ($_POST['password'] != $_POST['re_password'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => "password"
			), "-1", "两次输入的密码不一致"));
		}
		
		if (! $uid = $this->model('active')->active_code_active($_POST['active_code'], 11))
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "找回密码 Key 已失败")); //返回数据
		}
		
		$user_info = $this->model('account')->get_user_info_by_uid($uid);
		
		if ($this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $uid, $user_info['salt']))
		{
			//若账户未激活，则激活账户
			$this->model('account')->update_users_fields(array(
				'valid_email' => 1
			), $uid);
			
			if ($user_info['group_id'] == 3)
			{
				$this->model('account')->update_users_fields(array(
					'group_id' => 4,
				), $active_data['uid']);
			}
			
			$this->model("account")->setcookie_logout(); //清除COOKIE
			
			$this->model("account")->setsession_logout(); //清除session;
			
			$_SESSION['error'] = 0; //重置登录出错计数
			
			unset($_SESSION['find_password']);

			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
			), 1, "密码修改成功, 请返回登录"));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "修改失败, 请联系管理员"));
		}
	}
	
	function avatar_upload_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, '', 1),
			'is_image' => TRUE,
			'max_size' => (get_setting('upload_avatar_size_limit') * 1024),
			'file_name' => $this->model('account')->get_avatar($this->user_id, '', 2),
			'encrypt_name' => FALSE
		))->do_upload('user_avatar');
		
		if (AWS_APP::upload()->get_error())
		{
			switch (AWS_APP::upload()->get_error())
			{
				default:
					H::ajax_json_output(AWS_APP::RSM(null, '-1', '错误代码: ' . AWS_APP::upload()->get_error()));
				break;
				
				case 'upload_invalid_filetype':
					H::ajax_json_output(AWS_APP::RSM(null, '-1', '文件类型无效'));
				break;	
				
				case 'upload_invalid_filesize':
					H::ajax_json_output(AWS_APP::RSM(null, '-1', '文件尺寸过大, 最大允许尺寸为 ' . get_setting('upload_size_limit') .  ' KB'));
				break;
			}
		}
		
		if (! $upload_data = AWS_APP::upload()->data())
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '上传失败, 请与管理员联系'));
		}
		
		if ($upload_data['is_image'] == 1)
		{
			foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
			{			
				$thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($this->user_id, $key, 2);
				
				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();	
			}
		}
		
		$update_data['avatar_file'] = $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['min']);
		
		//更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);
		
		if (!$this->model('integral')->fetch_log($this->user_id, 'UPLOAD_AVATAR'))
		{
			$this->model('integral')->process($this->user_id, 'UPLOAD_AVATAR', round((get_setting('integral_system_config_profile') * 0.2)), '上传头像');
		}
		
		H::ajax_json_output(AWS_APP::RSM( array(
			'preview' => get_setting('upload_url') . '/avatar/' . $this->model('account')->get_avatar($this->user_id, null, 1) . basename($thumb_file['max'])
		), 1, null));
	}
	
	function add_edu_action()
	{
		$school_name = htmlspecialchars(trim($_POST['school_name']));
		$education_years = intval($_POST['education_years']);
		$departments = htmlspecialchars($_POST['departments']);
		
		if (empty($_POST['school_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'school_name'
			), '-1', "请输入学校名称"));
		}
		
		if (empty($_POST['departments']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'departments'
			), '-1', "请输入院系"));
		}
		
		if ($_POST['education_years'] == "请选择" OR !$_POST['education_years'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'education_years'
			), '-1', "请选择入学年份"));
		}
		
		$edu_id = $this->model('education')->add_education_experience($this->user_id, $school_name, $education_years, $departments);
		
		if (!$this->model('integral')->fetch_log($this->user_id, 'UPDATE_EDU'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_EDU', round((get_setting('integral_system_config_profile') * 0.2)), '完善教育经历');
		}	
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'id' => $edu_id
		), 1, null));
	
	}
	
	function remove_edu_action()
	{
		$this->model('education')->del_education_experience($_POST['id'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	
	}
	
	function add_work_action()
	{
		$job_id = intval($_POST['job_id']);
		$company_name = htmlspecialchars($_POST['company_name']);
		
		$start_year = intval($_POST['start_year']);
		$end_year = intval($_POST['end_year']);
		
		if (!$company_name)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'company_name'
			), '-1', "请输入公司名称"));
		}
		
		if (!$job_id)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'job_id'
			), '-1', "请选择职位"));
		}
		
		if (!$start_year OR !$end_year )
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'start_year'
			), '-1', "请选择工作时间"));
		}
		
		$work_id = $this->model('work')->add_work_experience($this->user_id, $start_year, $end_year, $company_name, $job_id);
		
		if (!$this->model('integral')->fetch_log($this->user_id, 'UPDATE_WORK'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_WORK', round((get_setting('integral_system_config_profile') * 0.2)), '完善工作经历');
		}	
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'id' => $work_id
		), 1, null));
	}
	
	function remove_work_action()
	{
		$this->model('work')->del_work_experience($_POST['id'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	//修改教育经历 
	function edit_edu_action()
	{
		if (empty($_POST['school_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'school_name'
			), '-1', "请输入学校名称"));
		}
		
		if (empty($_POST['departments']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'departments'
			), '-1', "请输入院系"));
		}
		
		if (!$_POST['education_years'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'education_years'
			), '-1', "请选择入学年份"));
		}
		
		$update_data['school_name'] = htmlspecialchars($_POST['school_name']);
		$update_data['education_years'] = intval($_POST['education_years']);
		$update_data['departments'] = htmlspecialchars($_POST['departments']);		
		
		$this->model('education')->update_education_experience($update_data, $_GET['id'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	//修改工作经历 
	function edit_work_action()
	{
		if (!$_POST['company_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'company_name'
			), '-1', "请输入公司名称"));
		}
		
		if (!$_POST['job_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'job_id'
			), '-1', "请选择职位"));
		}
		
		if (!$_POST['start_year'] OR !$_POST['end_year'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'start_year'
			), '-1', "请选择工作时间"));
		}

		$update_data['job_id'] = intval($_POST['job_id']);
		$update_data['company_name'] = htmlspecialchars($_POST['company_name']);
		
		$update_data['start_year'] = intval($_POST['start_year']);
		$update_data['end_year'] =  intval($_POST['end_year']);
		
		$this->model('work')->update_work_experience($update_data, $_GET['id'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	function privacy_setting_action()
	{
		$update_data['inbox_recv'] = intval($_POST['inbox_recv']);
		
		//更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);
		
		$update_dataay['sender_11'] = intval($_POST['sender_11']);
		$update_dataay['sender_12'] = intval($_POST['sender_12']);
		$update_dataay['sender_13'] = intval($_POST['sender_13']);
		$update_dataay['sender_14'] = intval($_POST['sender_14']);
		$update_dataay['sender_15'] = intval($_POST['sender_15']);
		
		if ($notify_actions = $this->model('notify')->notify_action_details)
		{
			foreach ($notify_actions as $key => $val)
			{
				if (! isset($_POST['notification_setting'][$key]) && $val['user_setting'])
				{
					$nt_data[] = $key;
				}
			}
		}
		$update_nt_array = array(
			'data' => serialize($nt_data)
		);
		
		$this->model('account')->update_notification_setting_fields($update_nt_array, $this->user_id);
		$this->model('account')->update_email_setting_fields($update_dataay, $this->user_id);
		
		$this->model('account')->update_users_fields(array(
			'weibo_visit' => intval($_POST['weibo_visit'])
		), $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '设置已保存'));
	}
	
	function profile_setting_action()
	{
		if (!$this->user_info['user_name'] OR $this->user_info['user_name'] == $this->user_info['email'])
		{
			$update_data['user_name'] = htmlspecialchars(trim($_POST['user_name']));
			
			if ($check_result = $this->model('account')->check_username_char($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'user_name'
				), '-1', $check_result));
			}
		}
		
		if ($_POST['url_token'] AND $_POST['url_token'] != $this->user_info['url_token'])
		{
			if ($this->user_info['url_token_update'] AND $this->user_info['url_token_update'] > (time() - 3600 * 24 * 30))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'url_token'
				), '-1', '你距离上次修改个性网址未满 30 天'));
			}
			
			if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'url_token'
				), '-1', '个性网址只允许输入英文或数字'));
			}
			
			if ($this->model('account')->check_url_token($_POST['url_token'], $this->user_id))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'url_token'
				), '-1', '个性网址已经被占用请更换一个'));
			}
			
			if (preg_match("/^[\d]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'url_token'
				), '-1', '个性网址不允许为纯数字'));
			}
			
			$this->model('account')->update_url_token($_POST['url_token'], $this->user_id);
		}
		
		if ($update_data['user_name'] and $this->model('account')->check_username($update_data['user_name']) and $this->user_info['user_name'] != $update_data['user_name'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'user_name'
			), '-1', '已经存在相同的姓名, 请重新填写'));
		}
		
		if (! H::valid_email($this->user_info['email']))
		{
			if (! H::valid_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), '-1', '请输入正确的 E-Mail 地址'));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'email'
				), '-1', '邮箱已经存在, 请使用新的邮箱'));
			}
			
			$update_data['email'] = $_POST['email'];
		
			$this->model('active')->new_valid_email($this->user_id, $_POST['email']);
		}
		
		if($_POST['common_email'])
		{
			if (! H::valid_email($_POST['common_email']))
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'input' => 'common_email'
				), '-1', '请输入正确的常用邮箱地址'));
			}
			
			$update_data['common_email'] = $_POST['common_email'];
		}
		
		$update_data['sex'] = intval($_POST['sex']);
		
		$update_data['province'] = htmlspecialchars($_POST['province']);
		$update_data['city'] = htmlspecialchars($_POST['city']);
		$update_data['job_id'] = intval($_POST['job_id']);
		
		if ($_POST['birthday_y'])
		{
			$update_data['birthday'] = intval(strtotime(intval($_POST['birthday_y']) . '-' . intval($_POST['birthday_m']) . '-' . intval($_POST['birthday_d'])));
		}
		
		if (!$this->user_info['verified'])
		{
			$update_attrib_data['signature'] = htmlspecialchars($_POST['signature']);
		}
		
		if ($_POST['signature'] AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_SIGNATURE'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_SIGNATURE', round((get_setting('integral_system_config_profile') * 0.1)), '完善一句话介绍');
		}
		
		$update_attrib_data['qq'] = htmlspecialchars($_POST['qq']);
		$update_attrib_data['homepage'] = htmlspecialchars($_POST['homepage']);		
		$update_data['mobile'] = htmlspecialchars($_POST['mobile']);
		
		if (($update_attrib_data['qq'] OR $update_attrib_data['homepage'] OR $update_data['mobile']) AND !$this->model('integral')->fetch_log($this->user_id, 'UPDATE_CONTACT'))
		{
			$this->model('integral')->process($this->user_id, 'UPDATE_CONTACT', round((get_setting('integral_system_config_profile') * 0.1)), '完善联系资料');
		}

		//更新主表
		$this->model('account')->update_users_fields($update_data, $this->user_id);
		
		//更新从表
		$this->model('account')->update_users_attrib_fields($update_attrib_data, $this->user_id);
		
		$this->model('account')->set_default_timezone($_POST['default_timezone'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '个人资料已更新'));
	}
	
	function modify_password_action()
	{		
		$old_password = $_POST['old_password'];
		$password = $_POST['password'];
		$re_password = $_POST['re_password'];
		
		if (!$old_password)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'old_password'
			), '-1', '请输入当前密码'));
		}
		
		if ($password != $re_password)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'password'
			), '-1', '请输入相同的确认密码'));
		}
		
		if (strlen($password) < 6 OR strlen($password) > 16)
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'password'
			), -1, "密码长度不符合规则"));
		}
		
		if (get_setting('ucenter_enabled') == 'Y')
		{
			if ($this->model('ucenter')->is_uc_user($this->user_info['email']))
			{
				$result = $this->model('ucenter')->user_edit($this->user_id, $this->user_info['user_name'], $old_password, $password);
				
				if ($result !== 1)
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, $result));
				}
			}
		}
		
		if ($this->model('account')->update_user_password($old_password, $password, $this->user_id, $this->user_info['salt']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, '密码修改成功, 请牢记新密码'));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'old_password'
			), '-1', '请输入正确的当前密码'));
		}
	}
	
	public function areas_json_data_action()
	{
		echo file_get_contents(ROOT_PATH . 'static/js/areas.js');
	}
	
	public function integral_log_action()
	{
		$log = $this->model('integral')->fetch_all('integral_log', 'uid = ' . intval($this->user_id), 'time DESC', (intval($_GET['page']) * 50) . ', 50');
		
		foreach ($log AS $key => $val)
		{
			$parse_items[$val['id']] = array(
				'item_id' => $val['item_id'],
				'action' => $val['action']
			);
		}
		
		TPL::assign('log', $log);
		TPL::assign('log_detail', $this->model('integral')->parse_log_item($parse_items));
		
		TPL::output('account/ajax/integral_log');
	}
}
