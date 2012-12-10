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
			'topic_info',
			'question_list',
			'get_focus_users',
			'topics_list'
		);
		
		return $rule_action;
	}

	function setup()
	{
		HTTP::no_cache_header();
	}

	public function get_focus_users_action()
	{
		if ($focus_users = $this->model('topic')->get_focus_users_by_topic($_GET['topic_id'], 18))
		{
			foreach ($focus_users as $key => $val)
			{
				$focus_users[$key]['avatar_file'] = get_avatar_url($val['uid'], 'mid');
				$focus_users[$key]['url'] = get_js_url('/people/' . $val['url_token']);
			}
		}
		
		H::ajax_json_output($focus_users);
	}

	public function question_list_action()
	{
		if ($_GET['feature_id'])
		{
			$_GET['topic_id'] = $this->model('feature')->get_topics_by_feature_id($_GET['feature_id'], false, false);
		}
				
		if ($_GET['type'] == 'best')
		{
			$action_list = $this->model('topic')->get_topic_action_list($_GET['topic_id'], intval($_GET['page']) * get_setting('contents_per_page') . ', ' . get_setting('contents_per_page'), TRUE);
		}
		else if ($_GET['type'] == 'favorite')
		{
			$action_list = $this->model('favorite')->get_tag_action_list($_GET['topic_title'], $this->user_id, intval($_GET['page']) * get_setting('contents_per_page') . ', ' . get_setting('contents_per_page'));
		}
		else
		{
			$action_list = $this->model('topic')->get_topic_action_list($_GET['topic_id'], intval($_GET['page']) * get_setting('contents_per_page') . ', ' . get_setting('contents_per_page'));
		}
		
		TPL::assign('list', $action_list);
		
		if ($_GET['template'] == 'mobile')
		{
			TPL::output('mobile/ajax/index_actions');
		}
		else
		{
			TPL::output('home/ajax/index_actions');
		}
	}

	public function topic_info_action()
	{
		$topic_id = $_GET['topic_id'];
		
		$topic_info = $this->model('topic')->get_topic_by_id($topic_id);
		
		$data['type'] = 'topic';
		$data['topic_id'] = $topic_info['topic_id'];
		$data['topic_title'] = H::sensitive_words($topic_info['topic_title']);
		$data['topic_description'] = strip_tags(cjk_substr($topic_info['topic_description'], 0, 80, 'UTF-8', '...'));
		$data['focus_count'] = $topic_info['focus_count'];
		
		if ($this->user_id)
		{
			$data['focus'] = $this->model('topic')->has_focus_topic($this->user_id, $topic_id);
		}
		
		$data['discuss_count'] = $topic_info['discuss_count'];
		
		$data['topic_pic'] = get_topic_pic_url('mid', $topic_info['topic_pic']);
		
		$data['url'] = get_js_url('topic/' . $topic_info['url_token']);
		
		H::ajax_json_output($data);
	}

	public function edit_topic_action()
	{
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '你没有权限进行操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_POST['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定话题不能编辑'));
			}
		}
		
		if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题不存在'));
		}
		
		if ((get_setting('topic_title_limit') > 0) && (cjk_strlen($_POST['topic_title']) > get_setting('topic_title_limit')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题标题字数不得超过' . get_setting('topic_title_limit') . '个'));
		}
		
		$new_topic_id = $this->model('topic')->get_topic_id_by_title($_POST['topic_title']);
		
		if ($new_topic_id AND $new_topic_id != $_POST['topic_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '同名话题已经存在'));
		}
		
		if (!$_POST['topic_description'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '请填写话题描述'));
		}
		
		$this->model('topic')->update_topic($_POST['topic_id'], $_POST['topic_title'], $_POST['topic_description']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/topic/' . $_POST['topic_id'])
		), 1, null));
	}
	
	public function save_related_topic_action()
	{
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '你没有权限进行操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定话题不能编辑'));
			}
		}
		
		if (!$this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题不存在'));
		}
		
		$topic_title = trim($_POST['topic_title']);
		
		if (!$topic_title)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '请输入话题标题'));
		}
		
		if (preg_match('/\//is', $topic_title))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题不能包含"/"'));
		}
		
		if ((get_setting('topic_title_limit') > 0) && (cjk_strlen($topic_title) > get_setting('topic_title_limit')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题标题字数不得超过' . get_setting('topic_title_limit') . '个'));
		}
		
		$related_id = $this->model('topic')->save_topic(0, $topic_title, $this->user_id, 0);
		
		if (!$this->model('topic')->save_related_topic($_GET['topic_id'], $related_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '已经存在相同推荐话题'));
		}
		
		ACTION_LOG::save_action($this->user_id, $_GET['topic_id'], ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::ADD_RELATED_TOPIC, '', $related_id);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'related_id' => $related_id,
		), 1, null));
	}
	
	public function remove_related_topic_action()
	{
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '你没有权限进行操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定话题不能编辑'));
			}
		}
		
		$this->model('topic')->remove_related_topic($_GET['topic_id'], $_GET['related_id']);
		
		ACTION_LOG::save_action($this->user_id, $_GET['topic_id'], ACTION_LOG::CATEGORY_TOPIC, ACTION_LOG::DELETE_RELATED_TOPIC, '', $_GET['related_id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
	
	public function upload_topic_pic_action()
	{
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '你没有权限进行操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_GET['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定话题不能编辑'));
			}
		}
		
		if (!$topic_info = $this->model('topic')->get_topic_by_id($_GET['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题 ID 错误'));
		}
		
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/topic/' . date('Ymd'),
			'is_image' => TRUE,
			'max_size' => (get_setting('upload_avatar_size_limit') * 1024)
		))->do_upload('topic_pic');
		
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
			foreach(AWS_APP::config()->get('image')->topic_thumbnail AS $key => $val)
			{				
				$thumb_file[$key] = $upload_data['file_path'] . str_replace($upload_data['file_ext'], '_' . $val['w'] . '_' . $val['h'] . $upload_data['file_ext'], basename($upload_data['full_path']));
				
				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();
				
				@unlink(get_setting('upload_dir') . '/topic/' . str_replace(AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'], $val['w'] . '_' . $val['h'], $topic_info['topic_pic']));
			}
			
			@unlink(get_setting('upload_dir') . '/topic/' . str_replace('_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->topic_thumbnail['min']['h'], '', $topic_info['topic_pic']));
		}
		
		$this->model('topic')->update_topic($_GET['topic_id'], null, null, date('Ymd') . '/' . basename($thumb_file['min']));
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'preview' => get_setting('upload_url') . '/topic/' . date('Ymd') . '/' . basename($thumb_file['max'])
		), 1, null));
	}

	/**
	 * 
	 * 关注话题
	 * 
	 * @return boolean true|false
	 */
	public function focus_topic_action()
	{	
		if (!$_GET['topic_id'])
		{
			return false;
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('topic')->add_focus_topic($this->user_id, intval($_GET['topic_id']))
		), '1', null));
	}
	
	/**
	 * 锁定/解锁话题
	 */
	public function lock_topic_action()
	{
		if (!$_GET['topic_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '没有找到相关话题'));
		}
		
		$this->model('topic')->lock_topic_by_id($_GET['topic_id'], $this->model('topic')->has_lock_topic($_GET['topic_id']));
		
		H::ajax_json_output(AWS_APP::RSM(null, 1));
	}
	
	public function focus_list_action()
	{
		if ($focus_list = $this->model('topic')->get_focus_topic_list($this->user_id, intval($_GET['page']) * get_setting('focus_topics_list_per_page') . ', ' . get_setting('focus_topics_list_per_page')))
		{
			foreach ($focus_list AS $key => $val)
			{
				$focus_list[$key]['action_list'] = $this->model('topic')->get_topic_action_list($val['topic_id'], 3);
			}
		}
		
		TPL::assign('list', $focus_list);
		
		TPL::output('topic/ajax/focus_list');
	}
	
	public function topics_list_action()
	{
		if ($topic_list = $this->model('topic')->get_topic_list(null, intval($_GET['page']) * 10 . ', ' . 10, false, 'discuss_count DESC'))
		{
			foreach ($topic_list as $key => $val)
			{
				$topic_ids[] = $val['topic_id'];
			}
			
			if ($topic_ids)
			{
				$topic_focus = $this->model('topic')->has_focus_topics($this->user_id, $topic_ids);
				
				foreach ($topic_list as $key => $val)
				{
					$topic_list[$key]['has_focus'] = $topic_focus[$val['topic_id']];
				}
			}
		}
		
		TPL::assign('list', $topic_list);
		
		TPL::output('topic/ajax/topic_list');
	}
	
	function save_url_token_action()
	{
		if (!($this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator']))
		{
			if (!$this->user_info['permission']['edit_topic'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '你没有权限进行操作'));
			}
			else if ($this->model('topic')->has_lock_topic($_POST['topic_id']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定话题不能编辑'));
			}
		}
		
		if (!$topic_info = $this->model('topic')->get_topic_by_id($_POST['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题不存在'));
		}
		
		if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题别名只允许输入英文或数字'));
		}
			
		if ($this->model('topic')->check_url_token($_POST['url_token'], $topic_info['topic_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题别名已经被占用请更换一个'));
		}
			
		if (preg_match("/^[\d]+$/i", $_POST['url_token']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题别名不允许为纯数字'));
		}
			
		$this->model('topic')->update_url_token($_POST['url_token'], $topic_info['topic_id']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_js_url('/topic/' . $_POST['url_token'])
		), 1, null));
	}
}