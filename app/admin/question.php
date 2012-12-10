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

if (! defined('IN_ANWSION'))
{
	die();
}

class question extends AWS_CONTROLLER
{
	var $per_page = 20;

	function get_permission_action()
	{
	
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 301));
	}

	public function index_action()
	{
		$this->list_action();
	}

	public function question_list_action()
	{
		if ($_POST)
		{
			if ($_POST['username'] && (! $user_info = $this->model('account')->get_user_info_by_username($_POST['username'])))
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "搜索的用户不存在。"));
			}
			
			foreach ($_POST as $key => $val)
			{
				if ($key == 'start_date' || $key == 'end_date')
				{
					$val = base64_encode($val);
				}
				
				if ($key == 'keyword' || $key == 'username' || $key == 'topic')
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/question/question_list/' . implode('__', $param)
			), 1, null));
		}
		
		$_GET['sort_key'] = isset($_GET['sort_key']) ? $_GET['sort_key'] : 'question_id';
		$_GET['order'] = isset($_GET['order']) ? $_GET['order'] : 'DESC';

		$search_data = array(
			'action' => $_GET['action'],
			'detail' => TRUE,
			'keyword' => rawurldecode($_GET['keyword']),
			'category_id' => $_GET['category_id'],
			'category_child' => $_GET['category_child'],
			'start_date' => base64_decode($_GET['end_date']),
			'end_date' => base64_decode($_GET['end_date']),
			'answer_count_min' => $_GET['answer_count_min'],
			'answer_count_max' => $_GET['answer_count_max'],
			'topic' => rawurldecode($_GET['topic']),
			'username' => rawurldecode($_GET['username']),
			'best_answer' => $_GET['best_answer'],
			'sort_key' => $_GET['sort_key'],
			'order' => $_GET['order'],
			'page' => intval($_GET['page']),
			'per_page' => $this->per_page,
		);
		
		$question_list = $this->model('question')->search_questions_list(false, $search_data);
		
		$totalnum = $this->model('question')->search_questions_list(true, $search_data);;
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if (isset($search_data[$key]) AND !in_array($key, array('sort_key', 'order', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/question/question_list/' . implode('__', $url_param);
		
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
		
		$this->crumb("问题管理", "admin/question/question_list/");
		
		TPL::assign('question_num', $totalnum);
		TPL::assign('search_url', $search_url);
		TPL::assign('category_list', $this->model('system')->build_category_html('question', 0, 0, null, true));
		TPL::assign('keyword', $_GET['keyword']);
		TPL::assign('list', $question_list);
		TPL::output("admin/question/question_list");
	}

	public function answer_list_action()
	{
		$question_id = intval($_GET['question_id']);
		
		if (! $question_info = $this->model("question")->get_question_info_by_id($question_id))
		{
			H::redirect_msg('问题编号错误，请返回');
		}
		
		$answer_list = $this->model('answer')->get_answer_list_by_question_id($question_id, calc_page_limit($_GET['page'], $this->per_page));
		
		$this->crumb("问题 : {$question_info['question_content']}  回复列表", "admin/question/question_list/");
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/question/answer_list/question_id-' . $question_id, 
			'total_rows' => $question_info['answer_count'], 
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
		
		TPL::assign('list', $answer_list);
		TPL::output('admin/question/answer_list');
	}

	public function question_batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (! $_POST['question_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "请先选择问题"));
		}
		
		$this->model('question')->remove_question_by_ids($_POST['question_ids']);
		
		H::ajax_json_output(AWS_APP::RSM(array(), 1, "删除成功"));
	}

	public function answer_batch_action()
	{
		define('IN_AJAX', TRUE);
		
		if (! $_POST['answer_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(nul, - 1, "请先选择回复"));
		}
		
		$this->model('answer')->remove_answers_by_ids($_POST['answer_ids']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, "删除成功"));
	}

	public function report_list_action()
	{
		if ($report_list = $this->model('question')->get_report_list('status = ' . intval($_GET['status']), null, '10'))
		{
			$userinfos = $this->model('account')->get_user_info_by_uids(fetch_array_value($report_list, 'uid'));
			
			foreach ($report_list as $key => $val)
			{
				$report_list[$key]['user'] = $userinfos[$val['uid']];
			}
		}
		
		$this->crumb('用户举报');
		TPL::assign('list', $report_list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 306));
		TPL::output('admin/question/report_list');
	}

	public function report_batch_action()
	{
		$action_type = $_POST['action_type'];
		
		if (! $_POST['report_ids'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "请先选择举报内容。"));
		}
		
		if (! $action_type)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "请先选择操作。"));
		}
		
		if ($action_type == 'delete')
		{
			foreach ($_POST['report_ids'] as $val)
			{
				$this->model('question')->delete_report($val);
			}
		}
		else if ($action_type == 'handle')
		{
			foreach ($_POST['report_ids'] as $val)
			{
				$this->model('question')->update_report($val, array(
					'status' => 1
				));
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function report_handle_ajax_action()
	{
		$this->model('question')->update_report($_GET['report_id'], array(
			'status' => 1
		));
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, ''));
	}
	
	public function approval_list_action()
	{
		if (!$_GET['type'])
		{
			$_GET['type'] = 'question';
		}
		
		switch ($_GET['type'])
		{
			case 'question':
				TPL::assign('answer_count', $this->model('publish')->count('approval', "type = 'answer'"));
			break;
			
			case 'answer':
				TPL::assign('question_count', $this->model('publish')->count('approval', "type = 'question'"));
			break;
		}
		
		if ($approval_list = $this->model('publish')->get_approval_list($_GET['type'], $_GET['page'], $this->per_page))
		{
			$found_rows = $this->model('publish')->found_rows();
			
			TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
				'base_url' => get_setting('base_url') . '/?/admin/question/approval_list/type-' . $_GET['type'], 
				'total_rows' => $found_rows, 
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
			
			foreach ($approval_list AS $key => $val)
			{
				if (!$approval_uids[$val['uid']])
				{
					$approval_uids[$val['uid']] = $val['uid'];
				}
			}
			
			TPL::assign('users_info', $this->model('account')->get_user_info_by_uids($approval_uids));
		}
		
		TPL::assign($_GET['type'] . '_count', intval($found_rows));
		
		$this->crumb('内容审核');
		
		if (get_setting('category_enable') == 'Y')
		{
			TPL::assign('category', $this->model('system')->get_category_list('question'));
		}
		
		TPL::assign('approval_list', $approval_list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 300));
		
		TPL::output('admin/question/approval_list');
	}
	
	public function approval_preview_action()
	{
		if (!$approval_item = $this->model('publish')->get_approval_item($_GET['id']))
		{
			die;
		}
		
		switch ($approval_item['type'])
		{
			case 'question':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['question_detail'])));
			break;
			
			case 'answer':
				$approval_item['content'] = nl2br(FORMAT::parse_markdown(htmlspecialchars($approval_item['data']['answer_content'])));
			break;
		}
		
		if ($approval_item['data']['attach_access_key'])
		{
			$approval_item['attachs'] = $this->model('publish')->get_attach_by_access_key($approval_item['type'], $approval_item['data']['attach_access_key']);
		}
		
		TPL::assign('approval_item', $approval_item);
		
		TPL::output('admin/question/approval_preview');
	}
	
	public function approval_batch_action()
	{
		if (!is_array($_POST['approval_ids']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '请选择要操作的条目'));
		}
		
		switch ($_POST['batch_type'])
		{
			case 'approval':
			case 'decline':
				$func = $_POST['batch_type'] . '_publish';
				
				foreach ($_POST['approval_ids'] AS $approval_id)
				{
					$this->model('publish')->$func($approval_id);
				}
			break;
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}
}