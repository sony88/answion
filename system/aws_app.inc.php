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

class AWS_APP
{
	private static $config;
	private static $db;
	private static $form;
	private static $upload;
	private static $image;
	private static $pagination;
	private static $cache;
	
	private static $models = array();
	private static $plugins = array();
	private static $setting = array();
	
	public static $_debug = array();
	
	/**
	 * 程序开始执行,查找控制器和动作
	 * 
	 * @param string $app_dir 应用的绝对目录如果没有,就是当前目录
	*/
	public static function run()
	{
		// 全局变量
		global $__controller, $__action, $__default_controller, $__default_action;
		
		self::init();
		
		if (!$app_dir = load_class('core_uri')->set_rewrite()->app_dir)
		{
			$app_dir = ROOT_PATH . 'app/home/';
		}
		
		// 控制器操作进行选择
		if (! $__controller)
		{
			if (isset($_GET['c']))
			{
				$__controller = $_GET['c']; // 有传入控制器字段名
			}
			else
			{
				$__controller = $__default_controller ? $__default_controller : 'main'; // 读取默认控制器字段名
			}
		}
		
		if (! $__action)
		{
			// 动作		
			if (isset($_GET['act']))
			{
				$__action = $_GET['act']; // 有传入动作字段名
			}
			else
			{
				$__action = $__default_action ? $__default_action : 'act'; // 读取默认动作字段名
			}
		}

		// 传入应用目录,返回控制器路径
		
		$handle_controller = self::create_controller($__controller, $app_dir);
		
		$action_method = $__action . '_action';
		
		// 判断
		if (! is_object($handle_controller) || ! method_exists($handle_controller, $action_method))
		{
			HTTP::error_404();
		}
		
		// 判断 ACTION
		if (method_exists($handle_controller, 'get_access_rule'))
		{
			$access_rule = $handle_controller->get_access_rule();
		}
		
		// 判断使用白名单还是黑名单,默认使用黑名单
		if ($access_rule)
		{			
			// 黑名单,黑名单中的检查  'white'白名单,白名单以外的检查(默认是黑名单检查)
			if (isset($access_rule['rule_type']) && ($access_rule['rule_type'] == 'white')) // 白
			{
				if ((! $access_rule['actions']) || (! in_array($__action, $access_rule['actions'])))
				{
					self::login_check();
				}
			}
			else if (isset($access_rule['actions']) && in_array($__action, $access_rule['actions'])) // 非白就是黑名单
			{
				self::login_check();
			}
		
		}
		else // 没有设置就全部检查
		{
			self::login_check();
		}
		
		// 执行
		$handle_controller->$action_method();
	}

	private static function init()
	{		
		self::$config = load_class('core_config');
		self::$db = load_class('core_db');
		self::$plugins = load_class('core_plugins');
		self::$setting = self::model('setting')->get_setting();
		
		if (get_setting('default_timezone'))
		{
			date_default_timezone_set(get_setting('default_timezone'));
		}

		$img_url = get_setting('img_url');
		
		$base_url = get_setting('base_url');
		
		! empty($img_url) ? define('G_STATIC_URL', $img_url) : define('G_STATIC_URL', $base_url . '/static');
		
		
		if (AWS_APP::config()->get('system')->debug)
		{
			$cornd_db_file = TEMP_PATH . 'cornd_db.php';
		
			if (file_exists($cornd_db_file))
			{
				$cornd_db = unserialize(file_get_contents($cornd_db_file));
			}
			
			if (is_array($cornd_db))
			{
				foreach ($cornd_db AS $cornd_tag => $run_time)
				{
					AWS_APP::debug_log('crond', 0, 'Tag: ' . $cornd_tag . ', Last run time: ' . date('Y-m-d H:i:s', $run_time));
				}
			}
		}
	}
	
	public static function setting()
	{
		if (!self::$setting)
		{
			return false;
		}
		
		return self::$setting;
	}

	/**
	 * 建立控制器
	 * 返回控制器类对象
	 * @param string $route
	 */
	public static function create_controller($controller, $app_dir)
	{
		if (trim($app_dir) == '')
		{
			return false; // 没有应用目录,返回错误
		}
		
		if (($controller = trim($controller, '/')) === '')
		{
			$controller = 'main';
		}
		
		$class_file = $app_dir . $controller . '.php';
		
		if (is_file($class_file))
		{
			if (! class_exists($controller, false))
			{
				require_once ($class_file);
			}
			
			if (class_exists($controller, false))
			{
				return new $controller();
			}
		}
		
		return false;
	}

	
	/**
   	* 格式化弹出信息组件返回值
   	* 
   	* @param $rsm 		结果数据,可以为空
   	* @param $errno		错误代码，默认为 0 无错误，其它值为相应的错误代码
   	* @param $err		错误信息
   	* @param $level 	错误级别，默认为 0 ， $err 将直接显示给用户看，如果为 1 则不显示给用户看，统一为提示为  系统繁忙，请稍后再试...
   	* @param $log		当数据层需要 组件管理中心 写日志时，给出值，默认为空，不写日志
   	* 
   	* @return 返回标准的RSM数据
   	*/
	public static function RSM($rsm, $errno = 0, $err = "", $level = 0, $log = "")
	{
		return array(
			'rsm' => $rsm, 
			'errno' => (int)$errno, 
			'err' => $err, 
			'level' => $level, 
			'log' => $log
		);
	}
		
	public static function login_check()
	{
		if (! USER::is_user_login())
		{
			HTTP::redirect('/account/login/url-' . base64_encode($_SERVER['REQUEST_URI']));
		}
	}

	public static function config()
	{
		return self::$config;
	}
	
	public static function upload()
	{
		if (!self::$upload)
		{
			self::$upload = load_class('core_upload');
		}
		
		return self::$upload;
	}
	
	public static function image()
	{
		if (!self::$image)
		{
			self::$image = load_class('core_image');
		}
		
		return self::$image;
	}
	
	public static function cache()
	{
		if (!self::$cache)
		{
			self::$cache = load_class('core_cache');
		}
		
		return self::$cache;
	}
		
	public static function form()
	{
		if (!self::$form)
		{
			self::$form = load_class('core_form');
		}
		
		return self::$form;
	}
	
	public static function plugins()
	{
		return self::$plugins;
	}
	
	public static function pagination()
	{
		if (!self::$pagination)
		{
			self::$pagination = load_class('core_pagination');
		}
		
		return self::$pagination;
	}
	
	public static function db($db_object_name = 'master')
	{
		if (!self::$db)
		{
			return false;
		}
		
		return self::$db->setObject($db_object_name);
	}
	
	public static function debug_log($type, $expend_time, $message)
	{
		self::$_debug[$type][] = array(
			'expend_time' => $expend_time,
			'log_time' => microtime(true),
			'message' => $message
		);
	}

	public static function model($model_class = null)
	{
		if (!$model_class)
		{
			$model_class = 'AWS_MODEL';
		}
		else if (! strstr($model_class, '_class'))
		{
			$model_class .= '_class';
		}
		
		if (! isset(self::$models[$model_class]))
		{
			$model = new $model_class();
			self::$models[$model_class] = $model;
		}
		
		return self::$models[$model_class];
	}
}