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

/**
 * 根据特定规则对数据进行排序
 *
 * @return array
 */
function aasort($source_array, $order_field, $sort_type)
{
	if (! is_array($source_array) or sizeof($source_array) == 0)
	{
		return false;
	}
	
	foreach ($source_array as $array_key => $array_row)
	{
		$sort_array[$array_key] = $array_row[$order_field];
	}
	
	$sort_func = ($sort_type == 'ASC' ? 'asort' : 'arsort');
	
	$sort_func($sort_array);
	
	// 重组数组
	foreach ($sort_array as $key => $val)
	{
		$sorted_array[$key] = $source_array[$key];
	}
	
	return $sorted_array;
}

/**
 * 获取用户 IP
 *
 * @return string
 */
function fetch_ip()
{
	if ($_SERVER['HTTP_X_FORWARDED_FOR'] and valid_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else if ($_SERVER['REMOTE_ADDR'] and $_SERVER['HTTP_CLIENT_IP'])
	{
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}
	else if ($_SERVER['REMOTE_ADDR'])
	{
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}
	else if ($_SERVER['HTTP_CLIENT_IP'])
	{
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}
	
	if ($ip_address === FALSE)
	{
		$ip_address = '0.0.0.0';
		
		return $ip_address;
	}
	
	if (strstr($ip_address, ','))
	{
		$x = explode(',', $ip_address);
		$ip_address = end($x);
	}
	
	return $ip_address;
}

function valid_ip($ip)
{
	$ip_segments = explode('.', $ip);
	
	// Always 4 segments needed
	if (count($ip_segments) != 4)
	{
		return FALSE;
	}
	// IP can not start with 0
	if (substr($ip_segments[0], 0, 1) == '0')
	{
		return FALSE;
	}
	// Check each segment
	foreach ($ip_segments as $segment)
	{
		// IP segments must be digits and can not be 
		// longer than 3 digits or greater then 255
		if (preg_match("/[^0-9]/", $segment) or $segment > 255 or strlen($segment) > 3)
		{
			return FALSE;
		}
	}
	
	return TRUE;
}

if (! function_exists('iconv'))
{

	function iconv($from_encoding = 'GBK', $target_encoding = 'UTF-8', $string)
	{
		return convert_encoding($string, $from_encoding, $target_encoding);
	}
}

if (! function_exists('iconv_substr'))
{

	function iconv_substr($string, $start, $length, $charset = 'UTF-8')
	{
		return mb_substr($string, $start, $length, $charset);
	}
}

if (! function_exists('iconv_strpos'))
{

	function iconv_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
	{
		return mb_strpos($haystack, $needle, $offset, $charset);
	}
}

function convert_encoding($string, $from_encoding = 'GBK', $target_encoding = 'UTF-8')
{
	if (function_exists('mb_convert_encoding'))
	{
		return mb_convert_encoding($string, str_replace('//IGNORE', '', strtoupper($target_encoding)), $from_encoding);
	}
	else
	{
		if (strtoupper($target_encoding) == 'GB2312' or strtoupper($target_encoding) == 'GBK')
		{
			$target_encoding .= '//IGNORE';
		}
		
		return iconv($from_encoding, $target_encoding, $string);
	}
}

function cjk_strpos($haystack, $needle, $offset = 0, $charset = 'UTF-8')
{
	if (function_exists('iconv_strpos'))
	{
		return iconv_strpos($haystack, $needle, $offset, $charset);
	}
	
	return mb_strpos($haystack, $needle, $offset, $charset);
}

function cjk_substr($string, $start, $length, $charset = 'UTF-8', $dot = '')
{
	if (cjk_strlen($string, $charset) <= $length)
	{
		return $string;
	}
	
	if (function_exists('mb_substr'))
	{
		return mb_substr($string, $start, $length, $charset) . $dot;
	}
	else
	{
		return iconv_substr($string, $start, $length, $charset) . $dot;
	}
}

function cjk_strlen($string, $charset = 'UTF-8')
{
	if (function_exists('mb_strlen'))
	{
		return mb_strlen($string, $charset);
	}
	else
	{
		return iconv_strlen($string, $charset);
	}
}

function make_dir($dir, $mode = 0777)
{
	$dir = rtrim($dir, '/') . '/';
	
	if (is_dir($dir))
	{
		return TRUE;
	}
	
	if (! make_dir(dirname($dir), $mode))
	{
		return FALSE;
	}
	
	return @mkdir($dir, $mode);
}

/**
 * 获取头像目录文件地址
 */
function get_avatar_url($uid, $size = 'min')
{
	$uid = intval($uid);
	
	if ($uid < 1)
	{
		return G_STATIC_URL . '/common/avatar-' . $size . '-img.jpg';
	}
	
	foreach (AWS_APP::config()->get('image')->avatar_thumbnail as $key => $val)
	{
		$all_size[] = $key;
	}
	
	$size = in_array($size, $all_size) ? $size : $all_size[0];
	
	$uid = abs(intval($uid));
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	
	if (file_exists(get_setting('upload_dir') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . "_avatar_$size.jpg"))
	{
		return get_setting('upload_url') . '/avatar/' . $dir1 . '/' . $dir2 . '/' . $dir3 . '/' . substr($uid, - 2) . "_avatar_$size.jpg";
	}
	else
	{
		return G_STATIC_URL . '/common/avatar-' . $size . '-img.jpg';
	}
}

function jsonp_encode($json = array(), $callback = 'jsoncallback')
{
	if ($_GET[$callback])
	{
		return $_GET[$callback] . '(' . json_encode($json) . ')';
	}
	
	return json_encode($json);
}

function download_url($file_name, $url)
{
	$file_name = trim($file_name);
	$url = trim($url);
	
	if (! $file_name || ! $url)
	{
		return false;
	}
	
	return get_js_url('file/download/file_name-' . base64_encode($file_name) . '__url-' . base64_encode($url));

}

function date_friendly($timestamp, $time_limit = 604800, $out_format = 'Y-m-d H:i', $formats = null, $time_now = null)
{
	if (get_setting('time_style') == 'N')
	{
		return date($out_format, $timestamp);
	}
	
	if ($formats == null)
	{
		$formats = array('YEAR' => '%s 年前', 'MONTH' => '%s 月前', 'DAY' => '%s 天前', 'HOUR' => '%s 小时前', 'MINUTE' => '%s 分钟前', 'SECOND' => '%s 秒前');
	}
	
	$time_now = $time_now == null ? time() : $time_now;
	$seconds = $time_now - $timestamp;
	
	if ($seconds == 0)
	{
		$seconds = 1;
	}
	
	if ($time_limit != null && $seconds > $time_limit)
	{
		return date($out_format, $timestamp);
	}
	
	$minutes = floor($seconds / 60);
	$hours = floor($minutes / 60);
	$days = floor($hours / 24);
	$months = floor($days / 30);
	$years = floor($months / 12);
	
	if ($years > 0)
	{
		$diffFormat = 'YEAR';
	}
	else
	{
		if ($months > 0)
		{
			$diffFormat = 'MONTH';
		}
		else
		{
			if ($days > 0)
			{
				$diffFormat = 'DAY';
			}
			else
			{
				if ($hours > 0)
				{
					$diffFormat = 'HOUR';
				}
				else
				{
					$diffFormat = ($minutes > 0) ? 'MINUTE' : 'SECOND';
				}
			}
		}
	}
	
	$dateDiff = null;
	
	switch ($diffFormat)
	{
		case 'YEAR' :
			$dateDiff = sprintf($formats[$diffFormat], $years);
			break;
		case 'MONTH' :
			$dateDiff = sprintf($formats[$diffFormat], $months);
			break;
		case 'DAY' :
			$dateDiff = sprintf($formats[$diffFormat], $days);
			break;
		case 'HOUR' :
			$dateDiff = sprintf($formats[$diffFormat], $hours);
			break;
		case 'MINUTE' :
			$dateDiff = sprintf($formats[$diffFormat], $minutes);
			break;
		case 'SECOND' :
			$dateDiff = sprintf($formats[$diffFormat], $seconds);
			break;
	}
	
	return $dateDiff;
}

function &load_class($class)
{
	static $_classes = array();
	
	// Does the class exist?  If so, we're done...
	if (isset($_classes[$class]))
	{
		return $_classes[$class];
	}
	
	if (class_exists($class) === FALSE)
	{
		$file = AWS_PATH . preg_replace('#_+#', '/', $class) . '.php';
		
		if (! file_exists($file))
		{
			show_error('Unable to locate the specified class: ' . $class . ' ' . preg_replace('#_+#', '/', $class) . '.php');
		}
		
		require_once $file;
	}
	
	$_classes[$class] = new $class();
	
	return $_classes[$class];
}

function _show_error($errorMessage = '')
{
	$errorBlock = '';
	$name = strtoupper($_SERVER['HTTP_HOST']);
	
	if ($errorMessage)
	{
		$errorMessage = htmlspecialchars($errorMessage);
		$errorBlock = <<<EOF
		<div class='database-error'>
	    	<form name='mysql'>
	    		<textarea rows="15" cols="60">{$errorMessage}</textarea>
	    	</form>
    	</div>
EOF;
	}
	
	if (defined('IN_AJAX'))
	{
		return $errorMessage;
	}
	
	return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Cache-Control" content="no-cache" />
		<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
		<title>{$name} System Error</title>
		<style type='text/css'>
			body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td { margin:0; padding:0; } 
			table {	border-collapse:collapse; border-spacing:0; }
			fieldset,img { border:0; }
			address,caption,cite,code,dfn,em,strong,th,var { font-style:normal; font-weight:normal; }
			ol,ul { list-style:none; }
			caption,th { text-align:left; }
			h1,h2,h3,h4,h5,h6 { font-size:100%;	font-weight:normal; }
			q:before,q:after { content:''; }
			abbr,acronym { border:0; }
			hr { display: none; }
			address{ display: inline; }
			body {
				font-family: arial, tahoma, sans-serif;
				font-size: 0.8em;
				width: 100%;
			}
			
			h1 {
				font-family: arial, tahoma, "times new roman", serif;
				font-size: 1.9em;
				color: #fff;
			}
			h2 {
				font-size: 1.6em;
				font-weight: normal;
				margin: 0 0 8px 0;
				clear: both;
			}
			a {
				color: #3e70a8;
			}
			
				a:hover {
					color: #3d8ce4;
				}
				
				a.cancel {
					color: #ad2930;
				}
			#branding {
				background: #484848;
				padding: 8px;
			}
			
			#content {
				clear: both;
				overflow: hidden;
				padding: 20px 15px 0px 15px;
			}
			
			* #content {
				height: 1%;
			}
			
			.message {
				border-width: 1px;
				border-style: solid;
				border-color: #d7d7d7;
				background-color: #f5f5f5;
				padding: 7px 7px 7px 30px;
				margin: 0 0 10px 0;
				clear: both;
			}
			
				.message.error {
					background-color: #f3dddd;
					border-color: #deb7b7;
					color: #281b1b;
					font-size: 1.3em;
					font-weight: bold;
				}
				
				.message.unspecific {
					background-color: #f3f3f3;
					border-color: #d4d4d4;
					color: #515151;
				}
			.footer {
				text-align: center;
				font-size: 1.5em;
			}
			
			.database-error {
				padding: 4px 0px 10px 80px;
				margin: 10px 0px 10px 0px;
			}
			
			textarea {
				width: 700px;
				height: 250px;
			}
		</style>
	</head>
	<body id='ipboard_body'>
		<div id='header'>
			<div id='branding'>
				<h1>{$name} System Error</h1>
			</div>
		</div>
		<div id='content'>
			<div class='message error'>
				There appears to be an error:
				{$errorBlock}
			</div>
			
			<p class='message unspecific'>
				If you are seeing this page, it means there was a problem communicating with our database.  Sometimes this error is temporary and will go away when you refresh the page.  Sometimes the error will need to be fixed by an administrator before the site will become accessible again.
				<br /><br />
				You can try to refresh the page by clicking <a href="#" onclick="window.location=window.location; return false;">here</a>
			</p>
		</div>
	</body>
</html>
EOF;
}

function show_error($errorMessage = '')
{
	echo _show_error($errorMessage);
	exit();
}

function get_table($name)
{
	return AWS_APP::config()->get('database')->prefix . $name;
}

function get_setting($varname = null)
{
	if (! class_exists('AWS_APP', false))
	{
		return false;
	}
	
	static $setting;
	
	if (! $setting)
	{
		if ($setting = AWS_APP::setting())
		{
			if ($setting['upload_enable'] == 'Y' && ! $_SESSION['permission']['upload_attach'])
			{
				$setting['upload_enable'] = 'N';
			}
		}
	}
	
	if ($varname)
	{
		return $setting[$varname];
	}
	else
	{
		return $setting;
	}
}

// ------------------------------------------------------------------------


/**
 * Tests for file writability
 *
 * is_writable() returns TRUE on Windows servers when you really can't write to 
 * the file, based on the read-only attribute.  is_writable() is also unreliable
 * on Unix servers if safe_mode is on. 
 *
 * @return	void
 */
function is_really_writable($file)
{
	// If we're on a Unix server with safe_mode off we call is_writable
	if (DIRECTORY_SEPARATOR == '/' and @ini_get("safe_mode") == FALSE)
	{
		return is_writable($file);
	}
	
	// For windows servers and safe_mode "on" installations we'll actually
	// write a file then read it.  Bah...
	if (is_dir($file))
	{
		$file = rtrim($file, '/') . '/' . md5(rand(1, 100));
		
		if (! @file_put_contents($file, 'is_really_writable() test.'))
		{
			return FALSE;
		}
		else
		{
			@unlink($file);
		}
		
		return TRUE;
	}
	else if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
	{
		return FALSE;
	}
	
	return TRUE;
}

/**
 * 生成密码种子的函数
 *
 * @access      private
 * @param       int     length        长度
 * @return      string
 */
function fetch_salt($length = 4)
{
	$salt = '';
	for ($i = 0; $i < $length; $i ++)
	{
		$salt .= chr(rand(97, 122));
	}
	
	return $salt;
}

/**
 * 编译密码
 *
 *  @param  $password 	密码
 *  @param  $salt		混淆码
 * 	@return string		加密后的密码
 */
function compile_password($password, $salt)
{
	// md5 password...
	if (strlen($password) == 32)
	{
		return md5($password . $salt);
	}
	
	$password = md5(md5($password) . $salt);
	
	return $password;
}

function get_js_url($url)
{
	if (substr($url, 0, 1) == '/')
	{
		$url = substr($url, 1);
		
		if ((get_setting('url_rewrite_enable') == 'Y') && $request_routes = get_request_route())
		{
			foreach ($request_routes as $key => $val)
			{
				if (preg_match('/^' . $val[0] . '$/', $url))
				{
					$url = preg_replace('/^' . $val[0] . '$/', $val[1], $url);
					break;
				}
			}
		}
		
		$url = get_setting('base_url') . '/' . (get_setting('url_rewrite_enable') != 'Y' ? G_INDEX_SCRIPT : '') . $url;
	}
	
	return $url;
}

function calc_page_limit($page, $per_page)
{
	if (intval($per_page) == 0)
	{
		die('calc_page_limit(): Error param - $per_page');
	}
	
	if ($page < 1)
	{
		$page = 1;
	}
	
	return ((intval($page) - 1) * intval($per_page)) . ', ' . intval($per_page);
}

function get_login_cookie_hash($user_name, $password, $salt, $uid, $hash_password = true)
{
	if ($hash_password)
	{
		$password = compile_password($password, $salt);
	}
	
	return H::encode_hash(array('user_name' => $user_name, 'password' => $password, 'uid' => $uid, 'UA' => $_SERVER['HTTP_USER_AGENT']));
}

function random($length, $numeric = 0)
{
	//PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
	

	if ($numeric)
	{
		$hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
	}
	else
	{
		$hash = '';
		$chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		$max = strlen($chars) - 1;
		
		for ($i = 0; $i < $length; $i ++)
		{
			$hash .= $chars[mt_rand(0, $max)];
		}
	}
	
	return $hash;
}

function valid_post_hash($hash)
{
	return AWS_APP::form()->valid_post_hash($hash);
}

function new_post_hash()
{
	return AWS_APP::form()->new_post_hash();
}

// 检测当前操作是否需要验证码
function human_valid($permission_tag)
{
	if (! is_array($_SESSION['human_valid']))
	{
		return FALSE;
	}
	
	if (! $_SESSION['human_valid'][$permission_tag] or ! $_SESSION['permission'][$permission_tag])
	{
		return FALSE;
	}
	
	foreach ($_SESSION['human_valid'][$permission_tag] as $time => $val)
	{
		if (date('H', $time) != date('H', time()))
		{
			unset($_SESSION['human_valid'][$permission_tag][$time]);
		}
	}
	
	if (sizeof($_SESSION['human_valid'][$permission_tag]) >= $_SESSION['permission'][$permission_tag])
	{
		return TRUE;
	}
	
	return FALSE;
}

function set_human_valid($permission_tag)
{
	if (! is_array($_SESSION['human_valid']))
	{
		return FALSE;
	}
	
	$_SESSION['human_valid'][$permission_tag][time()] = TRUE;
	
	return count($_SESSION['human_valid'][$permission_tag]);
}

/**
 * @param int $positive	true:正向 false:反向
 */
function get_request_route($positive = true)
{
	$route_data = (get_setting('request_route') == 99) ? get_setting('request_route_custom') : get_setting('request_route_sys_' . get_setting('request_route'));
	
	if ($request_routes = explode("\n", $route_data))
	{
		$routes = array();
		
		$replace_array = array("(:any)" => "([^\"'&#\?\/]+[&#\?\/]*[^\"'&#\?\/]*)", "(:num)" => "([0-9]+)");
		
		foreach ($request_routes as $key => $val)
		{
			$val = trim($val);
			
			if (empty($val))
			{
				continue;
			}
			
			if ($positive)
			{
				list($pattern, $replace) = explode('===', $val);
			}
			else
			{
				list($replace, $pattern) = explode('===', $val);
			}
			
			if (substr($pattern, 0, 1) == '/' and $pattern != '/')
			{
				$pattern = substr($pattern, 1);
			}
			
			if (substr($replace, 0, 1) == '/' and $replace != '/')
			{
				$replace = substr($replace, 1);
			}
			
			$pattern = addcslashes($pattern, "/\.?");
			
			$pattern = str_replace(array_keys($replace_array), array_values($replace_array), $pattern);
			
			$replace = str_replace(array_keys($replace_array), "\$1", $replace);
			
			$routes[] = array($pattern, $replace);
		}
		
		return $routes;
	}
	else
	{
		return false;
	}
}

function restri_url($matches)
{
	return strip_tags($matches[0]);
}

function strip_ubb($str)
{
	$str = preg_replace('/\[attach\]([0-9]+)\[\/attach]/', '[附件内容]', $str);
	$str = preg_replace('/\[[^\]]+\](http[s]?:\/\/[^\[]*)\[\/[^\]]+\]/', "\$1 ", $str);
	return preg_replace('/\[[^\]]+\]([^\[]*)\[\/[^\]]+\]/', "\$1", $str);
}

function normalize_whitespace($str)
{
	$str = trim($str);
	$str = str_replace("\r", "\n", $str);
	$str = preg_replace(array('/\n+/', '/[ \t]+/'), array("\n", ' '), $str);
	
	return $str;
}

/**
 * 打印变量
 *
 * @param fixed $var
 */
if (! function_exists("p"))
{

	function p($var)
	{
		echo "<br><pre>";
		if (empty($var))
		{
			var_dump($var);
		}
		else
		{
			print_r($var);
		}
		
		echo "</pre><br>";
	}
}

/**
 * 打印变量 带 REQUEST['debugx']==1
 *
 * @param fixed $var
 */
if (! function_exists("pd"))
{

	function pd($var, $exit = 0)
	{
		if ($_REQUEST['debugx'] == 1)
		{
			echo "<br><pre>";
			if (empty($var))
			{
				var_dump($var);
			}
			else
			{
				print_r($var);
			}
			
			echo "</pre><br>";
			if ($exit == 1)
				exit();
		}
	
	}
}

function parse_attachs_callback($matches)
{
	if ($attach = AWS_APP::model('publish')->get_attach_by_id($matches[1]))
	{
		TPL::assign('attach', $attach);
		
		return TPL::output('question/ajax/load_attach', false);
	}
}

function get_topic_pic_url($size = null, $pic_file = null)
{
	$sized_file = AWS_APP::model('topic')->get_sized_file($size, $pic_file);
	
	if ($sized_file)
	{
		return get_setting('upload_url') . '/topic/' . $sized_file;
	}
	else
	{
		if (! $size)
		{
			return G_STATIC_URL . '/common/topic-max-img.jpg';
		}
		
		return G_STATIC_URL . '/common/topic-' . $size . '-img.jpg';
	}
}

function get_feature_pic_url($size = null, $pic_file = null)
{
	if (! $pic_file)
	{
		return false;
	}
	else
	{
		if ($size)
		{
			$pic_file = str_replace(AWS_APP::config()->get('image')->feature_thumbnail['min']['w'] . '_' . AWS_APP::config()->get('image')->feature_thumbnail['min']['h'], AWS_APP::config()->get('image')->feature_thumbnail[$size]['w'] . '_' . AWS_APP::config()->get('image')->feature_thumbnail[$size]['h'], $pic_file);
		}
	}
	
	return get_setting('upload_url') . '/feature/' . $pic_file;
}

function array_random($arr)
{
	shuffle($arr);
	
	return end($arr);
}

/**
 * 获得二维数据中第二维指定键对应的值，并组成新数组
 */
function fetch_array_value($array, $key)
{
	if (! is_array($array) || empty($array))
	{
		return array();
	}
	
	$data = array();
	
	foreach ($array as $_key => $val)
	{
		$data[] = $val[$key];
	}
	
	return $data;
}

function get_host_top_domain()
{
	$host = strtolower($_SERVER['HTTP_HOST']);
	
	if (strpos($host, '/') !== false)
	{
		$parse = @parse_url($host);
		$host = $parse['host'];
	}
	
	$topleveldomaindb = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'museum', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me');
	
	$str = '';
	
	foreach ($topleveldomaindb as $v)
	{
		$str .= ($str ? '|' : '') . $v;
	}
	
	$matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
	
	if (preg_match("/" . $matchstr . "/ies", $host, $matchs))
	{
		$domain = $matchs['0'];
	}
	else
	{
		$domain = $host;
	}
	
	return $domain;
}

function parse_link_callback($matches)
{
	if (preg_match('/^(?!http).*/i', $matches[1]))
	{
		$url = 'http://' . $matches[1];
	}
	else
	{
		$url = $matches[1];
	}
	
	if (is_inside_url($url))
	{
		return '<a href="' . $url . '" class="a">' . FORMAT::sub_url($matches[1], 50) . '</a>';
	}
	else
	{
		return '<a href="' . $url . '" class="a" rel="nofollow" target="_blank">' . FORMAT::sub_url($matches[1], 50) . '</a>';
	}
}

function is_inside_url($url)
{
	if(!$url)
	{
		return false;
	}
	
	if (preg_match('/^(?!http).*/i', $url))
	{
		$url = 'http://' . $url;
	}
	
	$domain = get_host_top_domain();
	
	if (preg_match('/^http[s]?:\/\/([-_a-zA-Z0-9]+[\.])*?' . $domain . '(?!\.)[-a-zA-Z0-9@:;%_\+.~#?&\/\/=]*$/i', $url))
	{
		return true;
	}
	
	return false;
}

function intval_string(&$value)
{
	if (! is_numeric($value))
	{
		$value = intval($value);
	}
}

function get_time_zone()
{
	$time_zone = 0 + (date('O') / 100);
	
	if ($time_zone == 0)
	{
		return '';
	}
	
	if ($time_zone > 0)
	{
		return '+' . $time_zone;
	}
	
	return $time_zone;
}