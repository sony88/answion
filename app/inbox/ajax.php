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
	function setup()
	{
		HTTP::no_cache_header();
	}
	
	public function send_action()
	{
		if ($this->is_post())
		{
			$sender_uid = $this->user_id;
			$message = $_POST['message'];
			$recipient = $_POST['recipient'];
			
			if (trim($message) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', "请输入私信内容"));
			}
			
			if (!$recipient_user = $this->model('account')->get_user_info_by_username($recipient))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', "接收信息的用户不存在"));
			}
			else
			{
				$recipient_uid = $recipient_user['uid'];
			}
			
			if ($recipient_uid == $sender_uid)
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '不能给自己发私信'));
			}
			
			//判断是否设置为关注人发送信息
			if (! $this->model('message')->check_recv($recipient_user['uid'], $sender_uid))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '对方设置了只有 Ta 关注的人才能给 Ta 发送私信'));
			}
			
			if ($this->model('message')->send_message($sender_uid, $recipient_uid, null, $message, 0, 0))
			{
				if ($_POST['return_url'])
				{
					$rsm = array(
						'url' => get_js_url(strip_tags($_POST['return_url']))
					);
				}
				else
				{
					$rsm = array(
						'url' => get_js_url('/inbox/')
					);
				}
				
				if($_GET['type'] == 'clickEve')
				{
					H::ajax_json_output(AWS_APP::RSM($rsm, 1, '私信发送成功'));
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM($rsm, 1, null));
				}
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '私信发送失败, 请联系管理员'));
			}
		}
	}
}