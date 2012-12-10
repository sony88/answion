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
		$rule_action['rule_type'] = "white"; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		if ($this->user_info['permission']['visit_topic'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'square';
			$rule_action['actions'][] = 'index';
			$rule_action['actions'][] = 'detail';
		}
		
		return $rule_action;
	}

	public function setup()
	{		
		$this->crumb('话题', '/topic/');
	}

	public function index_action()
	{
		if ($_GET['id'] or $_GET['title'])
		{
			$this->_topic();
		}
		else
		{
			$this->square_action();
		}
	}
	
	public function square_action()
	{
		if ($today_topics = rtrim(get_setting('today_topics'), ','))
		{
			if (!$today_topic = $this->model('cache')->load('today_topic_' . md5($today_topics)))
			{
				if ($today_topic = $this->model('topic')->get_topic_by_title(array_random(explode(',', $today_topics))))
				{					
					$today_topic['best_answer_users'] = $this->model('topic')->get_best_answer_users($today_topic['topic_id'], 0, 5);
					$today_topic['action_list'] = $this->model('topic')->get_topic_action_list($today_topic['topic_id'], 3);
					
					$this->model('cache')->save('today_topic_' . md5($today_topics), $today_topic, (strtotime('Tomorrow') - time()));
				}
			}
			
			TPL::assign('today_topic', $today_topic);
		}
		
		$related_topics = $this->model('topic')->get_topic_list('topic_id != ' . intval($today_topic['topic_id']), get_setting('recommend_topics_number'), false, 'focus_count DESC');
		
		foreach ($related_topics AS $key => $val)
		{
			$related_topics[$key]['related_topics'] = $this->model('topic')->related_topics($val['topic_id']);
		}
		
		TPL::assign('related_topics', $related_topics);
		
		TPL::assign('hot_topics', $this->model('topic')->get_topic_list('topic_id != ' . intval($today_topic['topic_id']), 10, false, 'discuss_count DESC'));
		
		$edit_log_list = ACTION_LOG::get_action_by_event_id(null, 5, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
			ACTION_LOG::ADD_TOPIC,
			ACTION_LOG::MOD_TOPIC,
			ACTION_LOG::MOD_TOPIC_DESCRI,
			ACTION_LOG::MOD_TOPIC_PIC,
			ACTION_LOG::DELETE_TOPIC,
			ACTION_LOG::ADD_RELATED_TOPIC,
			ACTION_LOG::DELETE_RELATED_TOPIC
		)));
		
		foreach ($edit_log_list AS $key => $val)
		{
			$edit_log_topic_ids[] = $val['associate_id'];
			$edit_log_uids[] = $val['uid'];
		}
		
		if ($edit_log_uids)
		{
			$edit_log_user_info = $this->model('account')->get_user_info_by_uids($edit_log_uids);
			$edit_log_topic_info = $this->model('topic')->get_topics_by_ids($edit_log_topic_ids);
		}
		
		foreach ($edit_log_list AS $key => $val)
		{
			$edit_log[] = array(
				'topic_info' => $edit_log_topic_info[$val['associate_id']],
				'user_info' => $edit_log_user_info[$val['uid']],
				'add_time' => $val['add_time']
			);
		}
		
		TPL::assign('edit_log', $edit_log);
		
		TPL::assign('unedit_topics', $this->model('topic')->get_topic_list('topic_description = \'\'', 10, false, 'topic_id DESC'));
		
		$this->crumb('话题广场', '/topic/');
		
		TPL::import_css(array(
			'css/main.css'
		));
		
		TPL::output('topic/square');
	}
	
	/**
	 * 话题主页
	 */
	public function _topic()
	{
		if (is_numeric($_GET['id']))
		{
			if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
			{
				$topic_info = $this->model('topic')->get_topic_by_title($_GET['id']);
			}
		}
		else if ($topic_info = $this->model('topic')->get_topic_by_title($_GET['id']))
		{
			
		}
		else
		{
			$topic_info = $this->model('topic')->get_topic_by_url_token($_GET['id']);
		}
		
		if (!$topic_info)
		{
			H::redirect_msg('话题不存在', '/');
		}
		
		if (urldecode($topic_info['url_token']) != $_GET['id'])
		{
			HTTP::redirect('/topic/' . $topic_info['url_token']);
		}
		
		TPL::assign('best_answer_users', $this->model('topic')->get_best_answer_users($topic_info['topic_id'], $this->user_id, 5));
		
		$topic_info['has_focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_info['topic_id']);
		
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['url_token']);
		
		if ($topic_info['topic_description'])
		{
			TPL::set_meta('description', $topic_info['topic_title'] . ' - ' . cjk_substr(str_replace("\r\n", ' ', strip_tags($topic_info['topic_description'])), 0, 128, 'UTF-8', '...'));
		}
		
		$topic_info['topic_description'] = nl2br(FORMAT::parse_markdown($topic_info['topic_description']));
		
		TPL::assign('topic_info', $topic_info);
		
		TPL::assign('related_topics', $this->model('topic')->related_topics($topic_info['topic_id']));
		
		$this->model('topic')->update_discuss_count($topic_info['topic_id']);
		
		$log_list = ACTION_LOG::get_action_by_event_id($topic_info['topic_id'], 10, ACTION_LOG::CATEGORY_TOPIC, implode(',', array(
			ACTION_LOG::ADD_TOPIC,
			ACTION_LOG::MOD_TOPIC,
			ACTION_LOG::MOD_TOPIC_DESCRI,
			ACTION_LOG::MOD_TOPIC_PIC,
			ACTION_LOG::DELETE_TOPIC,
			ACTION_LOG::ADD_RELATED_TOPIC,
			ACTION_LOG::DELETE_RELATED_TOPIC
		)));
		
		$log_list = $this->model('topic')->analysis_log($log_list);
		
		TPL::assign('log_list', $log_list);
		
		TPL::import_css('css/main.css');
		TPL::import_js('js/ajaxupload.js');
		TPL::output('topic/index');
	}
	
	public function edit_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg('话题不存在', '/');
		}
		
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::redirect_msg('你没有权限进行操作');
			}
			else if ($this->model('topic')->has_lock_topic($_GET['id']))
			{
				H::redirect_msg('锁定话题不能编辑');
			}
		}
		
		$this->crumb('话题编辑', '/topic/edit/' . $topic_info['topic_id']);
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);
		
		TPL::assign('topic_info', $topic_info);
		TPL::assign('related_topics', $this->model('topic')->related_topics($_GET['id']));
		
		TPL::import_css(array(
			'css/main.css'
		));
		
		TPL::import_js('js/ajaxupload.js');
		
		if (get_setting('advanced_editor_enable') == 'Y')
		{
			TPL::import_js('js/markItUp.js');
		}
		
		TPL::output('topic/edit');
	}
	
	public function manage_action()
	{
		if (! $topic_info = $this->model('topic')->get_topic_by_id($_GET['id']))
		{
			H::redirect_msg('话题不存在', '/');
		}
		
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::redirect_msg('你没有权限进行操作');
			}
			else if ($this->model('topic')->has_lock_topic($_GET['id']))
			{
				H::redirect_msg('锁定话题不能编辑');
			}
		}
		
		$this->crumb('话题管理', '/topic/manage/' . $topic_info['topic_id']);
		$this->crumb($topic_info['topic_title'], '/topic/' . $topic_info['topic_id']);
		
		TPL::assign('topic_info', $topic_info);
		
		TPL::import_css(array(
			'css/main.css'
		));
		
		TPL::output('topic/manage');
	}
}