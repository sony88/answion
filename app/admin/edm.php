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

class edm extends AWS_CONTROLLER
{
	var $per_page = 20;
	
	function get_permission_action()
	{
	
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
		
		$this->crumb('邮件群发', "admin/edm/");
	}

	public function groups_action()
	{
		$groups_list = $this->model('edm')->fetch_groups($_GET['page'], $this->per_page);
		$total_rows = $this->model('edm')->found_rows();
		
		if ($groups_list)
		{
			foreach ($groups_list AS $key => $val)
			{
				$groups_list[$key]['users'] = $this->model('edm')->calc_group_users($val['id']);
			}
		}
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/edm/groups/', 
			'total_rows' => $total_rows, 
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
		
		TPL::assign('groups_list', $groups_list);
		
		TPL::assign('reputation_user_group', $this->model('account')->get_user_group_list(1));
		TPL::assign('system_user_group', $this->model('account')->get_user_group_list(0));
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 701));
		
		TPL::output('admin/edm/groups');
	}
	
	public function add_group_action()
	{
		@set_time_limit(0);
		
		if (trim($_POST['title']) == '')
		{
			H::redirect_msg('请填写用户群名称');
		}
				
		$usergroup_id = $this->model('edm')->add_group($_POST['title']);
				
		switch ($_POST['import_type'])
		{
			case 'text':
				if ($email_list = explode("\n", str_replace(array("\r", "\t"), "\n", $_POST['email'])))
				{
					foreach ($email_list AS $key => $email)
					{
						$this->model('edm')->add_user_data($usergroup_id, $email);
					}
				}
			break;
			
			case 'system_group':
				if ($_POST['user_groups'])
				{
					foreach ($_POST['user_groups'] AS $key => $val)
					{
						$this->model('edm')->import_system_email_by_user_group($usergroup_id, $val);
					}
				}
			break;
			
			case 'reputation_group':
				if ($_POST['user_groups'])
				{
					foreach ($_POST['user_groups'] AS $key => $val)
					{
						$this->model('edm')->import_system_email_by_reputation_group($usergroup_id, $val);
					}
				}
			break;
			
			case 'last_active':
				if ($_POST['last_active'])
				{
					$this->model('edm')->import_system_email_by_last_active($usergroup_id, $_POST['last_active']);
				}
			break;
		}
		
		H::redirect_msg('用户群添加完成', get_setting('base_url') . '/?/admin/edm/groups/');
	}
	
	public function add_task_action()
	{
		if (trim($_POST['title']) == '')
		{
			H::redirect_msg('请填写任务名称');
		}
					
		if (trim($_POST['subject']) == '')
		{
			H::redirect_msg('请填写邮件标题');
		}
					
		if (intval($_POST['usergroup_id']) == 0)
		{
			H::redirect_msg('请选择用户群组');
		}
					
		if (trim($_POST['from_name']) == '')
		{
			$_POST['from_name'] = get_setting('site_name');
		}
					
		$task_id = $this->model('edm')->add_task($_POST['title'], $_POST['subject'], $_POST['message'], $_POST['from_name']);
		
		$this->model('edm')->import_group_data_to_task($task_id, $_POST['usergroup_id']);
		
		H::redirect_msg('任务建立完成', get_setting('base_url') . '/?/admin/edm/tasks/');
					
	}
	
	public function remove_task_action()
	{
		$this->model('edm')->remove_task($_GET['id']);
		
		H::redirect_msg('任务已删除', get_setting('base_url') . '/?/admin/edm/tasks/');
	}
	
	public function remove_group_action()
	{
		$this->model('edm')->remove_group($_GET['id']);
		
		H::redirect_msg('用户群已删除', get_setting('base_url') . '/?/admin/edm/groups/');
	}
	
	public function tasks_action()
	{
		$tasks_list = $this->model('edm')->fetch_tasks($_GET['page'], $this->per_page);
		$total_rows = $this->model('edm')->found_rows();
		
		if ($tasks_list)
		{
			foreach ($tasks_list AS $key => $val)
			{
				$tasks_list[$key]['users'] = $this->model('edm')->calc_task_users($val['id']);
				$tasks_list[$key]['views'] = $this->model('edm')->calc_task_views($val['id']);
				$tasks_list[$key]['sent'] = $this->model('edm')->calc_task_sent($val['id']);
			}
		}
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_setting('base_url') . '/?/admin/edm/tasks/', 
			'total_rows' => $total_rows, 
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
		
		TPL::assign('tasks_list', $tasks_list);
		
		TPL::assign('usergroups', $this->model('edm')->fetch_groups());
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 702));
		
		TPL::output('admin/edm/tasks');
	}
	
	public function export_active_users_action()
	{
		if ($export = $this->model('edm')->fetch_task_active_emails($_GET['id']))
		{
			HTTP::force_download_header('export.txt');
			
			foreach ($export AS $key => $data)
			{
				echo $data['email'] . "\r\n";
			}
		}
		else
		{
			H::redirect_msg('没有活跃用户');
		}
	}
}