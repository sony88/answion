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
	var $versions = array();
	var $db_version = 0;
	var $db_engine = '';
	
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();
		
		return $rule_action;
	}

	public function setup()
	{
		@set_time_limit(0);
		
		// 升级程序禁止任何输入
		unset($_POST);
		
		if (is_dir(ROOT_PATH . 'plugins/aws_tinymce/'))
		{
			H::redirect_msg('1.1 版本已不支持 Tinymce 插件, 请删除 plugins/aws_tinymce/ 目录.');
		}
		
		if (!is_really_writable(AWS_PATH))
		{
			H::redirect_msg('目录 ' . AWS_PATH . ' 无法写入, 请修改目录权限');
		}
		
		/*$trash_files = array(
			ROOT_PATH . 'app/account/controller/c_login_class.inc.php',
			ROOT_PATH . 'app/account/controller/c_captcha_class.inc.php',
			ROOT_PATH . 'app/account/controller/c_register_class.inc.php',
			ROOT_PATH . 'app/account/controller/c_valid_class.inc.php',
			ROOT_PATH . 'app/account/controller/c_active_class.inc.php',
		);
		
		foreach ($trash_files AS $trash_file)
		{
			if (file_exists($trash_file))
			{
				if (!@unlink($trash_file))
				{
					$exists_trash_files[] = $trash_file;
				}
			}
		}

		if (is_array($exists_trash_files))
		{
			H::redirect_msg('老版本的一些文件已经不再使用, 请删除下列文件: <br />' . implode($exists_trash_files, '<br />'));
		}*/
		
		$this->model('upgrade')->db_clean();
		
		// 在此标记有 SQL 升级的版本号, 名称为上一个版本的 Build 编号
		$this->versions = array(
			20120608,
			20120615,
			20120622,
			20120629,
			20120706,
			20120713,
			20120719,
			20120720,
			20120727,
			20120803,
			20120810,
			20120817,
			20120824,
			20120831,
			20120921,
			20120928,
			20121012,
			20121019,
			20121026,
			20121102,
			20121109
		);
		
		if (!$this->db_version = get_setting('db_version'))
		{
			$this->db_version = 20120608;
		}
		
		if (!in_array($this->db_version, $this->versions) AND $_GET['act'] != 'final')
		{
			H::redirect_msg('您的程序已经是最新版本');
		}
		
		TPL::assign('static_url', G_STATIC_URL);
		
		$this->db_engine = get_setting('db_engine');
		
		if (!$this->db_engine)
		{
			$this->db_engine = 'MyISAM';
		}
	}
	
	public function index_action()
	{
		if ($this->user_id)
		{
			$this->model('account')->setcookie_logout();	// 清除 COOKIE
			$this->model('account')->setsession_logout();	// 清除 Session
			
			HTTP::redirect('/upgrade/');
		}
		
		TPL::assign('db_version', $this->db_version);
		TPL::output('install/upgrade');
	}
	
	public function sql_action()
	{
		$sql_file = ROOT_PATH . 'app/upgrade/db/' . str_replace('.', '', $_GET['id']) . '.sql';
			
		if (file_exists($sql_file))
		{	
			$sql_query = file_get_contents($sql_file);
		}
		
		if (trim($sql_query))
		{
			$sql_query .= "\n\nUPDATE `[#DB_PREFIX#]system_setting` SET `value` = 's:8:\"" . ($_GET['id'] + 1) . "\";' WHERE `varname` = 'db_version';";
			
			header('Content-type: text/plain; charset=UTF-8');
			
			echo str_replace(array('[#DB_PREFIX#]', '[#DB_ENGINE#]'), array(AWS_APP::config()->get('database')->prefix, $this->db_engine), $sql_query);
			die;
		}
	}
	
	public function run_action()
	{
		foreach ($this->versions AS $version)
		{
			$sql_query = null;
			
			$sql_file = ROOT_PATH . 'app/upgrade/db/' . $version . '.sql';
			
			if ($this->db_version <= $version AND file_exists($sql_file))
			{	
				$sql_query = file_get_contents($sql_file);
			}
			
			if (trim($sql_query))
			{
				if ($sql_error = $this->model('upgrade')->run_query($sql_query))
				{
					TPL::assign('sql_error', $sql_error);
					TPL::assign('version', $version);
					TPL::output('install/upgrade_fail');
					die;
				}
			}
		
			$this->model('setting')->set_vars(array(
				'db_version' => $version
			));
		}
		
		$this->model('setting')->set_vars(array(
			'db_version' => G_VERSION_BUILD
		));
		
		H::redirect_msg('升级完成, 下面开始重建数据', '/upgrade/final/case-start');
	}
	
	public function final_action()
	{
		if (!$_GET['page'])
		{
			$_GET['page'] = 1;
		}
		
		switch ($_GET['case'])
		{
			case 'start':				
				H::redirect_msg('正在进入重建数据阶段', '/upgrade/final/case-update_question_attach_statistics');
			break;
			
			case 'update_question_attach_statistics':
				if ($this->model('upgrade')->check_question_attach_statistics())
				{
					H::redirect_msg('问题附件统计重建完成, 开始重建回复附件统计', '/upgrade/final/case-update_answer_attach_statistics');
				}
				
				if ($this->model('upgrade')->update_question_attach_statistics($_GET['page'], 2500))
				{
					H::redirect_msg('正在重建问题附件统计, 批次: ' . $_GET['page'], '/upgrade/final/case-update_question_attach_statistics__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg('问题附件统计重建完成, 开始重建附件统计', '/upgrade/final/case-update_answer_attach_statistics');
				}
			break;
			
			case 'update_answer_attach_statistics':
				if ($this->model('upgrade')->check_answer_attach_statistics())
				{
					H::redirect_msg('附件统计重建完成, 开始重建动作数据', '/upgrade/final/case-upgrade_user_action_history');
				}
				
				if ($this->model('upgrade')->update_answer_attach_statistics($_GET['page'], 2500))
				{
					H::redirect_msg('正在重建附件统计, 批次: ' . $_GET['page'], '/upgrade/final/case-update_answer_attach_statistics__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg('附件统计重建完成, 开始更新最后回复数据', '/upgrade/final/case-update_last_answer');
				}
			break;
			
			case 'update_last_answer':
				if ($this->model('upgrade')->check_last_answer())
				{
					H::redirect_msg('最后回复数据更新完成, 开始更新问题热门度', '/upgrade/final/case-update_popular_value');
				}
				
				if ($this->model('upgrade')->update_last_answer($_GET['page'], 2500))
				{
					H::redirect_msg('正在更新最后回复数据, 批次: ' . $_GET['page'], '/upgrade/final/case-update_last_answer__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg('最后回复数据更新完成, 开始更新问题热门度', '/upgrade/final/case-update_popular_value');
				}
			break;
			
			case 'update_popular_value':
				if ($this->model('upgrade')->update_popular_value_answer($_GET['page'], 2000))
				{
					H::redirect_msg('正在更新问题热门度, 批次: ' . $_GET['page'], '/upgrade/final/case-update_popular_value__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg('问题热门度更新完成, 开始重建动作数据', '/upgrade/final/case-upgrade_user_action_history');
				}
			break;
			
			case 'upgrade_user_action_history':				
				if ($this->model('upgrade')->upgrade_user_action_history($_GET['page'], 5000))
				{
					H::redirect_msg('正在重建动作数据, 批次: ' . $_GET['page'], '/upgrade/final/case-upgrade_user_action_history__page-' . ($_GET['page'] + 1));
				}
				else
				{
					H::redirect_msg('动作数据重建完成', '/upgrade/final/case-final');
				}
			break;
			
			case 'final':
				H::redirect_msg('升级完成, 您的程序已经是最新版本, 如遇搜索功能异常, 请进入后台更新搜索索引<!-- Analytics --><img src="http://www.anwsion.com/analytics/?build=' . G_VERSION_BUILD . '&amp;site_name=' . urlencode(get_setting('site_name')) . '&amp;base_url=' . urlencode(get_setting('base_url')) . '&amp;php=' . PHP_VERSION . '" alt="" width="1" height="1" /><!-- / Analytics -->', '/');
			break;
		}
	}
}
