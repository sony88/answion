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

class ACTION_LOG
{
	/** 
	 * 添加问题
	 */
	const ADD_QUESTION = 101;
	/** 
	 * 修改问题标题
	 */
	const MOD_QUESTON_TITLE = 102;
	/** 
	 * 修改问题描述 
	 */
	const MOD_QUESTION_DESCRI = 103;
	/**
	 * 添加问题关注
	 */
	const ADD_REQUESTION_FOCUS = 105;
	/**
	 * 删除问题关注
	 */
	const DELETE_REQUESTION_FOCUS = 106;
	/**
	 * 问题重定向
	 */
	const REDIRECT_QUESTION = 107;
	/**
	 * 修改问题分类
	 */
	const MOD_QUESTION_CATEGORY = 108;
	/**
	 * 修改问题附件
	 */
	const MOD_QUESTION_ATTACH = 109;
	/**
	 * 删除问题重定向
	 */
	const DEL_REDIRECT_QUESTION = 110;
	/**
	 * 修改问题
	 */
	const MOD_QUESTION = 111;
	/** 
	 * 回复问题
	 */
	const ANSWER_QUESTION = 201;
	/** 
	 * 修改回复
	 */
	const MOD_ANSWER = 202;
	/**
	 * 删除回复 
	 */
	const DELETE_ANSWER = 203;
	/**
	 * 增加赞同
	 */
	const ADD_AGREE = 204;
	/**
	 * 增加反对投票
	 */
	const ADD_AGANIST = 205;
	/**
	 * 增加感谢作者
	 */
	const ADD_USEFUL = 206;
	/**
	 * 问题没有帮助
	 */
	const ADD_UNUSEFUL = 207;
	/**
	 * 取消赞成
	 */
	const DEL_AGREE = 208;
	/**
	 * 取消反对投票
	 */
	const DEL_AGANIST = 209;
	/** 
	 * 增加评论
	 */
	const ADD_COMMENT = 301;
	/**
	 * 删除评论
	 */
	const DELETE_COMMENT = 302;
	/** 
	 * 创建话题
	 */
	const ADD_TOPIC = 401;
	/** 
	 * 修改话题
	 */
	const MOD_TOPIC = 402;
	/** 
	 * 修改话题描述
	 */
	const MOD_TOPIC_DESCRI = 403;
	/**
	 * 修改话题缩图
	 */
	const MOD_TOPIC_PIC = 404;
	/**
	 * 删除话题
	 */
	const DELETE_TOPIC = 405;
	/**
	 * 添加话题关注
	 */
	const ADD_TOPIC_FOCUS = 406;
	/**
	 * 删除话题关注
	 */
	const DELETE_TOPIC_FOCUS = 407;
	/**
	 * 增加话题分类
	 */
	const ADD_TOPIC_PARENT = 408;
	/**
	 * 删除话题分类
	 */
	const DELETE_TOPIC_PARENT = 409;
	/**
	 * 添加相关话题
	 */
	const ADD_RELATED_TOPIC = 410;
	/**
	 * 删除相关话题
	 */
	const DELETE_RELATED_TOPIC = 411;
	/**
	 * 问题
	 */
	const CATEGORY_QUESTION = 1;
	/**
	 * 回复
	 */
	const CATEGORY_ANSWER = 2;
	/**
	 * 评论
	 */
	const CATEGORY_COMMENT = 3;
	/**
	 * 话题 
	 */
	const CATEGORY_TOPIC = 4;
	
	/**
	 * 
	 * 类型定义对应数组
	 */
	public static $ACTION_STRING_ARRAY = array(
		self::ADD_QUESTION => '添加问题', 
		self::MOD_QUESTON_TITLE => '修改问题标题', 
		self::MOD_QUESTION_DESCRI => '修改问题描述', 
		self::ADD_REQUESTION_FOCUS => '添加问题关注', 
		self::DELETE_REQUESTION_FOCUS => '删除问题关注', 
		self::REDIRECT_QUESTION => '问题重定向', 
		self::DEL_REDIRECT_QUESTION => '删除问题重定向', 
		self::ANSWER_QUESTION => '回复问题', 
		self::MOD_ANSWER => '修改回复', 
		self::DELETE_ANSWER => '删除回复', 
		self::ADD_AGREE => '增加赞成', 
		self::ADD_AGANIST => '增加反对 ', 
		self::DEL_AGREE => '取消赞成', 
		self::DEL_AGANIST => '取消反对 ', 
		self::ADD_COMMENT => '增加评论', 
		self::DELETE_COMMENT => '删除评论', 
		self::ADD_TOPIC => '添加话题', 
		self::MOD_TOPIC => '修改话题', 
		self::MOD_TOPIC_DESCRI => '修改话题描述', 
		self::MOD_TOPIC_PIC => '修改话题缩略图', 
		self::DELETE_TOPIC => '删除话题', 
		self::ADD_TOPIC_FOCUS => '关注话题', 
		self::DELETE_TOPIC_FOCUS => '取消话题关注', 
		self::ADD_TOPIC_PARENT => '增加话题分类', 
		self::DELETE_TOPIC_PARENT => '删除话题分类'
	);

	/**
	 * 
	 * 增加用户动作跟踪
	 * @param int    $uid
	 * @param int    $associate_id   关联ID
	 * @param int    $action_type    动作大类型
	 * @param int    $action_id      动作详细类型
	 * @param string $action_content 动作内容
	 * @param string $action_attch   动作附加内容
	 * @param int    $add_time       动作发送时间
	 * 
	 * @return boolean true|false
	 */
	public static function save_action($uid, $associate_id, $action_type, $action_id, $action_content = '', $action_attch = '', $add_time = 0, $anonymous = 0, $addon_data = null)
	{
		if (intval($uid) == 0 || intval($associate_id) == 0)
		{
			return false;
		}
		
		//增加用户计数器
		self::update_user_nums($action_id, uid);
		
		if (is_numeric($action_attch))
		{
			$action_attch_insert = $action_attch;
		}
		else
		{
			$action_attch_insert = '-1';
			$action_attch_update = $action_attch;
		}
		
		$history_id = AWS_APP::model()->insert('user_action_history', array(
			'uid' => intval($uid), 
			'associate_type' => $action_type, 
			'associate_action' => $action_id, 
			'associate_id' => $associate_id, 
			'associate_attached' => $action_attch_insert,
			'add_time' => ($add_time == 0) ? time() : $add_time,
			'anonymous' => $anonymous,
		));
		
		AWS_APP::model()->insert('user_action_history_data', array(
			'history_id' => $history_id, 
			'associate_content' => htmlspecialchars($action_content), 
			'associate_attached' => htmlspecialchars($action_attch_update),
			'addon_data' => $addon_data ? serialize($addon_data) : '',
		));
		
		return $history_id;
	}

	/**
	 * 
	 * 更新用户计数器
	 */
	public static function update_user_nums($action_id, $uid)
	{
		$account_class = new account_class();
		
		switch ($action_id)
		{
			case self::ADD_COMMENT :
				
				break;
			case self::ADD_QUESTION :
				
				break;
			case self::ADD_TOPIC :
				
				break;
			case self::ADD_TOPIC_FOCUS :
				
				break;
				
			case self::ANSWER_QUESTION :
				$account_class->increase_user_statistics(account_class::ANSWER_COUNT, $uid);
				break;
			case self::DELETE_ANSWER :
				$account_class->increase_user_statistics(account_class::ANSWER_COUNT, -1, $uid);
				break;
			case self::DELETE_COMMENT :
				
				break;
			case self::DELETE_REQUESTION_FOCUS :
				
				break;
			case self::DELETE_TOPIC :
				
				break;
			case self::DELETE_TOPIC_FOCUS :
				
				break;
			case self::DELETE_TOPIC_PARENT :
				
				break;
			case self::MOD_ANSWER :
			case self::MOD_QUESTION_DESCRI :
			case self::MOD_QUESTON_TITLE :
			case self::MOD_TOPIC :
			case self::MOD_TOPIC_DESCRI :
			case self::MOD_TOPIC_PIC :
		}
	}

	/**
	 * 
	 * 根据事件 ID,得到事件列表
	 * @param boolean $count
	 * @param int     $event_id
	 * @param int     $limit
	 * @param int     $action_type
	 * @param int     $action_id
	 * 
	 * @return array
	 */
	public static function get_action_by_event_id($event_id = 0, $limit = 20, $action_type = null, $action_id = null)
	{
		if ($event_id > 0)
		{
			$where[] = 'associate_id = ' . intval($event_id);
		}
		
		if ($action_type)
		{
			$where[] = 'associate_type IN (' . $action_type . ')';
		}
		
		if ($action_id)
		{
			$where[] = 'associate_action IN (' . $action_id . ')';
		}
		else
		{
			$where[] = 'associate_action NOT IN (' . implode(',', array(				
				self::ADD_REQUESTION_FOCUS,
				self::DELETE_REQUESTION_FOCUS,
				self::DELETE_ANSWER,
				self::ADD_AGREE,
				self::ADD_AGANIST,
				self::ADD_USEFUL,
				self::ADD_UNUSEFUL,
				self::DEL_AGREE,
				self::DEL_AGANIST,
			)) . ')';
		}
		
		if ($user_action_history = AWS_APP::model()->fetch_all('user_action_history', implode(' AND ', $where), 'add_time DESC', $limit))
		{
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
				
			$actions_data = self::get_action_data_by_history_ids($history_ids);
			
			foreach ($user_action_history AS $key => $val)
			{
				$user_action_history[$key]['addon_data'] = $actions_data[$val['history_id']]['addon_data'];
				$user_action_history[$key]['associate_content'] = $actions_data[$val['history_id']]['associate_content'];
				
				if ($val['associate_attached'] == -1)
				{
					$user_action_history[$key]['associate_attached'] = $actions_data[$val['history_id']]['associate_attached'];
				}
			}
		}
			
		return $user_action_history;
	}
	
	public static function get_action_data_by_history_ids($history_ids)
	{
		if ($action_data = AWS_APP::model()->fetch_all('user_action_history_data', 'history_id IN(' . implode(',', $history_ids) . ')'))
		{
			foreach ($action_data AS $key => $val)
			{
				if ($val['addon_data'])
				{
					$val['addon_data'] = unserialize($val['addon_data']);
				}
				
				$result[$val['history_id']] = $val;
			}
		}
		
		return $result;
	}
	
	public static function get_action_data_by_history_id($history_id)
	{
		return AWS_APP::model()->fetch_row('user_action_history_data', 'history_id = ' . intval($history_id));
	}

	public static function get_action_by_history_id($history_id)
	{
		if ($action_history = AWS_APP::model()->fetch_row('user_action_history', 'history_id = ' . intval($history_id)))
		{
			$action_history_data = self::get_action_data_by_history_id($action_history['history_id']);
			
			$action_history['associate_content'] = $action_history_data['associate_content'];
			
			if ($action_history['associate_attached'] == -1)
			{
				$action_history['associate_attached'] = $action_history_data['associate_attached'];
			}
		}
		
		return $action_history;
	}

	public static function update_action_time_by_history_id($history_id)
	{
		return AWS_APP::model()->update('user_action_history', array(
			'add_time' => time()
		), 'history_id = ' . intval($history_id));
	}

	/**
	 * 
	 * 根据条件,得到事件列表
	 * @param int     $limit
	 * 
	 * @return array
	 */
	public static function get_action_by_where($where = '', $limit = 20, $show_anonymous = false, $order = 'add_time DESC')
	{
		if (! $where)
		{
			return false;
		}
		
		if (!$show_anonymous)
		{
			$where = '(' . $where . ') AND anonymous = 0';
		}
		
		if ($user_action_history = AWS_APP::model()->fetch_all('user_action_history', $where, $order, $limit))
		{
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
				
			$actions_data = self::get_action_data_by_history_ids($history_ids);
				
			foreach ($user_action_history AS $key => $val)
			{
				$user_action_history[$key]['associate_content'] = $actions_data[$val['history_id']]['associate_content'];
				
				if ($val['associate_attached'] == -1)
				{
					$user_action_history[$key]['associate_attached'] = $actions_data[$val['history_id']]['associate_attached'];
				}
			}
		}
		
		return $user_action_history;
	}
		
	public static function get_actions_distint_by_where($where = '', $limit = 20, $add_time = null, $show_anonymous = false)
	{
		if (!$where)
		{
			return false;
		}
		
		if ($add_time)
		{
			$where = '(' . $where . ') AND add_time > ' . intval($add_time);
		}
		
		if (!$show_anonymous)
		{
			$where = '(' . $where . ') AND anonymous = 0';
		}
		
		$sql = "SELECT MAX(history_id) history_id FROM " . get_table('user_action_history') . " WHERE " . $where . " GROUP BY associate_id, associate_type ORDER BY history_id DESC";
		
		if ($action_history = AWS_APP::model()->query_all($sql, $limit))
		{
			foreach ($action_history as $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
			
			if ($action_history = self::get_action_by_where('history_id IN(' . implode(',', $history_ids) . ')', null, $show_anonymous, null))
			{
				$last_history = array();
						
				foreach ($action_history as $key => $val)
				{
					$last_history[$val['history_id']] = $action_history[$key];
				}
						
				krsort($last_history);
						
				return $last_history;
			}	
		}
		
		return array();
	}

	/**
	 * 
	 * 得到不重复的日志,以关联ID为唯一
	 * @param int $uid
	 * @param int $limit
	 * @param int $action_type
	 * @param int $action_id
	 * 
	 * @return array
	 */
	public static function get_action_distinct($uid = 0, $limit = null, $action_type = null, $action_id = null)
	{
		$sql = "SELECT DISTINCT associate_id, associate_type FROM " . get_table('user_action_history') . " WHERE uid = " . intval($uid);
		
		if ($action_type)
		{
			$sql .= ' AND associate_type IN(' . $action_type . ')';
		}
		
		if ($action_id)
		{
			$sql .= ' AND associate_action IN(' . $action_id . ')';
		}
		
		$sql .= ' ORDER BY add_time DESC';
		
		return AWS_APP::model()->query_all($sql, $limit);
	}

	/**
	 * 
	 * 根据问题日志获取一个条日志相信信息
	 * @param int $associate_id
	 * @param int $action_type
	 * @param int $limit
	 * 
	 * @return array
	 */
	public static function get_action_detail_by_action_type($associate_id = '', $action_type = '', $limit = 1, $action_id = '', $uid = 0)
	{		
		$sql = "SELECT * FROM " . get_table('user_action_history') . " WHERE associate_id = " . intval($associate_id);
		
		if ($action_type)
		{
			$sql .= ' AND associate_type IN (' . $action_type . ')';
		}
		
		if ($action_id)
		{
			$sql .= ' AND associate_action IN(' . $action_id . ')';
		}
		
		if ($uid)
		{
			$sql .= ' AND uid = ' . intval($uid);
		}
		
		$sql .= ' ORDER BY add_time DESC, history_id DESC';
		
		if ($user_action_history = AWS_APP::model()->query_all($sql, $limit))
		{
			foreach ($user_action_history AS $key => $val)
			{
				$history_ids[] = $val['history_id'];
			}
				
			$actions_data = self::get_action_data_by_history_ids($history_ids);
				
			foreach ($user_action_history AS $key => $val)
			{
				$user_action_history[$key]['associate_content'] = $actions_data[$val['history_id']]['associate_content'];
				
				if ($user_action_history[$key]['associate_attached'] == -1)
				{
					$user_action_history[$key]['associate_attached'] = $actions_data[$val['history_id']]['associate_attached'];
				}
			}
		}
		
		return $user_action_history;
	}
	
	public static function format_action_str($action, $uid = 0, $user_name = null, $question_info = array(), $topic_info = array())
	{
		$action = intval($action);
		$action_str = null;
		$user_tip = 'class="user_msg" data-message="&uid=' . $uid . '&card=user"';
		$topic_tip = 'class="user_msg" data-message="&uid=' . $topic_info['topic_id'] . '&card=topic"';
		$user_url = 'people/' . $uid;
		$topic_url = 'topic/' . $topic_info['url_token'];
		
		switch ($action)
		{
			case ACTION_LOG::ADD_QUESTION : //'添加问题',
				if ($question_info['anonymous'] == 1)
				{
					$action_str = "匿名用户 发起了问题";
				}
				else
				{
					$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 发起了问题";
				}
				break;
				
			case ACTION_LOG::MOD_QUESTON_TITLE : //'修改问题标题',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 修改了问题标题";
				break;
				
			case ACTION_LOG::MOD_QUESTION_DESCRI : // '修改问题描述',	
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 修改了问题";
				break;
				
			case ACTION_LOG::ADD_REQUESTION_FOCUS : // '添加问题关注',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 关注了该问题";
				break;
				
			case ACTION_LOG::DELETE_REQUESTION_FOCUS : // '删除问题关注',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 取消关注了该问题";
				break;
				
			case ACTION_LOG::ANSWER_QUESTION : // '回复问题',
				if ($topic_info)
				{
					$action_str = " <a href=\"{$topic_url}\" class='default_topic' {$topic_tip}>" . $topic_info['topic_title'] . "</a> 话题添加了一个问题回复";
				}
				else if ($user_name)
				{
					$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 回复了问题";
				}
				else
				{
					$action_str = "该问题增加了一个回复";
				}
				break;
			
			case ACTION_LOG::MOD_ANSWER : // '修改回复',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 修改了回复";
				break;
				
			case ACTION_LOG::ADD_AGREE : //'增加赞同',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 赞同了该回复";
				break;
			
			case ACTION_LOG::ADD_COMMENT : // '增加评论',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 添加了评论";
				break;
				
			case ACTION_LOG::DELETE_COMMENT : //'删除评论',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 删除了评论";
				break;
			
			case ACTION_LOG::ADD_TOPIC : //'添加话题',
				if ($topic_info && $user_name)
				{
					if (isset($topic_info[0]))
					{
						$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 将该问题添加到 ";
						$tmp_i = 0;
						
						foreach ($topic_info as $key => $val)
						{
							$action_str .= "<a href=\"topic/" . $val['url_token'] . " {$topic_tip}>" . $val['topic_title'] . "</a>";
							
							$tmp_i++;
							
							if ($tmp_i > 2)
							{
								break;
							}
						}
						
						if (count($topic_info) > 3)
						{
							$action_str .= "等";
						}
						
						$action_str .= "话题";
					}
					else
					{
						$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 将该问题添加到 <a href=\"topic/" . rawurlencode($topic_info['topic_title']) . "\" class='default_topic' $topic_tip>" . $topic_info['topic_title'] . "</a> 话题";
					
					}
				
				}
				else if ($topic_info)
				{
					if (isset($topic_info[0]))
					{
						$action_str = '该问题被添加到 ';
						$tmp_i = 0;
						
						foreach ($topic_info as $key => $val)
						{
							$action_str .= "<a href=\"topic/" . $val['url_token'] . " {$topic_tip}>" . $val['topic_title'] . "</a>";
							
							$tmp_i++;
							
							if ($tmp_i > 2)
							{
								break;
							}
						}
						
						if (count($topic_info) > 3)
						{
							$action_str .= '等';
						}
						
						$action_str .= '话题';
					}
					else
					{
						$action_str = "该问题被添加到 <a href=\"{$topic_url}\" {$topic_tip}>" . $topic_info['topic_title'] . "</a> 话题";
					}
				}
				else if ($user_name)
				{
					$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 添加了一个话题";
				}
				else
				{
					$action_str = '该问题添加了一个话题';
				}
				break;
			
			case ACTION_LOG::MOD_TOPIC : // '修改话题',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 修改话题";
				break;
			
			case ACTION_LOG::MOD_TOPIC_DESCRI : // '修改话题描述',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 修改话题描述";
				break;
			
			case ACTION_LOG::MOD_TOPIC_PIC : // '修改话题缩略图',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 修改话题缩略图";
				break;
			
			case ACTION_LOG::DELETE_TOPIC : // '删除话题',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 删除话题";
				break;
			
			case ACTION_LOG::ADD_TOPIC_FOCUS : // '关注话题',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 关注话题";
				break;
			
			case ACTION_LOG::DELETE_TOPIC_FOCUS : // '取消话题关注',
				$action_str = "<a href=\"{$user_url}\" {$user_tip}>{$user_name}</a> 取消话题关注";
				break;
		}
		
		return $action_str;
	}
	
	public static function delete_action_history($where)
	{
		if ($action_history = AWS_APP::model()->fetch_all('user_action_history', $where))
		{
			foreach ($action_history AS $key => $val)
			{
				AWS_APP::model()->delete('user_action_history_data', 'history_id = ' . $val['history_id']);
			}
			
			$action_history = AWS_APP::model()->delete('user_action_history', $where);
		}
	}
}