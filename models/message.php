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

class message_class extends AWS_MODEL
{

	/**
	 * 用户发送短信息类
	 * @param $sender_uid	发送用户ID
	 * @param $recipient_uid	接收用户ID
	 * @param $notice_title	短信息标题
	 * @param $notice_content	短信息内容
	 * @param $notice_type		短信息类型，0-普通消息10-系统发的消息，不能回复11-系统通知
	 * @param $sender_del		发送者删除，默认0
	 * @param $recipient_del	接收者删除，默认0
	 */
	public function send_message($sender_uid, $recipient_uid, $notice_title, $notice_content, $notice_type = 0, $sender_del = 0, $recipient_del = 0)
	{
		if (empty($sender_uid) || empty($recipient_uid) || empty($notice_content))
		{
			return false;
		}
		
		$diag_info = $this->get_dialog_id($sender_uid, $recipient_uid);
		
		//会话不存在则创建
		if (! $diag_info)
		{
			$diag_id = $this->create_dialog($sender_uid, $recipient_uid);
		}
		else
		{
			$diag_id = $diag_info['dialog_id'];
			
			if ($diag_info['sender_uid'] == $sender_uid)
			{
				$this->add_dialog_unread($diag_id, "recipient_unread");
			}
			else if ($diag_info['recipient_uid'] == $sender_uid)
			{
				$this->add_dialog_unread($diag_id, "sender_unread");
			}
		}
		
		$mesg_id = $this->add_notice($diag_id, $notice_title, $notice_content, $sender_uid, $notice_type);
		
		if (! $mesg_id)
		{
			return false;
		}
		
		//增加附加表数据
		$this->add_recipient_data($mesg_id, $diag_id, $sender_uid, $recipient_uid);
		
		//更新用户未读通知汇总数据
		$this->model('account')->increase_user_statistics(account_class::NOTICE_UNREAD, 1, $recipient_uid);
		
		$userinfo = $this->model('account')->get_user_info_by_uid($sender_uid);
		
		$this->model('email')->action_email(email_class::TYPE_NEW_MESSAGE, $recipient_uid, get_js_url('/inbox/'), array(
			'user_name' => $userinfo['user_name'],
		));
		
		return $mesg_id;
	}

	/**
	 * 插入数据到消息表
	 * @param $diag_id
	 * @param $notice_title
	 * @param $notice_content
	 * @param $sender_uid
	 * @param $notice_type
	 */
	public function add_notice($diag_id, $notice_title, $notice_content, $sender_uid, $notice_type)
	{
		$data = array(
			"dialog_id" => $diag_id,  //会话ID
			"notice_title" => htmlspecialchars($notice_title), 
			"notice_content" => htmlspecialchars($notice_content), 
			"add_time" => time(),  //添加时间
			"sender_uid" => $sender_uid,  //最后短消息ID
			"notice_type" => $notice_type
		);
		
		return $this->insert('notice', $data);
	}

	/**
	 * 插入数据到附加表
	 * @param $mesg_id
	 * @param $diag_id
	 * @param $sender_uid
	 * @param $recipient_uid
	 */
	public function add_recipient_data($mesg_id, $diag_id, $sender_uid, $recipient_uid)
	{
		$data = array(
			"dialog_id" => $diag_id, 
			"notice_id" => $mesg_id, 
			"sender_uid" => $sender_uid, 
			"sender_time" => time(), 
			"sender_del" => 0, 
			"recipient_uid" => $recipient_uid, 
			"recipient_time" => 0, 
			"recipient_del" => 0
		);
		
		return $this->insert('notice_recipient', $data);
	}

	/**
	 * 创建对话
	 */
	public function create_dialog($sender_uid, $recipient_uid)
	{
		$data = array(
			"sender_uid" => $sender_uid,  //发送者UID
			"sender_unread" => 0,  //发送者未读
			"recipient_uid" => $recipient_uid,  //接收者UID
			"recipient_unread" => 1,  //接收者未读
			"add_time" => time(),  //添加时间
			"last_time" => time(),  //最后更新时间
			"last_notice_id" => 0,  //最后短消息ID
			"sender_count" => 1,  //发送者显示对话条数
			"recipient_count" => 1,  //接收者显示对话条数
			"all_count" => 1 //总对话条数
		);
		
		return $this->insert('notice_dialog', $data);
	}

	/**
	 * 更新对话表未读数
	 * Enter description here ...
	 * @param $diag_id
	 * @param $filed
	 */
	public function add_dialog_unread($diag_id, $filed)
	{
		if (! in_array($filed, array('sender_unread','recipient_unread')))
		{
			return false;
		}
		
		$data = array(
			'last_time' => time(), 
			'sender_count' => "sender_count + 1", 
			'recipient_count' => "recipient_count + 1", 
			'all_count' => "all_count + 1", 
			$filed => $filed . " + 1"
		);
		
		if ($data)
		{
			foreach ($data as $key => $val)
			{
				$update_arr[] = "`{$key}` = {$val}";
			}
		}
		
		return $this->query("UPDATE " . $this->get_table('notice_dialog') . " SET " . implode(',', $update_arr) . " WHERE dialog_id = " . intval($diag_id));
	}

	/**
	 * 标示已读对话
	 * Enter description here ...
	 * @param $dialog_id
	 */
	public function read_message($dialog_id)
	{
		$uid = USER::get_client_uid();
		
		$message_unread = $this->get_message_unread_num($uid, $dialog_id);
		
		if ($message_unread)
		{
			//更新短信息列表
			$this->update('notice_dialog', array('recipient_unread' => 0), "recipient_uid = " . $uid . " AND dialog_id = " . $dialog_id);
			
			$this->update('notice_dialog', array('sender_unread' => 0), 'sender_uid = ' . $uid . ' AND dialog_id = ' . $dialog_id);
			
			//更新接收表时间
			$this->update('notice_recipient', array('recipient_time' => time()), 'recipient_uid = ' . $uid . ' AND dialog_id = ' . $dialog_id);
			
			//更新用户未读通知汇总数据
			$this->model('account')->increase_user_statistics(account_class::NOTICE_UNREAD, 0, $uid);
		}
		
		return true;
	}

	/**
	 * 
	 * 阅读短信息
	 * @param int $dialog_id 信息id
	 * @param int $cur_page 页码
	 * @param int $page_size 页面条数
	 * 
	 * @return array信息内容数组
	 */
	public function get_message_by_dialog_id($dialog_id, $limit = "0,10")
	{
		$uid = USER::get_client_uid();
		
		$sql = "SELECT a.*, b.recipient_id, b.sender_uid, b.recipient_uid, b.sender_del, b.recipient_del FROM " . $this->get_table('notice') . " as a LEFT JOIN " . $this->get_table('notice_recipient') . " as b ON a.notice_id = b.notice_id";
		
		$sql .= " WHERE a.dialog_id = " . intval($dialog_id) . " AND ((b.sender_uid=" . $uid . " AND b.sender_del = 0) OR (b.recipient_uid = " . $uid . " AND b.recipient_del = 0)) ORDER BY notice_id DESC LIMIT " . $limit;
		
		$rs['list'] = $this->query_all($sql);
		
		$rs['list_one'] = $this->query_all("SELECT * FROM " . $this->get_table('notice_dialog') . " WHERE dialog_id = " . intval($dialog_id));
		
		return $rs;
	}

	public function count_message($dialog_id)
	{
		$sql = "SELECT COUNT(*) as count FROM " . $this->get_table('notice') . " as a LEFT JOIN " . $this->get_table('notice_recipient') . " as b ON a.notice_id = b.notice_id WHERE a.dialog_id = " . intval($dialog_id);
		
		$rs = $this->query_row($sql);
		
		return $rs['count'];
	}

	/**
	 * 获得未读短消息条数
	 * Enter description here ...
	 */
	public function get_message_unread_num($uid, $dialog = 0)
	{
		if ($uid == 0)
		{
			$uid = USER::get_client_uid();
		}
		
		$where = "recipient_uid = " . intval($uid) . " AND recipient_time = 0 AND recipient_del = 0";
		
		if ($dialog > 0)
		{
			$where .= " AND dialog_id = " . intval($dialog);
		}
		
		return $this->count('notice_recipient', $where);
	}

	/**
	 * 
	 * 删除对话信息
	 * @param int $dialog_id
	 * 
	 * @return boolean true|false
	 */
	public function delete_dialog($dialog_id)
	{
		$uid = USER::get_client_uid();
		
		$dialog_id = intval($dialog_id);
		
		$this->read_message($dialog_id);
		
		//更新短信息表
		$this->update('notice_dialog', array('sender_count' => 0), 'sender_uid = ' . $uid . ' AND dialog_id = ' . $dialog_id);
		
		$this->update('notice_dialog', array('recipient_count' => 0), 'recipient_uid = ' . $uid . ' AND dialog_id = ' . $dialog_id);
		
		//更新接收表
		$this->update('notice_recipient', array('recipient_del' => 1), 'recipient_uid = ' . $uid . ' AND dialog_id = ' . $dialog_id);
		
		$this->update('notice_recipient', array('sender_del' => 1), 'sender_uid = ' . $uid . ' AND dialog_id = ' . $dialog_id);
		
		return true;
	}

	/**
	 * 
	 * 删除短信息 
	 * @param int/array $recipient_id 数组为批量删除
	 * 
	 * @return boolean true|false
	 */
	public function delete_message($recipient_id)
	{
		$uid = USER::get_client_uid();
		$recipient_id = intval($recipient_id);
		$retval = false;
		
		$mes_info = $this->fetch_all('notice_recipient', "recipient_id = " . $recipient_id);
		
		if (! empty($mes_info))
		{
			$mes_info = $mes_info[0];
			
			if ($mes_info['sender_uid'] == $uid)
			{
				$data = array(
					"sender_del" => 1
				);
				
				$retval = $this->update('notice_recipient', $data, "sender_uid=" . $uid . " AND recipient_id=" . $mes_info['recipient_id']);
			}
			else if ($mes_info['recipient_uid'] == $uid)
			{
				$data = array(
					"recipient_del" => 1
				);
				
				$retval = $this->update('notice_recipient', $data, "recipient_uid = " . $uid . " AND recipient_id = " . $mes_info['recipient_id']);
			}
			
			//更新短信息列表
			$sql = " UPDATE " . $this->get_table('notice_dialog') . " SET sender_count = sender_count - 1 ";
			$sql .= " WHERE sender_uid = " . $uid . " AND dialog_id = " . $mes_info['dialog_id'];
			$this->query($sql);
			
			//更新短信息列表
			$sql = " UPDATE " . $this->get_table('notice_dialog') . " SET recipient_count = recipient_count - 1 ";
			$sql .= " WHERE recipient_uid = " . $uid . " AND dialog_id = " . $mes_info['dialog_id'];
			
			$this->query($sql);
		}
		
		return $retval;
	}

	/**
	 * 
	 * 列出用户相关短信息
	 * @param $cur_page 页码
	 * @param $page_size 页面条数
	 * 
	 * @return array
	 */
	public function list_message($page = 1, $limit = 10, $uid = null)
	{
		if ($list_msg = $this->fetch_page('notice_dialog', '(sender_uid = ' . intval($uid) . ' AND sender_count > 0) OR (recipient_uid = ' . intval($uid) . ' AND recipient_count > 0)', 'last_time DESC', $page, $limit))
		{
			$diag_ids = array();
			$send_ids = array();
			
			foreach ($list_msg as $recordset)
			{
				$diag_ids[] = $recordset['dialog_id'];

				if ($uid == $recordset['recipient_uid'])
				{
					$send_ids[] = $recordset['sender_uid'];
				}
				else
				{
					$send_ids[] = $recordset['recipient_uid'];
				}
			}
		}
		
		$result['diag_ids'] = $diag_ids;
		$result['content_list'] = $list_msg;
		$result['user_list'] = $send_ids;
		
		return $result;
	}
	
	public function get_last_messages($diag_ids)
	{
		if (!is_array($diag_ids))
		{
			return false;
		}
		
		foreach ($diag_ids as $diag_id)
		{
			$dialog_message = $this->fetch_row('notice', 'dialog_id = ' . intval($diag_id), 'notice_id DESC');
				
			$last_message[$diag_id] = cjk_substr($dialog_message['notice_content'], 0, 60, 'UTF-8', '...');
		}
		
		return $last_message;
	}

	/**
	 * 得到用户关注列表
	 * @param int $uid
	 * 
	 * @return array
	 */
	public function check_send_message($uid)
	{
		$data = array();
		
		if ($user_focus = $this->model('follow')->get_user_friends($uid))
		{
			foreach ($user_focus as $user_info)
			{
				$data[] = $user_info['uid'];
			}
		}
		
		return $data;
	}

	/**
	 * 
	 * 判断是否存在用户对话,包括接受者或者发送者,得到对话ID
	 * 
	 * @return int 不存在用户，返回新建用户对话ID  存在直接返回用户对话ID
	 */
	public function get_dialog_id($u_id, $s_id)
	{
		return $this->fetch_row('notice_dialog', "(`sender_uid` = " . intval($u_id) . " AND `recipient_uid` = " . intval($s_id) . ")  OR (`recipient_uid` = " . intval($u_id) . " AND `sender_uid` = " . intval($s_id) . ")");
	}

	/**
	 * 
	 * 判断用户是否设置了关注人才能接收
	 */
	public function check_recv($uid, $sender_uid)
	{
		$recipicent_info = $this->model('account')->get_user_info_by_uid($uid);
		
		if ($recipicent_info['inbox_recv'] == 1)
		{
			$user_list = $this->model('message')->check_send_message($uid);
			
			if (! in_array($sender_uid, $user_list))
			{
				return false;
			}
		}
		
		return true;
	}
}