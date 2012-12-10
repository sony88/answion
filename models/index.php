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

class index_class extends AWS_MODEL
{	
	/**
	 * 获取首页我关注的模块内容
	 *
	 * @param  $uid
	 * @param  $limit
	 */
	function get_index_focus($uid, $limit = 10)
	{
		$index_actions_day_limit = get_setting('index_actions_day_limit');
		
		if ($index_actions_day_limit <= 0)
		{
			$index_actions_day_limit = 30;
		}
		
		$add_time = time() - 60 * 60 * 24 * $index_actions_day_limit;
		
		$user_focus_questions_ids = array(); // 我关注的问题
		$questions_relating_ids = array(); // 我关注的问题和关注话题的问题的集合
		
		$user_focus_topics_questions_ids = array(); // 我关注的话题的问题
		
		$user_focus_topics_ids = array(); // 我关注的话题集合
		$user_follow_uids = array();
		$user_focus_topics_by_questions_ids = array(); //	我关注的话题 BY 问题 ID
		
		
		if (! $questions_uninterested_ids = $this->model('question')->get_question_uninterested($uid))
		{
			$questions_uninterested_ids = array();
		}
		
		//我关注的问题
		if ($user_focus_questions_list = $this->model('question')->get_focus_question_list_by_uid($uid, false))
		{
			foreach ($user_focus_questions_list as $key => $val)
			{
				if (! isset($questions_relating_ids[$val['question_id']]))
				{
					$questions_relating_ids[$val['question_id']] = $val['question_id']; //存我关注的话题的问题集合				
					$user_focus_questions_ids[] = $val['question_id'];
				}
			}
		}
		

		//我关注的话题		
		if ($user_focus_topics = $this->model('topic')->get_focus_topic_list($uid, false))
		{
			foreach ($user_focus_topics as $key => $val)
			{
				if (empty($val['topic_id']))
				{
					continue;
				}
				
				$user_focus_topics_ids[] = $val['topic_id'];
			}
			
			$user_focus_topics_ids = array_unique($user_focus_topics_ids);
			
			if ($user_focus_topics_questions_ids = $this->model('topic')->get_question_ids_by_topics_ids($user_focus_topics_ids, 1000))
			{
				foreach ($user_focus_topics_questions_ids as $key => $question_id)
				{
					if (! isset($questions_relating_ids[$question_id]))
					{
						$questions_relating_ids[$question_id] = $question_id;
					}
				}
			}
			
			if ($user_focus_topics_questions_ids)
			{
				$user_focus_topics_by_questions_ids = $this->model('question')->get_focus_questions_topic($user_focus_topics_questions_ids, $user_focus_topics_ids);
			}
		}
		
		//我关注的人
		if ($user_follow_users = $this->model('follow')->get_user_friends($uid, false))
		{
			foreach ($user_follow_users as $key => $val)
			{
				// 排除自己
				if ($uid != $val['uid'])
				{
					$user_follow_uids[] = $val['uid'];
				}
			}
		}
		
		// 我关注的问题
		// 我关注的话题的回复
		// 我关注的话题添加的问题
		// 我关注的人添加问题, 回复问题, 赞成回答, 增加话题
		// 我关注的人关注了问题
		// 关注的话题的回复添加了赞同
		
		if (sizeof($user_focus_questions_ids) > 0)
		{
			// 回复问题, 创建话题
			/*$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND associate_id IN (" . implode(',', $user_focus_questions_ids) . ")
			AND associate_action IN (" . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_TOPIC . ") AND uid <> " . $uid . ")";*/
			
			// 回复问题
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND associate_id IN (" . implode(',', $user_focus_questions_ids) . ")
			AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . " AND uid <> " . $uid . ")";
			
			// 添加问题
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND associate_id IN (" . implode(',', $user_focus_questions_ids) . ")
			AND associate_action = " . ACTION_LOG::ADD_QUESTION . " AND uid = " . $uid . ")";
		}
		
		if (sizeof($user_focus_topics_questions_ids) > 0)
		{
			// 回复问题
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
			AND associate_action = " . ACTION_LOG::ANSWER_QUESTION . " AND uid <> " . $uid . ")";
			
			// 添加话题
			/*if (sizeof($user_focus_topics_ids) > 0)
			{
				$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
				AND associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
				AND associate_attached IN (" . implode(',', $user_focus_topics_ids) . ")
				AND associate_action = " . ACTION_LOG::ADD_TOPIC . " AND uid <> " . $uid . ")";
			}
			else
			{
				$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
				AND associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
				AND associate_action = " . ACTION_LOG::ADD_TOPIC . " AND uid <> " . $uid . ")";
			}*/
			
			// 关注的话题的回复添加了赞同
			/*if (sizeof($user_focus_topics_questions_ids) > 0)
			{
				$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
				AND associate_id IN (" . implode(',', $user_focus_topics_questions_ids) . ")
				AND associate_action = " . ACTION_LOG::ADD_AGREE . " AND uid <> " . $uid . ")";
			}*/
		}
		
		if (sizeof($user_follow_uids) > 0)
		{
			// 添加问题, 回复问题, 增加赞同, 创建话题
			/*$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_AGREE . ',' . ACTION_LOG::ADD_TOPIC ."))";*/
			
			// 添加问题, 回复问题, 创建话题
			/*$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . ',' . ACTION_LOG::ADD_TOPIC . "))";*/
			
			// 添加问题, 回复问题
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_QUESTION . ',' . ACTION_LOG::ANSWER_QUESTION . "))";
			
			// 增加赞同
			$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
			AND uid IN (" . implode(',', $user_follow_uids) . ")
			AND associate_action IN(" . ACTION_LOG::ADD_AGREE .") AND uid <> " . $uid . ")";
			
			// 添加问题关注
			if (sizeof($user_focus_questions_ids) > 0)
			{
				$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
				AND uid IN (" . implode(',', $user_follow_uids) . ")
				AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . "
				AND associate_id NOT IN (" . implode(',', $user_focus_questions_ids) . "))";
			}
			else
			{
				$where_in[] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . "
				AND uid IN (" . implode(',', $user_follow_uids) . ")
				AND associate_action = " . ACTION_LOG::ADD_REQUESTION_FOCUS . ")";
			}
		}
		
		if (sizeof($questions_uninterested_ids) > 0)
		{
			$where = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_id NOT IN (" . implode(',', $questions_uninterested_ids) . "))";
		}
		else if (sizeof($user_focus_questions_ids) > 0)
		{
			$where = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . ")";
		}
			
		if ($where_in AND $where)
		{
			$where .= ' AND (' . implode(' OR ', $where_in) . ')';
		}
		else if ($where_in)
		{
			$where = implode(' OR ', $where_in);
		}
		
		if (!$action_list = ACTION_LOG::get_actions_distint_by_where($where, $limit, $add_time, false))
		{
			return false;
		}
		
		$action_list_uids = array();
		
		foreach ($action_list as $key => $val)
		{
			$action_list_question_ids[] = $val['associate_id'];
			
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ANSWER_QUESTION, 
				ACTION_LOG::ADD_AGREE
			)) AND $val['associate_attached'])
			{
				$action_list_answer_ids[] = $val['associate_attached'];
			}
			
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ADD_TOPIC
			)) AND $val['associate_attached'])
			{
				$action_list_topic_ids[] = $val['associate_attached'];
			}
			
			if (! in_array($val['uid'], $action_list_uids))
			{
				$action_list_uids[] = $val['uid'];
			}
		
		}
		
		if ($action_list_question_ids)
		{
			$question_infos = $this->model('question')->get_question_info_by_ids($action_list_question_ids);
		}

		if ($action_list_answer_ids)
		{			
			$answer_infos = $this->model('answer')->get_answers_by_ids($action_list_answer_ids);
		}		
		
		if ($action_list_topic_ids)
		{
			$topic_infos = $this->model('topic')->get_topics_by_ids($action_list_topic_ids);
		}
		
		if ($action_list_uids)
		{
			$user_info_lists = $this->model('account')->get_user_info_by_uids($action_list_uids, true);
		}
		
		// 重组信息		
		foreach ($action_list as $key => $val)
		{
			$topic_info = null;
			
			$action_list[$key]['user_info'] = $user_info_lists[$val['uid']];
			
			if (in_array($val['associate_action'], array(
				ACTION_LOG::ADD_TOPIC
			)))
			{
				$topic_info = $topic_infos[$val['associate_attached']];
			}
			
			if (isset($user_focus_topics_by_questions_ids[$val['associate_id']]))
			{
				if (! $topic_info)
				{
					$topic_info = $user_focus_topics_by_questions_ids[$val['associate_id']];
				}
			}
			
			$question_info = $question_infos[$val['associate_id']];
			
			// 是否关注
			if (in_array($question_info['question_id'], $user_focus_questions_ids))
			{
				$question_info['has_focus'] = TRUE;
			}
			
			$question_info['last_action_str'] = ACTION_LOG::format_action_str($val['associate_action'], $val['uid'], $user_info_lists[$val['uid']]['user_name'], $question_info, $topic_info);
			
			//对于回复问题的
			if ($question_info['answer_count'] && (in_array($val['associate_action'], array(
				ACTION_LOG::ANSWER_QUESTION, 
				ACTION_LOG::ADD_AGREE
			))))
			{
				//取里面的回复的ID出来
				$question_info['answer_info'] = $answer_infos[$val['associate_attached']];
				
				if (! isset($user_info_lists[$question_info['answer_info']['uid']]))
				{
					$user_info_lists[$question_info['answer_info']['uid']] = $this->model('account')->get_user_info_by_uid($question_info['answer_info']['uid'], true);
				}
				
				$question_info['answer_info']['uid'] = $user_info_lists[$question_info['answer_info']['uid']]['uid'];
				$question_info['answer_info']['user_name'] = $user_info_lists[$question_info['answer_info']['uid']]['user_name'];
				$question_info['answer_info']['url_token'] = $user_info_lists[$question_info['answer_info']['uid']]['url_token'];
				$question_info['answer_info']['signature'] = $user_info_lists[$question_info['answer_info']['uid']]['signature'];
			}
			
			//处理回复
			if (! empty($question_info['answer_info']['answer_id']))
			{
				if ($question_info['answer_info']['anonymous'])
				{
					unset($action_list[$key]);
					
					continue;
				}
				
				$answer_all_ids[] = $question_info['answer_info']['answer_id'];
				
				if ($question_info['answer_info']['has_attach'])
				{
					$question_info['answer_info']['attachs'] = $this->model('publish')->get_attach('answer', $question_info['answer_info']['answer_id'], 'min');
				}
			}
			
			//还原到单个数组 ROW 里面
			foreach ($question_info as $qkey => $qval)
			{
				if ($qkey == 'add_time')
				{
					continue;
				}
				
				$action_list[$key][$qkey] = $qval;
			}
		
		}
		
		if ($answer_all_ids)
		{
			$answer_agree_users = $this->model('answer')->get_vote_user_by_answer_ids($answer_all_ids);
			$answer_vote_status = $this->model('answer')->get_answer_vote_status($answer_all_ids, $uid);
		}
		
		foreach ($action_list as $key => $val)
		{
			if (isset($action_list[$key]['answer_info']['answer_id']))
			{
				$answer_id = $action_list[$key]['answer_info']['answer_id'];
				
				if (isset($answer_agree_users[$answer_id]))
				{
					$action_list[$key]['answer_info']['agree_users'] = $answer_agree_users[$answer_id];
				}
				
				if (isset($answer_vote_status[$answer_id]))
				{
					$action_list[$key]['answer_info']['agree_status'] = $answer_vote_status[$answer_id];
				}
			}
		}
		
		return $action_list;
	}
}