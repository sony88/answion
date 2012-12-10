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

class topic extends AWS_CONTROLLER
{
	var $per_page = 15;

	function get_permission_action()
	{
		
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 303));
	}

	public function index_action()
	{
		$this->list_action();
	}

	public function list_action()
	{
		if ($_POST)
		{
			foreach ($_POST as $key => $val)
			{
				if ($key == 'keyword')
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/topic/list/' . implode('__', $param)
			), 1, null));
		}
		
		$_GET['sort_key'] = isset($_GET['sort_key']) ? $_GET['sort_key'] : 'topic_id';
		$_GET['order'] = isset($_GET['order']) ? $_GET['order'] : 'DESC';

		$search_data = array(
			'action' => $_GET['action'],
			'keyword' => rawurldecode($_GET['keyword']),
			'question_count_min' => $_GET['question_count_min'],
			'question_count_max' => $_GET['question_count_max'],
			'topic_pic' => $_GET['topic_pic'],
			'topic_description' => $_GET['topic_description'], 
			'sort_key' => $_GET['sort_key'],
			'order' => $_GET['order'],
			'page' => $_GET['page'],
			'per_page' => $this->per_page,
		);

		$topic_list = $this->model('topic')->get_topic_search_list(false, $search_data);
		
		$totalnum = $this->model('topic')->get_topic_search_list(true, $search_data);
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if(isset($search_data[$key]) && !in_array($key, array('sort_key', 'order', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/topic/list/' . implode('__', $url_param);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . $search_url . '__sort_key-' . $_GET['sort_key'] . '__order-' . $_GET['order'], 
			'total_rows' => $totalnum, 
			'per_page' => $this->per_page, 
			'last_link' => "末页", 
			'first_link' => "首页", 
			'next_link' => "下一页 »", 
			'prev_link' => "« 上一页", 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		$this->crumb("话题列表", "admin/topic/list/");
		TPL::assign('topic_num', $totalnum);
		TPL::assign('search_url', $search_url);
		TPL::assign('list', $topic_list);
		TPL::output("admin/topic/list");
	}

	/**
	 * 格式化话题列表
	 * Enter description here ...
	 * @param unknown_type $list
	 */
	function topic_list_process($list)
	{
		if (empty($list))
		{
			return false;
		}
		
		foreach ($list as $key => $topic)
		{
			$list[$key]['add_time'] = date("Y-m-d H:i", $topic['add_time']);
			$list[$key]['topic_title'] = cjk_substr($topic['topic_title'], 0, 12, 'UTF-8', '...');
			$list[$key]['topic_pic'] = get_topic_pic_url('min', $topic['topic_pic']);
			
			if ($topic['parent_id'] > 0)
			{
				$list[$key]['parent'] = $this->model('topic')->get_topic_by_id($topic['parent_id']);
			}
		}
		
		return $list;
	}

	/**
	 * 锁定话题
	 * Enter description here ...
	 */
	public function topic_lock_action()
	{
		define('IN_AJAX', TRUE);
		
		$status = isset($_GET['status']) ? $_GET['status'] : 0;
		
		if (!in_array($status, array('1', '0')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, "系统错误"));
		}
		
		$this->model('topic')->lock_topic_by_ids($_GET['topic_id'], $status);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	/**
	 * 话题修改
	 * Enter description here ...
	 */
	public function edit_action()
	{
		$this->crumb("话题修改");
		
		$topic_id = $_GET['topic_id'];
		
		$topic = $this->model('topic')->get_topic_by_id($topic_id);
		
		if (empty($topic['parent_id']))
		{
			$parent_id = 0;
		}
		else
		{
			$parent_id = $topic['parent_id'];
			
			$parent_topic = $this->model('topic')->get_topic_by_id($parent_id);
		}
		
		$topic['topic_pic_max'] = get_topic_pic_url('max', $topic['topic_pic']);
		
		$topic['parent_id'] = empty($topic['parent_id']) ? 0 : $topic['parent_id'];
		
		$this->crumb("话题列表", "admin/topic/list/");
		
		TPL::assign('refer', $_SERVER['HTTP_REFERER']);
		TPL::assign('parent_id', $parent_id);
		TPL::assign('topic', $topic);
		TPL::assign('parent_topic', $parent_topic);
		TPL::output("admin/topic/edit");
	}

	/**
	 * 保存修改话题
	 * Enter description here ...
	 */
	public function save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		$topic_id = $_GET['topic_id'];
		$topic_title = trim($_POST['topic_title']);
		$topic_description = $_POST['topic_description'];
		$topic_lock = $_POST['topic_lock'];
		
		if (! $topic_info = $this->model('topic')->get_topic_by_id($topic_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "话题不存在！"));
		}
		
		if (empty($topic_id))
		{
			if (empty($topic_title))
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", "话题名称不能为空！"));
			}
			
			$topic_id = $this->model('topic')->save_topic(0, '', 1, 0, 3);
		}
		else
		{
			$topic_info = $this->model('topic')->get_topic_by_id($topic_id);
			
			if (($topic_info['topic_title'] != $topic_title) && ($this->model('topic')->get_topic_by_title($topic_title)))
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", "话题名称已经存在！"));
			}
		}
		
		if ($_FILES['topic_pic']['name'])
		{
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
			
			$topic_pic = date('Ymd') . '/' . basename($thumb_file['min']);
		}
		
		$this->model('topic')->update_topic($topic_id, $topic_title, $topic_description, $topic_pic, $topic_lock);
			
		$refer_url = empty($_POST['refer']) ? get_js_url("/admin/topic/list/") : $_POST['refer'];
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => $refer_url
		), 1, null));
	}

	/**
	 * 删除话题
	 * Enter description here ...
	 */
	public function topic_batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (!$_POST['topic_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(nul, -1, "请先选择话题"));
		}
		
		switch($_POST['action_type'])
		{
			case 'remove' : 
				$this->model('topic')->remove_topic_by_ids($_POST['topic_ids']);
			break;
			case 'lock' : 
				$this->model('topic')->lock_topic_by_ids($_POST['topic_ids'], 1);
			break;
		}
		
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, "删除成功"));
	}

}