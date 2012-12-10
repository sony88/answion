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

class openid_qq_weibo_class extends AWS_MODEL
{
	function update_token($name, $access_token, $oauth_token_secret)
	{
		return $this->update('users_qq', array(
			'access_token' => $this->quote($access_token), 
			'oauth_token_secret' => $this->quote($oauth_token_secret)
		), "type = 'weibo' AND name = '" . $this->quote($name) . "'");
	}

	function get_users_qq_by_name($name)
	{
		return $this->fetch_row('users_qq', "type = 'weibo' AND name = '" . $this->quote($name) . "'");
	}

	function get_users_qq_by_uid($uid)
	{
		return $this->fetch_row('users_qq', "type = 'weibo' AND uid = " . intval($uid));
	}

	function del_users_by_uid($uid)
	{
		return $this->delete('users_qq', "type = 'weibo' AND uid = " . intval($uid));
	}

	function users_qq_add($uid, $name, $nick, $location, $gender)
	{
		$data['type'] = 'weibo';
		$data['uid'] = intval($uid);
		$data['name'] = htmlspecialchars($name);
		$data['nick'] = htmlspecialchars($nick);
		$data['location'] = htmlspecialchars($location);
		$data['gender'] = htmlspecialchars($gender);
		$data['add_time'] = time();
		
		return $this->insert('users_qq', $data);
	}

	function bind_account($uinfo, $redirect, $uid, $is_ajax = false)
	{
		if ($openid_info = $this->get_users_qq_by_uid($uid))
		{
			if ($openid_info['name'] != $uinfo['data']['name'])
			{
				if ($is_ajax)
				{
					H::ajax_json_output(AWS_APP::RSM(null, "-1", 'QQ 微博账号已经被其他账号绑定'));
				}
				else
				{
					H::redirect_msg('QQ 微博账号已经被其他账号绑定', '/account/logout/');
				}
			}
		}
		
		if (! $users_qq = $this->get_users_qq_by_name($uinfo['data']['name']))
		{
			$users_qq = $this->users_qq_add($uid, $uinfo['data']['name'], $uinfo['data']['nick'], $uinfo['data']['location'], $uinfo['data']['sex']);
		}
		else if ($users_qq['uid'] != $uid)
		{
			if ($is_ajax)
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", 'QQ 微博账号已经被其他账号绑定'));
			}
			else
			{
				H::redirect_msg('QQ 微博账号已经被其他账号绑定', '/account/setting/openid/');
			}
		}
		
		$this->update_token($uinfo['data']['name'], $_SESSION[Services_Tencent_OpenSDK_Tencent_Weibo::ACCESS_TOKEN], $_SESSION[Services_Tencent_OpenSDK_Tencent_Weibo::OAUTH_TOKEN_SECRET]);
		
		if ($redirect)
		{
			HTTP::redirect($redirect);
		}
	}

	function init($callback)
	{
		Services_Tencent_OpenSDK_Tencent_Weibo::init(get_setting('qq_app_key'), get_setting('qq_app_secret'));
		
		$request_token = Services_Tencent_OpenSDK_Tencent_Weibo::getRequestToken($callback);
		
		HTTP::redirect(Services_Tencent_OpenSDK_Tencent_Weibo::getAuthorizeURL($request_token));
	}
}
	