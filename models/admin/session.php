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

class admin_session_class
{
	static function get_admin_uid()
	{
		return $_SESSION['admin_login'];
	}
	
	static function init()
	{
		self::check_admin_login();
		
		TPL::import_clean();
		
		TPL::import_js(array(
			'js/jquery.js',
			'js/jquery.form.js',
			'js/functions.js',
			'js/plug_module/plug-in_module.js',
			'admin/js/admin_functions.js',
			'admin/js/global.js',
			'admin/css/date_input.css'
		));
				
		TPL::import_css(array(
			'admin/css/default/admin.css',
			'js/plug_module/style.css',
			'admin/js/jquery.date_input.js'
		), false);
	}
	
	//检查登录
	static function check_admin_login($permission = array())
	{
		$skip_controller = array(
			'login',
			'login_process_ajax',
		);
		
		if (in_array($_GET['act'], $skip_controller))
		{
			return true;
		}
		
		$admin_info = H::decode_hash($_SESSION['admin_login']);
		
		if (!($admin_info['uid'] == USER::get_client_uid() AND $admin_info['UA'] == $_SERVER['HTTP_USER_AGENT'] AND $admin_info['ip'] == $_SERVER['REMOTE_ADDR'] AND $_SESSION['permission']['is_administortar']))
		{
			unset($_SESSION['admin_login']);
			
			if ($_POST['_post_type'] == 'ajax')
			{
				die('帐户已退出，请重新登录。');
			}
			else
			{
				HTTP::redirect(get_setting('base_url') . '/?/admin/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
			}
		}
		
		unset($_POST['_post_type']);
	}
}
