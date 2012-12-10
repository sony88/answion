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

class core_db
{
	private $db;
	private $current_db_object;
	
	public function __construct()
	{
		$config_class = load_class('core_config');
		
		$config = $config_class->get('database');
		
		if ($config_class->get('system')->debug)
		{
			$start_time = microtime(TURE);
		}
		
		$this->db['master'] = Zend_Db::factory($config->driver, $config->master);

		try
		{
			$this->db['master']->query("SET NAMES {$config->charset}");
			$this->db['master']->query("SET sql_mode = ''");
		}
		catch (Exception $e)
		{
			show_error('Can\'t connect master database: ' . $e->getMessage());
		}
		
		if ($config_class->get('system')->debug AND class_exists('AWS_APP', false))
		{
			AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Connect Master DB');
		}
		
		if ($config->slave)
		{
			if ($config_class->get('system')->debug)
			{
				$start_time = microtime(TURE);
			}	
			
			$this->db['slave'] = Zend_Db::factory($config->driver, $config->slave);
			
			try
			{
				$this->db['slave']->query("SET NAMES {$config->charset}");
				$this->db['slave']->query("SET sql_mode = ''");
			}
			catch (Exception $e)
			{
				show_error('Can\'t connect slave database: ' . $e->getMessage());
			}
			
			if ($config_class->get('system')->debug AND class_exists('AWS_APP', false))
			{
				AWS_APP::debug_log('database', (microtime(TRUE) - $start_time), 'Connect Slave DB');
			}
		}
		else
		{
			$this->db['slave'] =& $this->db['master'];
		}
		
		if (!defined('MYSQL_VERSION'))
		{
			define('MYSQL_VERSION', $this->db['master']->getServerVersion());
		}
		
		//Zend_Db_Table_Abstract::setDefaultAdapter($this->db['master']);
		$this->setObject();
	}
	
	public function setObject($db_object_name = 'master')
	{
		if (isset($this->db[$db_object_name]))
		{
			Zend_Registry::set('dbAdapter', $this->db[$db_object_name]);
			Zend_Db_Table_Abstract::setDefaultAdapter($this->db[$db_object_name]);
			
			$this->current_db_object = $db_object_name;
			
			return $this->db[$db_object_name];
		}
		
		show_error('Can\'t find this db object: ' . $db_object_name);
	}
}