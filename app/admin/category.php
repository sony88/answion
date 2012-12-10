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

class category extends AWS_CONTROLLER
{
	function get_permission_action()
	{
	
	}

	public function setup()
	{
		$this->model('admin_session')->init($this->get_permission_action());
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], 302));
	}

	public function index_action()
	{
		$this->list_action();
	}

	public function list_action()
	{
		$parent_id = intval($_GET['parent_id']);
		
		$category_list = $this->model('category')->get_category_list('question', false, $parent_id, 'sort ASC');
		
		if ($parent_id > 0)
		{
			$category_position = $this->model('category')->get_category_position($parent_id);
		}
		
		$this->crumb("分类设置", "admin/category/list/");
		
		TPL::assign('list', $category_list);
		TPL::assign('setting', get_setting());
		TPL::assign('category_position', $category_position);
		TPL::assign('category_option', $this->model('system')->build_category_html('question', 0, $parent_id, null, false));
		TPL::output("admin/category/list");
	}

	public function edit_action()
	{
		$category_id = intval($_GET['category_id']);
		
		$category = $this->model('category')->get_category_by_id($category_id);
		
		$nav_menu = $this->model('menu')->get_nav_menu_list(null, false, true);
		
		$this->crumb('分类修改', "admin/category/list/");
		
		TPL::assign('category', $category);
		TPL::assign('menu_category_ids', $nav_menu['category_ids']);
		TPL::assign('category_option', $this->model('system')->build_category_html('question', 0, $category['parent_id'], null, false));
		TPL::output('admin/category/edit');
	}

	public function sort_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		if (is_array($_POST['category']))
		{
			foreach ($_POST['category'] as $key => $val)
			{
				$this->model('category')->update_category($key, array(
					'sort' => intval($val['sort'])
				));
			}
		}
		
		if($_POST['category_enable'])
		{
			$vars = $this->model('setting')->check_vars($_POST);
			
			$this->model('setting')->set_vars($vars);
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, '1', "保存成功"));
	}

	/**
	 * 保存分类
	 */
	public function save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		$category_id = intval($_GET['category_id']);
		$title = $_POST['title'];
		$parent_id = intval($_POST['parent_id']);
		
		if ($category_id > 0 AND $parent_id > 0 AND $category_list = $this->model('system')->fetch_category('question', $category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, "系统允许最多二级分类，当前分类下有子分类，不能移动到其它分类"));
		}
		
		if (empty($title))
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "分类标题不能为空"));
		}
		
		if($_POST['url_token'])
		{
			if (!preg_match("/^(?!__)[a-zA-Z0-9_]+$/i", $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '分类别名只允许输入英文或数字'));
			}
			
			if (preg_match("/^[\d]+$/i", $_POST['url_token']) AND ($category_id != $_POST['url_token']))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '分类别名不可以全为数字'));
			}
	
			if ($this->model('category')->check_url_token($_POST['url_token'], $category_id))
			{
				H::ajax_json_output(AWS_APP::RSM(null, '-1', '分类别名已经被占用请更换一个'));
			}
		}
		
		//增加新分类
		if (! $category_id)
		{
			$category_id = $this->model('category')->add_category('question', $title, $parent_id);
		}
		
		$category = $this->model('category')->get_category_by_id($category_id);
		
		if ($_POST['add_nav_menu'])
		{
			$this->model('menu')->add_nav_menu($title, '', 'category', $category_id);
		}
		
		if ($category['id'] == $parent_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "不能设置当前分类为父级分类"));
		}
		
		$update_data = array(
			'title' => $title, 
			'parent_id' => $parent_id,
			'url_token' => $_POST['url_token'],
		);
		
		$this->model('category')->update_category($category_id, $update_data);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => get_setting('base_url') . '/' . G_INDEX_SCRIPT . '/admin/category/list'
		), 1, "修改成功"));
	}

	public function category_remove_action()
	{
		define('IN_AJAX', TRUE);
		
		$category_id = intval($_GET['category_id']);
		
		if($category_id == 1)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '默认分类不可删除。'));
		}
		
		if (! $category_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '系统错误'));
		}
		
		if($this->model('category')->question_exists($category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '分类下存在问题，请先批量移动问题到其它分类，再删除当前分类。'));
		}
		
		if ($this->model('category')->delete_category('question', $category_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '1', ''));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '删除失败'));
		}
	}
	
	public function question_move_action()
	{
		$this->crumb('问题批量移动', "admin/category/list/");
		
		TPL::assign('from_category', $this->model('system')->build_category_html('question', 0, intval($_GET['category_id'])));
		
		TPL::assign('target_category', $this->model('system')->build_category_html('question', 0, null));
		
		TPL::output('admin/category/question_move');
	}
	
	public function question_move_process_action()
	{
		$fromids = $_POST['fromids'];
		$targetid = $_POST['targetid'];
		
		$target_id = array_pop($targetid);
		
		if (!is_array($fromids) || !$target_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, '请先选择指定分类和目标分类。'));
		}
		
		if (in_array($target_id, $fromids))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '指定分类不能与目标分类相同'));
		}
		
		$this->model('question')->question_move_category($fromids, $target_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '批量移动成功。'));
	}

}