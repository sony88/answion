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
	public $per_page = 10;

	public function get_access_rule()
	{
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		$rule_action['guest'] = array();
		$rule_action['user'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
		$this->crumb('私信', '/inbox');
		
		TPL::import_css('css/main.css');
	}

	/**
	 * 
	 * 列出用户短信息
	 */
	public function index_action()
	{
		$list = $this->model('message')->list_message($_GET['page'], $this->per_page, $this->user_id);
		
		$list_total_rows = $this->model('message')->found_rows();
				
		if ($list['user_list'])
		{
			if ($users_info_query = $this->model('account')->get_user_info_by_uids($list['user_list']))
			{
				foreach ($users_info_query as $user)
				{
					$users_info[$user['uid']] = $user;
				}
			}
		}
		
		if ($list['diag_ids'])
		{
			$last_message = $this->model('message')->get_last_messages($list['diag_ids']);
		}
				
		if ($list['content_list'])
		{
			$data = array();
			
			foreach ($list['content_list'] as $key => $value)
			{
				if (($value['sender_uid'] == $this->user_id) && ($value['sender_count'] > 0)) //当前处于发送用户
				{
					$tmp['user_name'] = $users_info[$value['recipient_uid']]['user_name'];
					$tmp['url_token'] = $users_info[$value['recipient_uid']]['url_token'];
					
					$tmp['unread'] = $value['sender_unread'];
					$tmp['count'] = $value['sender_count'];
					$tmp['uid'] = $value['recipient_uid'];
				}
				else if (($value['recipient_uid'] == $this->user_id) && ($value['recipient_count'] > 0)) ////当前处于接收用户
				{
					$tmp['user_name'] = $users_info[$value['sender_uid']]['user_name'];
					$tmp['url_token'] = $users_info[$value['sender_uid']]['url_token'];
					
					$tmp['unread'] = $value['recipient_unread'];
					$tmp['count'] = $value['recipient_count'];
					
					$tmp['uid'] = $value['sender_uid'];
				}
				
				$tmp['last_message'] = $last_message[$value['dialog_id']];
				
				$tmp['last_time'] = $value['last_time'];
				$tmp['dialog_id'] = $value['dialog_id'];
				
				$data[] = $tmp;
			}
		}
		
		TPL::assign('list', $data);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/inbox/'), 
			'total_rows' => $list_total_rows,
			'per_page' => $this->per_page
		))->create_links());
		
		TPL::assign('list', $data);
		
		TPL::output("inbox/index");
	}
	
	public function delete_dialog_action()
	{
		$dialog_id = intval($_GET['dialog_id']);
		
		if ($dialog_id == 0)
		{
			die;
		}
		
		$list = $this->model('message')->delete_dialog($dialog_id);
		
		if ($_SERVER['HTTP_REFERER'])
		{
			HTTP::redirect($_SERVER['HTTP_REFERER']);
		}
		else
		{
			HTTP::redirect('/inbox/');
		}
	}

	public function delete_message_action()
	{
		$msg_id = intval($_GET['recipient_id']);
		
		if ($msg_id == 0)
		{
			die;
		}
		
		$list = $this->model('message')->delete_message($msg_id);
		
		if ($_SERVER['HTTP_REFERER'])
		{
			HTTP::redirect($_SERVER['HTTP_REFERER']);
		}
		else
		{
			HTTP::redirect('/inbox/');
		}
	}

	public function read_message_action()
	{
		$dialog_id = intval($_GET['dialog_id']);
		
		$data = array();
		$tmp = array();
		
		if ($dialog_id == 0)
		{
			H::redirect_msg('指定的站内信不存在', '/inbox/');
		}
		
		$this->model('message')->read_message($dialog_id);
		
		$list = $this->model('message')->get_message_by_dialog_id($dialog_id, calc_page_limit($_GET['page'], $this->per_page));
		
		if (empty($list['list_one']))
		{
			HTTP::redirect("/inbox/");
		}
		
		if (! empty($list))
		{
			if ($list['list_one'][0]['sender_uid'] != $this->user_id)
			{
				$recipient_user = $this->model('account')->get_user_info_by_uid($list['list_one'][0]['sender_uid']);
			}
			else
			{
				$recipient_user = $this->model('account')->get_user_info_by_uid($list['list_one'][0]['recipient_uid']);
			}
			
			if ($list['list'])
			{
				foreach ($list['list'] as $key => $value)
				{
					$value['notice_content'] = FORMAT::parse_links($value['notice_content']);
					$value['user_name'] = $recipient_user['user_name'];
					$value['url_token'] = $recipient_user['url_token'];
					
					$list_data[] = $value;
				}
			}
		}
		
		$this->crumb('私信对话: ' . $recipient_user['user_name'], '/inbox/read_message/dialog_id-' . $dialog_id);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url('/inbox/read_message/dialog_id-' . $dialog_id), 
			'total_rows' => $this->model('message')->count_message($dialog_id), 
			'per_page' => $this->per_page
		))->create_links());
		
		TPL::assign('list', $list_data);
		TPL::assign('recipient_user', $recipient_user);
		TPL::output("inbox/read_message");
	}
}
