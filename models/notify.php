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

class notify_class extends AWS_MODEL
{
	//=========模型类别:model_type===================================================
	

	const CATEGORY_QUESTION = 1; //问题
	const CATEGORY_PEOPLE = 4; //人物
	const CATEGORY_CONTEXT = 7; //文字
	

	//=========操作标示:action_type==================================================
	

	const TYPE_PEOPLE_FOCUS = 101; //被人关注
	const TYPE_NEW_ANSWER = 102; //关注的问题增加了新回复
	const TYPE_COMMENT_AT_ME = 103; //有评论@提到我
	const TYPE_INVITE_QUESTION = 104; //被人邀请问题问题
	const TYPE_ANSWER_COMMENT = 105; //我的回复被评论
	const TYPE_QUESTION_COMMENT = 106; //我的问题被评论
	const TYPE_ANSWER_AGREE = 107; //我的回复收到赞同
	const TYPE_ANSWER_THANK = 108; //我的回复收到感谢
	const TYPE_MOD_QUESTION = 110; //我发布的问题被编辑
	const TYPE_REMOVE_ANSWER = 111; //我发表的回复被删除
	//const TYPE_REMOVE_QUESTION = 112;	//我发布的问题被删除
	const TYPE_REDIRECT_QUESTION = 113; //我发布的问题被重定向
	const TYPE_QUESTION_THANK = 114; 		//我发布的问题收到感谢
	const TYPE_CONTEXT = 100; //纯文本通知
	

	//===============================================================================
	

	public $user_id;
	public $notify_actions = array();
	public $notify_action_details;

	public function setup()
	{
		$this->user_id = USER::get_client_uid();
		
		if ($this->notify_action_details = AWS_APP::config()->get('notification')->action_details)
		{
			foreach ($this->notify_action_details as $key => $val)
			{
				$this->notify_actions[] = $key;
			}
		}
	}

	/**
	 * 发送通知
	 * @param $action_type	操作类型，使用notify_class调用TYPE
	 * @param $uid			接收用户id
	 * @param $data		附加数据
	 * @param $model_type	可选，合并类别，使用notify_class调用CATEGORY
	 * @param $source_id	可选，合并子ID
	 */
	public function send($sender_uid, $recipient_uid, $action_type, $model_type = 0, $source_id = 0, $data = array())
	{		
		$recipient_uid = intval($recipient_uid);
		
		if (!$recipient_uid)
		{
			return false;
		}
		
		if (! in_array($action_type, $this->notify_actions) && ($action_type > 0))
		{
			return false;
		}
		
		/*if ($recipient_uid == $this->user_id)
		{
			return false;
		}*/
		
		if (! $this->check_notification_setting($recipient_uid, $action_type))
		{
			return false;
		}
		
		$notify_data = array(
			'sender_uid' => $sender_uid, 
			'recipient_uid' => $recipient_uid, 
			'action_type' => intval($action_type), 
			'model_type' => intval($model_type), 
			'source_id' => intval($source_id), 
			'add_time' => time(), 
			'read_flag' => 0
		);
		
		if ($notification_id = $this->insert('notification', $notify_data))
		{
			$this->insert('notification_data', array(
				'notification_id' => $notification_id,
				'data' => serialize($data)
			));
			
			$this->model("account")->increase_user_statistics(account_class::NOTIFICATION_UNREAD, 0, $recipient_uid);
			
			return $notification_id;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 获得通知列表
	 * read_status 0-未读 1-已读 other-所有
	 */
	public function list_notification($recipient_uid, $read_status = 0, $limit = '0, 20')
	{		
		if (!$notify_ids = $this->get_notification_list(false, $recipient_uid, $read_status, $limit))
		{
			return array();
		}
		
		if (!$notify_list = $this->get_notification_by_ids($notify_ids))
		{
			return array();
		}
		
		$uids = array();
		
		$list = array();
		
		$question_ids = array();
		
		if ($unread_notifys = $this->get_unread_notification($recipient_uid))
		{
			$unread_extends = array();
		
			$unique_people = array();
			
			foreach ($unread_notifys as $key => $val)
			{
				if ($val['model_type'] == self::CATEGORY_QUESTION)
				{
					if (isset($unique_people[$val['source_id']][$val['action_type']][$val['data']['from_uid']]))
					{
						continue;
					}
					
					$unread_extends[$val['model_type']][$val['source_id']][] = $val;
					
					$action_type = $val['action_type'];
					
					if (in_array($val['action_type'], array(
						self::TYPE_COMMENT_AT_ME, 
						self::TYPE_QUESTION_COMMENT
					)))
					{
						$action_type = self::TYPE_ANSWER_COMMENT;
					}
					
					if ($val['action_type'] == self::TYPE_QUESTION_THANK)
					{
						$action_type = self::TYPE_ANSWER_THANK;
					}
					
					$action_ex_details[$val['source_id']][$action_type][] = $val;
					
					$uids[] = $val['data']['from_uid'];
					
					$unique_people[$val['source_id']][$val['action_type']][$val['data']['from_uid']] = 1;
				}
			}
		}
		
		foreach ($notify_list as $key => $val)
		{
			if ($val['data']['question_id'])
			{
				$question_ids[] = $val['data']['question_id'];
			}
			
			if (intval($val['data']['from_uid']))
			{
				$uids[] = intval($val['data']['from_uid']);
			}
			
			if ($read_status == 0 && (count($unread_extends[$val['model_type']][$val['source_id']]) > 1) and ($this->notify_action_details[$val['action_type']]['combine'] == 1))
			{
				$notify_list[$key]['extends'] = $unread_extends[$val['model_type']][$val['source_id']];
				$notify_list[$key]['extend_details'] = $action_ex_details[$val['source_id']];
			}
		}
		
		if ($question_ids)
		{
			$question_list = $this->model('question')->get_question_info_by_ids($question_ids);
		}
		
		if ($uids)
		{
			$user_infos = $this->model('account')->get_user_info_by_uids($uids);
		}
		
		foreach ($notify_list as $key => $notify)
		{
			$data = $notify['data'];
			
			if (empty($data))
			{
				continue;
			}
			
			$tmp = array();
			
			$tmp['notification_id'] = $notify['notification_id'];
			$tmp['model_type'] = $notify['model_type'];
			$tmp['action_type'] = $notify['action_type'];
			$tmp['read_flag'] = $notify['read_flag'];
			$tmp['add_time'] = $notify['add_time'];
			$tmp['anonymous'] = $data['anonymous'];
			
			if ($data['from_uid'])
			{
				$userinfo = $user_infos[$data['from_uid']];
				$tmp['p_username'] = $userinfo['user_name'];
				$tmp['p_url'] = get_js_url('people/' . $userinfo['url_token']);
			}
			
			$token = "notification_id-" . $notify['notification_id'];
			
			switch ($notify['model_type'])
			{
				case self::CATEGORY_QUESTION :
					
					switch ($notify['action_type'])
					{
						default :
							if (empty($question_list[$data['question_id']]))
							{
								unset($tmp);
								continue;
							}
							
							$tmp['title'] = $question_list[$data['question_id']]['question_content'];
							
							$rf = false;
							
							$querys = array();
							
							$querys[] = $token;
							
							if ($notify['extends'])
							{
								$tmp['extend_count'] = count($notify['extends']);

								$answer_ids = array();
								
								$comment_type = array();
								
								foreach ($notify['extends'] as $ex_key => $ex_notify)
								{
									if ($ex_notify['action_type'] == 104)
									{
										$from_uid = $ex_notify['data']['from_uid'];
									}
									
									if (($ex_notify['action_type'] == 106) || ($ex_notify['action_type'] == 103 && $ex_notify['data']['comment_type'] == 1))
									{
										$comment_type[] = '1';
									}
									
									if (($ex_notify['action_type'] == 105) || ($ex_notify['action_type'] == 103 && $ex_notify['data']['comment_type'] == 2))
									{
										$comment_type[] = '2';
									}
									
									if ($ex_notify['data']['item_id'] > 0)
									{
										$answer_ids[] = $ex_notify['data']['item_id'];
									}
									
									if ($ex_notify['action_type'] == 113)
									{
										$rf = true;
									}
								}
								
								if (! $rf)
								{
									$querys[] = 'rf-false';
								}
								
								if ($from_uid)
								{
									$querys[] = 'source-' . base64_encode($from_uid);
								}
								
								if ($comment_type)
								{
									if (count(array_unique($comment_type)) == 1)
									{
										$querys[] = 'comment-' . array_pop($comment_type);
									}
									else if (count(array_unique($comment_type)) == 2)
									{
										$querys[] = 'comment-all';
									}
								}
								
								if ($answer_ids)
								{
									$answer_ids = array_unique($answer_ids);
									
									asort($answer_ids);
									
									$querys[] = 'item_id-' . implode(',', $answer_ids) . '#!answer_' . array_pop($answer_ids);
								}
								
								$tmp['extend_details'] = $this->format_extend_detail($notify['extend_details'], $user_infos);
							}
							else
							{
								if ($notify['action_type'] != 113)
								{
									$querys[] = 'rf-false';
								}
								
								if ($notify['action_type'] == 110)
								{
									$querys[] = 'column-log';
								}
								
								if ($notify['action_type'] == 104)
								{
									$querys[] = 'source-' . base64_encode($data['from_uid']);
								}
								
								if (($notify['action_type'] == 106) || ($notify['action_type'] == 103 && $data['comment_type'] == 1))
								{
									$querys[] = 'comment-1';
								}
								
								if (($notify['action_type'] == 105) || ($notify['action_type'] == 103 && $data['comment_type'] == 2))
								{
									$querys[] = 'comment-2';
								}
								
								if ($data['item_id'])
								{
									$querys[] = 'item_id-' . $data['item_id'] . '#!answer_' . $data['item_id'];
								}
							}
							
							$tmp['key_url'] = 'question/' . $data['question_id'] . '?' . implode('__', $querys);
							
							break;
					}
					
					break;
				
				case self::CATEGORY_PEOPLE :
					
					if (empty($userinfo))
					{
						unset($tmp);
						continue;
					}
					
					$tmp['key_url'] = $tmp['p_url'] . '?' . $token;
					
					break;
				
				case self::CATEGORY_CONTEXT :
					
					$tmp['content'] = $data['content'];
					
					break;
			}
			
			if ($tmp)
			{
				$list[] = $tmp;
			}
			else
			{
				$this->delete_notify($notify['notification_id']);
			}
		}
		
		return $list;
	}

	function format_extend_detail($extends, $user_infos)
	{
		if (empty($extends) || ! is_array($extends))
		{
			return $extends;
		}
		
		$ex_details = array();
		
		foreach ($extends as $action_type => $val)
		{
			$tmp = array();
			
			$answer_ids = array();
			$comment_type = array();
			$action_users = array();
			
			foreach ($val as $action)
			{
				$notification_id = $action['notification_id'];
				
				$uid = intval($action['data']['from_uid']);
				
				if ($uid)
				{
					$action_users[$uid][] = $action;
				}
			}
			
			$tmp['count'] = count($val);
			
			foreach ($action_users as $uid => $action)
			{
				$querys = array();
				
				$rf = false;
				
				$show_log = false;
				
				$notification_ids = array();
				
				foreach ($action as $ex_notify)
				{
					$notification_ids[] = $ex_notify['notification_id'];
					
					if (($ex_notify['action_type'] == 106) || ($ex_notify['action_type'] == 103 && $ex_notify['data']['comment_type'] == 1))
					{
						$comment_type[] = '1';
					}
					
					if (($ex_notify['action_type'] == 105) || ($ex_notify['action_type'] == 103 && $ex_notify['data']['comment_type'] == 2))
					{
						$comment_type[] = '2';
					}
					
					if ($ex_notify['data']['item_id'] > 0)
					{
						$answer_ids[] = $ex_notify['data']['item_id'];
					}
					
					if ($ex_notify['action_type'] == 113)
					{
						$rf = true;
					}
					
					if ($ex_notify['action_type'] == 110)
					{
						$show_log = true;
					}
					
					if ($ex_notify['data']['anonymous'])
					{
						$anonymous = true;
					}
				}
				
				if (! $rf)
				{
					$querys[] = 'rf-false';
				}
				
				$querys[] = 'notification_id-' . implode(',', $notification_ids);
				
				$querys[] = 'ori-1';
				
				if ($show_log)
				{
					$querys[] = 'column-log';
				}
				
				if ($comment_type)
				{
					if (count(array_unique($comment_type)) == 1)
					{
						$querys[] = 'comment-' . array_pop($comment_type);
					}
					else if (count(array_unique($comment_type)) == 2)
					{
						$querys[] = 'comment-all';
					}
				}
				
				if ($answer_ids)
				{
					$answer_ids = array_unique($answer_ids);
					
					asort($answer_ids);
					
					$querys[] = 'item_id-' . implode(',', $answer_ids) . '#!answer_' . array_pop($answer_ids);
				}
				
				$tmp['users'][$uid] = array(
					'username' => $anonymous ? '匿名用户' : $user_infos[$uid]['user_name'], 
					'url' => 'question/' . $val[0]['data']['question_id'] . '?' . implode('__', $querys)
				);
			}
			
			$ex_details[$action_type] = $tmp;
		}
		
		return $ex_details;
	}

	/**
	 * 检查指定用户的通知设置
	 */
	public function check_notification_setting($recipient_uid, $action_type)
	{
		if (! in_array($action_type, $this->notify_actions))
		{
			return false;
		}
		
		$notification_setting = $this->model('account')->get_notification_setting_by_uid($recipient_uid);
		
		//默认不认置则全部都发送
		if (empty($notification_setting['data']))
		{
			return true;
		}
		
		if (in_array($action_type, $notification_setting['data']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * 
	 * 阅读段信息
	 * @param int $notification_id 信息id
	 * 
	 * @return array信息内容数组
	 */
	public function read_notification($notification_id, $only_read_id = false)
	{
		$notification_ids = explode(',', $notification_id);
		
		if (! $only_read_id && count($notification_ids) == 1 && intval($notification_id) > 0)
		{
			$notify_info = $this->get_notification_by_ids($notification_id, $this->user_id);
			
			$unread_notifys = $this->get_unread_notification($this->user_id);
			
			if (!$notify_info || !$unread_notifys)
			{
				return false;
			}
			
			$unread_extends = array();
			
			foreach ($unread_notifys as $key => $val)
			{
				$unread_extends[$val['model_type']][$val['source_id']][] = $val;
			}
			
			$notifications = $unread_extends[$notify_info['model_type']][$notify_info['source_id']];
			
			$notification_ids = array();
			
			if (empty($notifications))
			{
				$notification_ids[] = $notification_id;
			}
			else
			{
				foreach ($notifications as $key => $val)
				{
					$notification_ids[] = $val['notification_id'];
				}
			}
		}
		
		if ($notification_ids)
		{
			foreach($notification_ids as $key => $val)
			{
				if (!is_numeric($val))
				{
					return false;
				}
				
				$notification_ids[$key] = intval($val);
			}
			
			$this->query("UPDATE " . get_table('notification') . " SET read_flag = 1 WHERE recipient_uid = " . $this->user_id . " AND notification_id IN (" . $this->quote((implode(",", array_unique($notification_ids)))) . ")");
			
			$this->model('account')->increase_user_statistics(account_class::NOTIFICATION_UNREAD, 0, $this->user_id);
			
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 全部已读
	 */
	public function read_all()
	{
		$this->query("UPDATE " . get_table('notification') . " SET read_flag = 1 WHERE recipient_uid = " . $this->user_id);
		
		$this->model("account")->increase_user_statistics(account_class::NOTIFICATION_UNREAD, 0, $this->user_id);
		
		return true;
	}

	public function delete_notify($where)
	{
		$this->query('DELETE FROM ' . get_table('notification_data') . ' WHERE notification_id IN (SELECT notification_id FROM ' . get_table('notification') . ' WHERE ' . $where . ')');
		
		return $this->delete('notification', $where);
	}

	/**
	 * 获取当前用户未读通知个数
	 * @param $uid
	 * @return number
	 */
	function get_notifications_unread_num($uid = 0)
	{
		return $this->get_notification_list(true, $uid, 0);
	}

	/**
	 * 获得用户通知列表
	 * @param $count
	 * @param $recipient_uid
	 * @param $read_flag
	 * @param $limit
	 * @return multitype:|number
	 */
	function get_notification_list($count = false, $recipient_uid = 0, $read_flag = 0, $limit = '')
	{
		$where = array();
		
		$recipient_uid = intval($recipient_uid);
		
		if ($recipient_uid == 0)
		{
			$recipient_uid = $this->user_id;
		}
		
		$where[] = 'recipient_uid = ' . $recipient_uid;
		
		switch ($read_flag)
		{
			case 0 :
				$where[] = 'read_flag = 0';
				break;
			case 1 :
				$where[] = 'read_flag = 1';
				break;
			default :
				break;
		}
		
		if ($read_flag == 0)
		{
			$sql = "SELECT MAX(notification_id) notification_id FROM " . get_table('notification') . " WHERE " . implode(' AND ', $where) . " GROUP BY model_type, source_id ORDER BY notification_id DESC";
		}
		else
		{
			$sql = "SELECT MAX(notification_id) notification_id FROM " . get_table('notification') . " WHERE " . implode(' AND ', $where) . " GROUP BY model_type, source_id, sender_uid, action_type ORDER BY read_flag ASC, notification_id DESC";
		}
		
		$rs = $this->query_all($sql, $limit);
		
		if (empty($rs))
		{
			if ($count)
			{
				return 0;
			}
			else
			{
				return array();
			}
		}
		
		$notification_ids = array();
		
		foreach ($rs as $val)
		{
			$notification_ids[] = $val['notification_id'];
		}
		
		if ($count)
		{
			return count($notification_ids);
		}
		else
		{
			return $notification_ids;
		}
	}

	/**
	 * 获得用户未读合并通知
	 * @param $count
	 * @param $recipient_uid
	 * @param $read_flag
	 * @param $limit
	 * @return multitype:|number
	 */
	function get_unread_notification($recipient_uid)
	{
		if ($rs = $this->fetch_all('notification', 'recipient_uid = ' . intval($recipient_uid) . ' AND read_flag = 0', 'notification_id DESC'))
		{
			$notification_ids = array();
			
			foreach($rs as $key => $val)
			{
				$notification_ids[] = $val['notification_id'];
			}
			
			if ($rs_data = $this->fetch_all('notification_data', "notification_id IN (" . implode(",", $notification_ids) . ')'))
			{
				foreach($rs_data as $key => $val)
				{
					$nt_data[$val['notification_id']] = $val['data'];
				}
			}
			
			foreach($rs as $key => $val)
			{
				$rs[$key]['data'] = unserialize($nt_data[$val['notification_id']]);
			}
			
			return $rs;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 根据通知ID集获得通知
	 * @param $notifications_id
	 */
	function get_notification_by_ids($notification_id, $recipient_uid = 0)
	{
		$notification_ids = array();
		
		$where = array();
		
		$data = array();
		
		if (is_array($notification_id))
		{
			$notification_ids = $notification_id;
		}
		else
		{
			$notification_ids[] = $notification_id;
		}
		
		if (empty($notification_ids))
		{
			return false;
		}
		
		array_walk_recursive($notification_ids, 'intval_string');
		
		$where[] = 'notification_id IN (' . implode(',', $notification_ids) . ')';
		
		if ($recipient_uid)
		{
			$where[] = ' recipient_uid = ' . $recipient_uid;
		}
		
		if (!$rs = $this->fetch_all('notification', implode(' AND ', $where)))
		{
			return false;
		}
		
		foreach ($rs as $key => $val)
		{
			$rs_data[$val['notification_id']] = $val;
		}
		
		if ($add_data = $this->fetch_all('notification_data', "notification_id IN (" . implode(",", $notification_ids) . ')'))
		{
			foreach($add_data as $key => $val)
			{
				$rs_data[$val['notification_id']]['data'] = unserialize($val['data']);
			}
		}
		
		foreach($notification_ids as $id)
		{
			$data[] = $rs_data[$id];
		}
		
		if (is_array($notification_id))
		{
			return $data;
		}
		else
		{
			return $data[0];
		}
	}
}