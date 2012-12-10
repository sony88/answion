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

class setting extends AWS_CONTROLLER
{

	function get_permission_action()
	{
	
	}

	public function setup()
	{
		admin_session_class::init($this->get_permission_action());
	}

	public function index_action()
	{
		$this->setting_action();
	}

	public function setting_action()
	{
		$menu = array(
			101 => 'site_info',
			102 => 'reg_visit',
			103 => 'site_func',
			104 => 'content',
			106 => 'integral_reputation', 
			107 => 'email', 
			108 => 'open', 
			109 => 'cache', 
			110 => 'sys_info', 
			201 => 'view_set', 
			305 => 'sensitive_words', 
			404 => 'user_reputation', 
			111 => 'user_privilege',
			203 => 'editor',
			603 => 'today_topics',
		);
		
		$type = $_GET['type'];
		
		if (!in_array($type, $menu))
		{
			HTTP::redirect('?/admin/main/');
		}
		
		if ($type == 'view_set')
		{
			TPL::assign('styles', $this->model('setting')->get_ui_styles());
		}
		
		$this->crumb('系统设置', 'admin/setting/type-' . $type);
		
		TPL::import_js('admin/js/setting.js');
		
		TPL::assign('setting', get_setting());
		
		TPL::assign('menu_list', $this->model('admin_group')->get_menu_list($this->user_info['group_id'], array_search($type, $menu)));
		
		TPL::output("admin/setting/" . $type);
	}


	/**
	 * 保存设置
	 */
	public function sys_save_ajax_action()
	{
		define('IN_AJAX', TRUE);
		
		if ($_POST['upload_dir'] && preg_match('/(.*)\/$/i', $_POST['upload_dir']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "上传文件存放绝对路径结尾不带 /"));
		}
		
		if ($_POST['upload_url'] && preg_match('/(.*)\/$/i', $_POST['upload_url']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "上传目录外部访问 URL 地址结尾不带 /"));
		}
		
		if ($_POST['request_route_custom'])
		{
			$_POST['request_route_custom'] = trim($_POST['request_route_custom']);
			
			if ($request_routes = explode("\n", $_POST['request_route_custom']))
			{
				foreach ($request_routes as $key => $val)
				{
					if (! strstr($val, '==='))
					{
						continue;
					}
					
					list($m, $n) = explode('===', $val);
					
					if (substr($n, 0, 1) != '/' || substr($m, 0, 1) != '/')
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, "URL 自定义路由规则错误：URL 必须以 / 开头"));
					}
					
					if (strstr($m, '/admin') || strstr($n, '/admin'))
					{
						H::ajax_json_output(AWS_APP::RSM(null, -1, "URL 自定义路由规则不允许设置 /admin 路由"));
					}
				}
			}
		}
		
		if ($_POST['censoruser'])
		{
			$_POST['censoruser'] = trim($_POST['censoruser']);
		}
		
		if ($_POST['report_reason'])
		{
			$_POST['report_reason'] = trim($_POST['report_reason']);
		}
		
		if ($_POST['sensitive_words'])
		{
			$_POST['sensitive_words'] = trim($_POST['sensitive_words']);
		}
		
		$curl_require_setting = array('qq_login_enabled', 'sina_weibo_enabled', 'qq_t_enabled');
		
		if (array_intersect(array_keys($_POST), $curl_require_setting))
		{
			foreach($curl_require_setting as $key)
			{
				if ($_POST[$key] == 'Y' && !function_exists('curl_init'))
				{
					H::ajax_json_output(AWS_APP::RSM(null, -1, "微博登录、QQ 登录等功能须服务器支持 CURL"));
				}
			}
		}
		
		if (!$_POST['newer_content_type'])
		{
			$_POST['newer_content_type'] = array();
		}

		$vars = $this->model('setting')->check_vars($_POST);	//过滤参数
		
		$retval = $this->model('setting')->set_vars($vars);		//保存参数到数据库
		
		if ($retval)
		{
			H::ajax_json_output(AWS_APP::RSM(null, "1", "系统设置修改成功"));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, "-1", "系统设置修改失败"));
		}
	}

	public function test_email_setting_action()
	{
		define('IN_AJAX', TRUE);
		
		$smtp_config = array(
			'smtp_server' => $_POST['smtp_server'], 
			'smtp_ssl' => $_POST['smtp_ssl'], 
			'smtp_port' => $_POST['smtp_port'], 
			'smtp_username' => $_POST['smtp_username'], 
			'smtp_password' => $_POST['smtp_password']
		);
		
		$core_mail = new core_mail();
		
		$connect_result = $core_mail->connect($_POST['email_type'], $smtp_config);
		
		if (is_string($connect_result))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, "邮件服务器连接失败, 返回的信息: " . strip_tags($connect_result)));
		}
		
		$sendmail_result = $core_mail->send_mail($_POST['from_email'], get_setting('site_name'), $_POST['test_email'], $_POST['test_email'], get_setting('site_name') . " - 邮箱服务器配置测试", '这是一封测试邮件，收到邮件表明网站邮箱服务器配置成功');
		
		if (is_object($sendmail_result))
		{			
			H::ajax_json_output(AWS_APP::RSM(null, 1, "测试邮件已发送, 请查收邮件以确定配置是否正确"));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(null, 1, "测试邮件发送失败, 返回的信息: " . strip_tags($sendmail_result)));
		}
	}
}