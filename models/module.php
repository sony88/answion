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

class module_class extends AWS_MODEL
{
	/**
	 * 可能感兴趣的人和话题
	 * @return multitype:
	 */
	function recommend_users_topics($uid)
	{
		$recommend_users = $this->model('account')->get_user_recommend_v2($uid, 20);
		
		if (! $recommend_topics = $this->model('topic')->get_user_recommend_v2($uid, 20))
		{
			return array_slice($recommend_users, 0, get_setting('recommend_users_number'));
		}
		
		if ($recommend_topics)
		{
			shuffle($recommend_topics);
			
			$recommend_topics = array_slice($recommend_topics, 0, intval(get_setting('recommend_users_number') / 2));
		}
		
		if ($recommend_users)
		{
			shuffle($recommend_users);
			
			$recommend_users = array_slice($recommend_users, 0, (get_setting('recommend_users_number') - count($recommend_topics)));
		}
		
		if (! is_array($recommend_users))
		{
			$recommend_users = array();
		}
		
		return array_merge($recommend_users, $recommend_topics);
	}
	
	/**
	 * 内容分类
	 * @return 
	 */
	function content_category()
	{
		$content_category = $this->model('system')->fetch_category('question');
		
		return $content_category;
	}

	/**
	 * 边栏热门话题
	 */
	function sidebar_hot_topics($uid = 0, $category_id = 0)
	{
		$cache_key = 'topic_get_hot_topic_' . $category_id . '_5';
				
		$topics = $this->model('cache')->load($cache_key);
				
		if (! is_array($topics))
		{
			$topics = $this->model('topic')->get_hot_topic($category_id, 5);
					
			$this->model('cache')->save($cache_key, $topics, 3600);
		}
		
		if (is_array($topics['topics']))
		{
			return $topics['topics'];
		}
		else
		{	
			return array();
		}
	
	}

	/**
	 * 边栏热门用户
	 */
	function sidebar_hot_users($uid = 0, $limit = 5)
	{
		if ($users_list = $this->fetch_all('users', 'uid <> ' . intval($uid) . ' AND last_active > ' . (time() - (60 * 60 * 24 * 30)), 'answer_count DESC', ($limit * 4)))
		{
			foreach($users_list as $key => $val)
			{
				if (!$val['url_token'])
				{
					$users_list[$key]['url_token'] = urlencode($val['user_name']);
				}
			}
		}
		
		shuffle($users_list);
		
		return array_slice($users_list, 0, $limit);
	}
	
	public function feature_list()
	{
		return $this->model('feature')->get_feature_list(null, false, 'id DESC', 5);
	}
}