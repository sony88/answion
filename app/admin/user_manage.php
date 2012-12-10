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

class user_manage extends AWS_CONTROLLER
{
	var $per_page = 15;

	function get_permission_action()
	{
	
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
	}

	public function index_action()
	{
		$this->list_action();
	}

	public function list_action()
	{
		$_GET['sort_key'] = isset($_GET['sort_key']) ? $_GET['sort_key'] : 'uid';
		$_GET['order'] = isset($_GET['order']) ? $_GET['order'] : 'DESC';
		
		$search_data = array(
			'action' => $_GET['action'],
			'user_name' => rawurldecode($_GET['user_name']),
			'email' => rawurldecode($_GET['email']),
			'reg_date' => base64_decode($_GET['reg_date']),
			'group_id' => $_GET['group_id'],
			'last_login_date' => base64_decode($_GET['last_login_date']),
			'ip' => $_GET['ip'],
			'integral_min' => $_GET['integral_min'],
			'integral_max' => $_GET['integral_max'],
			'reputation_min' => $_GET['reputation_min'],
			'reputation_max' => $_GET['reputation_max'],
			'answer_count_min' => $_GET['answer_count_min'],
			'answer_count_max' => $_GET['answer_count_max'],
			'job_id' => $_GET['job_id'],
			'province' => $_GET['province'],
			'city' => $_GET['city'],
			'birthday' => base64_decode($_GET['birthday']),
			'signature' => rawurldecode($_GET['signature']),
			'common_email' => $_GET['common_email'],
			'mobile' => $_GET['mobile'],
			'qq' => $_GET['qq'],
			'homepage' => $_GET['homepage'],
			'school_name' => rawurldecode($_GET['school_name']),
			'departments' => rawurldecode($_GET['departments']),
			'company_name' => rawurldecode($_GET['company_name']),
			'company_job_id' => $_GET['company_job_id'],
			'sort_key' => $_GET['sort_key'],
			'order' => $_GET['order'],
			'page' => $_GET['page'],
			'per_page' => $this->per_page,
		);
		
		if ($_POST['action'] == 'save_users_search')	//保存当前用户搜索列表
		{
			unset($search_data['action']);
			unset($search_data['limit']);
			
			if ($_POST['users_search_id'] > 0)
			{
				$this->model('account')->update_users_search($_POST['users_search_id'], $search_data);
				H::ajax_json_output(AWS_APP::RSM(null, 1, '保存成功'));
			}
			else
			{
				if ($this->model('account')->get_users_search_by_name($_POST['name']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, '列表名已存在，请使用其它名称'));
				}
				
				$this->model('account')->save_users_search($_POST['name'], $search_data);
			}
				
			H::ajax_json_output(AWS_APP::RSM(null, 1, ''));
		}
		else if ($_POST['action'] == 'search')
		{
			foreach ($_POST as $key => $val)
			{
				if (in_array($key, array('reg_date', 'last_login_date', 'end_date', 'birthday')))
				{
					$val = base64_encode($val);
				}
				
				if (in_array($key, array('user_name', 'email', 'signature', 'school_name', 'departments', 'company_name')))
				{
					$val = rawurlencode($val);
				}
				
				$param[] = $key . '-' . $val;
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_setting('base_url') . '/?/admin/user_manage/list/' . implode('__', $param)
			), 1, null));
		}
		
		$user_list = $this->model('account')->get_users_list_by_search(false, $search_data);
		
		$total_rows = $this->model('account')->get_users_list_by_search(true, $search_data);
		
		$url_param = array();
		
		foreach($_GET as $key => $val)
		{
			if (isset($search_data[$key]) AND !in_array($key, array('sort_key', 'order', 'page')))
			{
				$url_param[] = $key . '-' . $val;
			}
		}
		
		$search_url = 'admin/user_manage/list/' . implode('__', $url_param);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/' . $search_url . '__sort_key-' . $_GET['sort_key'] . '__order-' . $_GET['order'], 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page, 
			'last_link' => '末页', 
			'first_link' => '首页', 
			'next_link' => '下一页 »', 
			'prev_link' => '« 上一页', 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		$this->crumb("会员列表", "admin/user_manage/list/");
		
		TPL::import_js('js/LocationSelect.js');
		
		TPL::assign('users_search_list', $this->model('account')->get_users_search_list());
		TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		TPL::assign('search_url', $search_url);
		TPL::assign('total_rows', $total_rows);
		TPL::assign('list', $user_list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 402));
		TPL::output('admin/user_manage/list');
	}
	
	public function delete_users_search_action()
	{
		$this->model('account')->delete_users_search_by_id($_POST['id']);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '删除成功'));
	}
	
	public function group_list_action()
	{
		$this->crumb("用户组管理", "admin/user_manage/group_list/");
		
		TPL::assign('mem_group', $this->model('account')->get_user_group_list(1));
		TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 403));
		TPL::output('admin/user_manage/group_list');
	}

	public function group_save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		if ($group_data = $_POST['group'])
		{
			foreach ($group_data as $key => $val)
			{
				if (empty($val['group_name']))
				{
					H::ajax_json_output(AWS_APP::RSM(null, "-1", "用户组名称不能为空"));
				}
				
				if ($val['reputation_factor'])
				{
					if (!is_numeric($val['reputation_factor']) || floatval($val['reputation_factor']) < 0)
					{
						H::ajax_json_output(AWS_APP::RSM(null, "-1", "威望系数必须为大于或等于 0 的数字"));
					}
					
					if (!is_numeric($val['reputation_lower']) || floatval($val['reputation_lower']) < 0 || !is_numeric($val['reputation_higer']) || floatval($val['reputation_higer']) < 0)
					{
						H::ajax_json_output(AWS_APP::RSM(null, "-1", "威望介于值必须为大于或等于 0 的数字"));
					}
					
					$val['reputation_factor'] = floatval($val['reputation_factor']);
				}
				
				$this->model('account')->update_group($key, $val);
			}
		}
		
		if ($group_new = $_POST['group_new'])
		{
			foreach ($group_new['group_name'] as $key => $val)
			{
				if (trim($group_new['group_name'][$key]))
				{
					$this->model('account')->add_group($group_new['group_name'][$key], $group_new['reputation_lower'][$key], $group_new['reputation_higer'][$key], $group_new['reputation_factor'][$key]);
				}
			}
		}
		
		if ($group_ids = $_POST['group_ids'])
		{
			foreach ($group_ids as $key => $id)
			{
				$group_info = $this->model('account')->get_group_by_id($id);
				
				if ($group_info['type'] == 1)
				{
					$this->model('account')->delete_group($id);
				}
				else
				{
					H::ajax_json_output(AWS_APP::RSM(null, "-1", "系统用户组不可删除"));
				}
			}
		}
		
		if ($group_new || $group_ids)
		{
			$rsm_array = array(
				'url' => get_js_url('admin/user_manage/group_list/')
			);
		}
		
		H::ajax_json_output(AWS_APP::RSM($rsm_array, "1", "用户组更新成功"));
	}

	public function group_edit_action()
	{
		if (! $group = $this->model('account')->get_group_by_id(intval($_GET['group_id'])))
		{
			H::redirect_msg('用户组不存在');
		}
		
		$this->crumb("用户组编辑", "admin/user_manage/group_list/");
		
		TPL::assign('group', $group);
		TPL::assign('group_pms', $group['permission']);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 403));
		TPL::output('admin/user_manage/group_edit');
	}

	/**
	 * 保存用户组编辑
	 */
	public function group_edit_process_action()
	{
		$permission_array = array(
			'is_administortar', 
			'is_moderator', 
			'publish_question',
			'publish_approval',
			'publish_approval_time',
			'edit_question', 
			'edit_topic', 
			'redirect_question', 
			'upload_attach', 
			'publish_url', 
			'human_valid', 
			'question_valid_hour', 
			'answer_valid_hour', 
			'visit_site', 
			'visit_explore', 
			'search_avail', 
			'visit_question', 
			'visit_topic', 
			'visit_feature', 
			'visit_people',  
			'answer_show', 
		);
		
		$group_setting = array();
		
		foreach ($permission_array as $permission)
		{
			if ($_POST[$permission])
			{
				$group_setting[$permission] = $_POST[$permission];
			}
		}
		
		$retval = $this->model('account')->update_group($_GET['group_id'], array(
			'permission' => serialize($group_setting)
		));
		
		H::ajax_json_output(AWS_APP::RSM(null, "1", "用户组更新成功"));
	}

	/**
	 * 修改用户资料
	 */
	public function edit_action()
	{
		$this->crumb("编辑用户资料", "admin/user_manage/list/");
		
		TPL::assign('system_group', $this->model('account')->get_user_group_list(0));
		TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid'], TRUE));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 402));
		TPL::output("admin/user_manage/edit");
	}

	/**
	 * 用户修改处理
	 */
	public function user_save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		$user_id = intval($_POST['uid']);
		
		if ($user_id)
		{
			unset($_POST['uid']);
			
			if ($user_id == 0)
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", "系统错误，用户 ID 不能为空"));
			}
			
			$user_info = $this->model('account')->get_user_info_by_uid($user_id);
			
			if ($_POST['user_name'] != $user_info['user_name'] && $this->model('account')->get_user_info_by_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, "-1", "用户名已存在"));
			}

			foreach($_POST as $key => $val)
			{
				if (empty($val))
				{
					continue;
				}
			
				if (in_array($key, array('reg_time', 'last_login', 'birthday')))
				{
					$update_user[$key] = strtotime($val);
				}
				else if (in_array($key, array('reg_ip', 'last_ip')))
				{
					$update_user[$key] = ip2long($val);
				}
				else
				{
					$update_user[$key] = htmlspecialchars($val);
				}
			}
			
			$update_user['verified'] = isset($_POST['verified']) ? 1 : 0;
			
			if ($update_user['delete_avatar'])
			{
				$this->model('account')->delete_avatar($user_id);
				
				unset($update_user['delete_avatar']);
			}
			
			if ($this->user_info['group_id'] != '1')
			{
				unset($update_user['group_id']);
			}
			
			if (! empty($update_user['password']))
			{
				$this->model('account')->update_user_password_ingore_oldpassword($update_user['password'], $user_id, fetch_salt(4));
			}
			
			if ($user_info['group_id'] == 3 && $update_user['valid_email'] == 1)
			{
				$update_user['group_id'] = 4;
			}
			
			unset($update_user['password']);
			
			$this->model('account')->update_users_attrib_fields(array(
				'signature' => htmlspecialchars($update_user['signature'])
			), $user_id);
			
			unset($update_user['signature']);
			
			$this->model('account')->update_users_fields($update_user, $user_id);
			
			if ($_POST['user_name'] != $user_info['user_name'])
			{
				$this->model('account')->update_user_name($_POST['user_name'], $user_id);
			}
			
			H::ajax_json_output(AWS_APP::RSM(null, "1", "用户资料修改成功"));
		}
		else
		{
			//检查用户名
			if (trim($_POST['user_name']) == '')
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "请输入用户名"));
			}
			
			if ($this->model('account')->check_username($_POST['user_name']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "用户名已经存在"));
			}
			
			if ($this->model('account')->check_email($_POST['email']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "E-Mail 已经被使用, 或格式不正确"));
			}
			
			if (strlen($_POST['password']) < 6 or strlen($_POST['password']) > 16)
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "密码长度不符合规则"));
			}
			
			if ($uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email'], true))
			{
				H::ajax_json_output(AWS_APP::RSM(null, 1, "用户添加成功。"));
			}
			else
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "用户添加失败。"));
			}
		
		}
	}
	
	/**
	 * 设置会员状态
	 */
	public function forbidden_status_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		$this->model('account')->forbidden_user($_GET['user_id'], $_GET['status'], $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, "1", null));
	}

	/**
	 * 列出在线列表
	 */
	public function online_list_action()
	{
		$online_users = $this->model('online')->get_db_online_users(false, null, calc_page_limit($_GET['page'], $this->per_page));
		
		$total_count = $this->model('online')->get_db_online_users(true);
		
		$online_users = $this->online_users_format($online_users);
		
		$this->crumb("在线会员", "admin/user_manage/online_list/");
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/user_manage/online_list/', 
			'total_rows' => $total_count, 
			'per_page' => $this->per_page, 
			'last_link' => '末页', 
			'first_link' => '首页', 
			'next_link' => '下一页 »', 
			'prev_link' => '« 上一页', 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		TPL::assign('list', $online_users);
		TPL::assign('total_count', $total_count);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 401));
		TPL::output('admin/user_manage/online');
	}

	/**
	 * 在线会员列表格式化
	 * @param unknown_type $online_users
	 */
	public function online_users_format($online_users)
	{
		if (is_array($online_users))
		{
			foreach ($online_users as $user)
			{
				$uids[] = $user['uid'];
			}
		}
		
		if (empty($uids))
		{
			return $online_users;
		}
		
		$uids = array_unique($uids);
		
		if ($_user_infos = $this->model('account')->get_user_info_by_uids($uids))
		{
			$user_infos = array();
			
			foreach ($_user_infos as $user)
			{
				$user_infos[$user['uid']] = $user;
			}
		}
		
		foreach ($online_users as $key => $val)
		{
			$online_users[$key]['userinfo'] = $user_infos[$val['uid']];
		}
		
		return $online_users;
	}

	public function user_add_action()
	{
		$this->crumb("添加用户", "admin/user_manage/user_add/");
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 405));
		TPL::output('admin/user_manage/add');
	}

	public function invites_action()
	{
		$this->crumb("批量邀请", "admin/user_manage/invites/");
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 406));
		TPL::output('admin/user_manage/invites');
	}

	public function invites_ajax_action()
	{
		if ($_POST['email_list'] && $emails = explode("\n", str_replace("\r", "\n", $_POST['email_list'])))
		{
			foreach($emails as $key => $email)
			{
				if (($email = trim($email)) == '')
				{
					continue;
				}
				
				if (!H::valid_email($email))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, $email . ' 不是合法邮箱地址。'));
				}
				
				$email_list[] = strtolower($email);
			}
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '邮箱地址不能为空。'));
		}
		
		if ($this->model('invitation')->send_batch_invitations(array_unique($email_list), $this->user_id, $this->user_info['user_name']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, '邀请成功。'));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '邀请发送失败。'));
		}
	}

	public function job_list_action()
	{
		TPL::assign('job_list', $this->model('work')->get_jobs_list());
		
		$this->crumb("职位设置", "admin/user_manage/job_list/");
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 407));
		TPL::output('admin/user_manage/job_list');
	}
	
	public function remove_job_action()
	{
		if ($this->model('work')->remove_job(intval($_GET['job_id'])))
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, ''));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '删除失败。'));
		}
	}
	
	public function add_job_ajax_action()
	{
		if (!$_POST['jobs'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请输入职位名称'));
		}
		
		$job_list = array();
		
		if ($job_list_tmp = explode("\n", $_POST['jobs']))
		{
			foreach($job_list_tmp as $key => $job)
			{
				$job_name = trim(strtolower($job));
				
				if (!empty($job_name))
				{
					$job_list[] = $job_name;
				}
			}
		}
		else
		{
			$job_list[] = $_POST['jobs'];
		}
		
		foreach($job_list as $key => $val)
		{
			$this->model('work')->add_job($val);
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '添加成功'));
	}
	
	public function save_job_ajax_action()
	{
		if ($_POST['job_list'])
		{
			foreach($_POST['job_list'] as $key => $val)
			{
				$this->model('work')->update_job($key, array(
					'job_name' => $val,
				));
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '保存成功'));
	}
	
	public function integral_action()
	{
		$this->crumb("增加用户积分", "admin/user_manage/tegral/");
		TPL::assign('user', $this->model('account')->get_user_info_by_uid($_GET['uid']));
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 407));
		TPL::output('admin/user_manage/integral');
	}
	
	public function integral_add_ajax_action()
	{
		if (!$_POST['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请选择用户。'));
		}
		
		$integral = $_POST['integral'];
		
		if ($integral <= 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '增加积分数值不正确。'));
		}
		
		if (!$_POST['note'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请填写理由。'));
		}
		
		$this->model('integral')->process($_POST['uid'], 'AWARD', $integral, $_POST['note']);
		
		H::ajax_json_output(AWS_APP::RSM(array('url' => get_setting('base_url') . '/?/admin/user_manage/integral/uid-' . $_POST['uid']), 1, '增加积分成功。'));
	}
	
	public function forbidden_list_action()
	{
		$list = $this->model('account')->get_forbidden_user_list(false, 'uid DESC', calc_page_limit($_GET['page'], $this->per_page));
		
		$total_rows = $this->model('account')->get_forbidden_user_list(true);
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/user_manage/forbidden_list/', 
			'total_rows' => $total_rows, 
			'per_page' => $this->per_page, 
			'last_link' => '末页', 
			'first_link' => '首页', 
			'next_link' => '下一页 »', 
			'prev_link' => '« 上一页', 
			'anchor_class' => ' class="number"', 
			'cur_tag_open' => '<a class="number current">', 
			'cur_tag_close' => '</a>', 
			'direct_page' => TRUE
		))->create_links());
		
		$this->crumb('禁封用户', 'admin/user_manage/forbidden_list/');
		
		TPL::assign('total_rows', $total_rows);
		TPL::assign('list', $list);
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 408));
		TPL::output('admin/user_manage/forbidden_list');
	}
}