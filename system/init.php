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

define('IN_ANWSION', TRUE);
define('ENVIRONMENT_PHP_VERSION', '5.2.2');

if (version_compare(PHP_VERSION, ENVIRONMENT_PHP_VERSION, '<'))
{
	die('Error: Anwsion require PHP version ' . ENVIRONMENT_PHP_VERSION . ' or newer');
}

if (version_compare(PHP_VERSION, '6.0', '>='))
{
	die('Error: Anwsion not support PHP version 6 currently');
}

define('START_TIME', microtime(TRUE));

if (function_exists('memory_get_usage'))
{
	define('MEMORY_USAGE_START', memory_get_usage());
}

error_reporting(E_ALL & ~E_NOTICE | E_STRICT);

define('AWS_PATH', dirname(__FILE__) . '/');
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
define('TEMP_PATH', dirname(dirname(__FILE__)) . '/tmp/');

if (function_exists('get_magic_quotes_gpc'))
{
	if (@get_magic_quotes_gpc()) // GPC 进行反向处理
	{
		if (! function_exists('stripslashes_gpc'))
		{
			function stripslashes_gpc(&$value)
			{
				$value = stripslashes($value);
			}
		
			array_walk_recursive($_GET, 'stripslashes_gpc');
			array_walk_recursive($_POST, 'stripslashes_gpc');
			array_walk_recursive($_COOKIE, 'stripslashes_gpc');
			array_walk_recursive($_REQUEST, 'stripslashes_gpc');
		}
	}
}

if (@ini_get('register_globals'))
{
	if ($_REQUEST)
	{
		foreach ($_REQUEST AS $name => $value)
		{
			unset($$name);
		}
	}
}

require_once(ROOT_PATH . 'version.php');
require_once(AWS_PATH . 'functions.inc.php');

if (file_exists(AWS_PATH . 'gz_config.inc.php'))
{
	rename(AWS_PATH . 'gz_config.inc.php', AWS_PATH . 'config.inc.php');
}

if (file_exists(AWS_PATH . 'config.inc.php'))
{
	require_once(AWS_PATH . 'config.inc.php');
}

load_class('core_autoload');

date_default_timezone_set('Etc/GMT-8');