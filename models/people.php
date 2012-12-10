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

class people_class extends AWS_MODEL
{	
	// 更新个人首页计数
	public function update_views_count($uid)
	{
		return $this->query('UPDATE ' . $this->get_table('users') . ' SET views_count = views_count + 1 WHERE uid = ' . intval($uid));
	}
	
	public function get_user_reputation_topic($uid, $user_reputation, $limit = 10)
	{
		if ($user_reputation_topics = $this->model('reputation')->get_reputation_topic($uid))
		{
			foreach ($user_reputation_topics as $key => $val)
			{
				if ($val['reputation'] < 1)
				{
					continue;
				}
				
				$reputation_topics[] = $val;
			}
		}
		
		if ($reputation_topics)
		{
			$reputation_topics = aasort($reputation_topics, 'reputation', 'DESC');
			
			$reputation_topics = array_slice($reputation_topics, 0, $limit);
			
			$topic_all_count = 0;
			
			foreach ($reputation_topics as $rtkey => $rtval)
			{
				$topic_all_count += $rtval['topic_count'];
				$rtopic_ids[] = $rtval['topic_id'];
			}
			
			$topics = $this->model('topic')->get_topics_by_ids($rtopic_ids);
			
			foreach ($reputation_topics as $rtkey => $rtval)
			{	
				if ($rtval['reputation'] && $user_reputation)
				{
					$reputation_topics[$rtkey]['percent'] = round(($rtval['reputation'] / $user_reputation) * 100);
				}
				else
				{
					$reputation_topics[$rtkey]['percent'] = 0;
				}
				
				$reputation_topics[$rtkey]['topic_title'] = $topics[$rtval['topic_id']]['topic_title'];
				$reputation_topics[$rtkey]['url_token'] = $topics[$rtval['topic_id']]['url_token'];
			}
		}
		
		return $reputation_topics;
	}
}