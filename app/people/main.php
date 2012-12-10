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
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white'; //黑名单,黑名单中的检查  'white'白名单,白名单以外的检查
		
		if ($this->user_info['permission']['visit_people'] AND $this->user_info['permission']['visit_site'])
		{
			$rule_action['actions'][] = 'index';
			$rule_action['actions'][] = 'list';
		}
		
		return $rule_action;
	}

	public function setup()
	{
		
	}


	public function index_action()
	{
		if (isset($_GET['notification_id']))
		{
			$this->model('notify')->read_notification($_GET['notification_id']);
		}
		
		//if ((is_numeric($_GET['id']) AND intval($_GET['id']) == $this->user_id AND $this->user_id) OR ($this->user_id AND !$_GET['id']))
		if ($this->user_id AND !$_GET['id'])
		{
			$user = $this->user_info;
		}
		else
		{
			if (is_numeric($_GET['id']))
			{
				if (!$user = $this->model('account')->get_user_info_by_uid($_GET['id'], TRUE))
				{
					$user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE);
				}
			}
			else if ($user = $this->model('account')->get_user_info_by_username($_GET['id'], TRUE))
			{
				
			}
			else
			{
				$user = $this->model('account')->get_user_info_by_url_token($_GET['id'], TRUE);
			}
			
			if (!$user)
			{
				H::redirect_msg('用户不存在', '/');
			}
			
			if (urldecode($user['url_token']) != $_GET['id'])
			{
				HTTP::redirect('/people/' . $user['url_token']);
			}
			
			$this->model('people')->update_views_count($user['uid']);
		}
		
		TPL::assign('user', $user);
		
		$job_info = $this->model('account')->get_jobs_by_id($user['job_id']);
		
		TPL::assign('job_name', $job_info['job_name']);
		
		if ($users_sina = $this->model('openid_weibo')->get_users_sina_by_uid($user['uid']))
		{
			TPL::assign('sina_weibo_url', 'http://www.weibo.com/' . $users_sina['id']);
		}
		
		if ($users_qq = $this->model('openid_qq_weibo')->get_users_qq_by_uid($user['uid']))
		{
			TPL::assign('qq_weibo_url', 'http://t.qq.com/' . $users_qq['name']);
		}
		
		TPL::assign('education_experience_list', $this->model('education')->get_education_experience_list($user['uid']));
		
		$jobs_list = $this->model('work')->get_jobs_list();
		
		if ($work_experience_list = $this->model('work')->get_work_experience_list($user['uid']))
		{
			foreach ($work_experience_list as $key => $val)
			{
				$work_experience_list[$key]['job_name'] = $jobs_list[$val['job_id']];
			}
		}
		
		TPL::assign('work_experience_list', $work_experience_list);
		
		TPL::assign('user_follow_check', $this->model('follow')->user_follow_check($this->user_id, $user['uid']));
		
		$this->crumb($user['user_name'] . ' 的个人主页', 'people/' . $user['url_token']);
		
		TPL::import_css('css/main.css');
		
		TPL::assign('reputation_topics', $this->model('people')->get_user_reputation_topic($user['uid'], $user['reputation'], 12));
		TPL::assign('fans_list', $this->model('follow')->get_user_fans($user['uid'], 5));
		TPL::assign('friends_list', $this->model('follow')->get_user_friends($user['uid'], 5));
		TPL::assign('focus_topics', $this->model('topic')->get_focus_topic_list($user['uid'], 10));
		
		TPL::assign('user_actions_questions', $this->model('account')->get_user_actions($user['uid'], 5, ACTION_LOG::ADD_QUESTION, $this->user_id), TRUE);
		TPL::assign('user_actions_answers', $this->model('account')->get_user_actions($user['uid'], 5, ACTION_LOG::ANSWER_QUESTION, $this->user_id), TRUE);
		TPL::assign('user_actions', $this->model('account')->get_user_actions($user['uid'], 5, implode(',', array(
			ACTION_LOG::ADD_QUESTION,
			ACTION_LOG::ANSWER_QUESTION,
			ACTION_LOG::ADD_REQUESTION_FOCUS,
			ACTION_LOG::ADD_AGREE,
			ACTION_LOG::ADD_TOPIC,
			ACTION_LOG::ADD_TOPIC_FOCUS
		)), $this->user_id), FALSE);
				
		TPL::output('people/index');
	}
	
	public function list_action()
	{
		$url = '/people/list/';
		
		if ($_GET['sort_key'] == 'integral')
		{
			$sort_key = $_GET['sort_key'];
			
			$url .= 'sort_key-' . $sort_key;
		}
		else
		{
			$sort_key = 'reputation';
		}

		if ($users_list = $this->model('account')->get_users_list('', calc_page_limit($_GET['page'], get_setting('contents_per_page')), true, false, $sort_key . ' DESC, MEM.uid DESC'))
		{
			foreach ($users_list as $key => $val)
			{
				if ($val['reputation'])
				{
					$users_list[$key]['reputation_topics'] = $this->model('people')->get_user_reputation_topic($val['uid'], $val['reputation'], 5);
				}					
				
				$users_ids[] = $val['uid'];
			}
		}
		
				
		if ($users_ids)
		{
			$users_follow_check = $this->model('follow')->users_follow_check($this->user_id, $users_ids);
		}
		
		foreach ($users_list as $key => $val)
		{
			$users_list[$key]['focus'] = $users_follow_check[$val['uid']];
		}
		
		$all_users = $this->model('account')->get_user_count('group_id <> 3');
		
		TPL::assign('pagination', AWS_APP::pagination()->initialize(array(
			'base_url' => get_js_url($url), 
			'total_rows' => $all_users, 
			'per_page' => get_setting('contents_per_page')
		))->create_links());
		
		TPL::assign('users_list', $users_list);
		
		$this->crumb('用户列表', $url);
		
		TPL::import_css('css/main.css');
		
		TPL::output('people/list');
	}
}