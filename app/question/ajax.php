<?php
/*
+--------------------------------------------------------------------------
|   Anwsion [#RELEASE_VERSION#]
|   ========================================
|   by Tatfook Network Team
|   (c) 2011 - 2012 Anwsion Software
|   http://www.anwsion.com
|   ========================================
|   Support: zhengqiang@gmail.com
|   
+---------------------------------------------------------------------------
*/

define('IN_AJAX', TRUE);


if (!defined('IN_ANWSION'))
{
	die;
}

class ajax extends AWS_CONTROLLER
{
	public function get_access_rule()
	{
		$rule_action['rule_type'] = 'white';
		
		$rule_action['actions'] = array(
			'get_question_comments', 
			'get_answer_comments', 
			'log', 
			'load_attach', 
			'get_focus_users', 
			'get_answer_users', 
			'discuss_json', 
			'send_share_email'
		);
		
		if ($this->user_info['permission']['visit_explore'])
		{
			$rule_action['actions'][] = 'discuss';
		}
		
		if ($this->user_info['permission']['visit_question'])
		{
			$rule_action['actions'][] = 'question_share_txt';
			$rule_action['actions'][] = 'answer_share_txt';
			$rule_action['actions'][] = 'topic_share_txt';
		}
		
		return $rule_action;
	}

	function setup()
	{
		HTTP::no_cache_header();
		
		$this->per_page = get_setting('contents_per_page');
	}
	
	public function fetch_answer_data_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_GET['id']);
		
		if ($answer_info['uid'] == $this->user_id OR $this->user_info['permission']['is_administortar'] OR $this->user_info['permission']['is_moderator'])
		{
			echo json_encode($answer_info);
		}
	}

	public function uninterested_action()
	{
		$question_id = intval($_POST['question_id']);
		
		if ($question_id == 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "问题不存在"));
		}
		
		$this->model('question')->add_question_uninterested($this->user_id, $question_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '操作成功'));
	}

	public function get_focus_users_action()
	{
		$focus_users = array();
		
		if ($focus_users_info = $this->model('question')->get_focus_users_by_question($_GET['question_id']))
		{
			$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);
			
			foreach($focus_users_info as $key => $val)
			{
				if ($val['uid'] == $question_info['published_uid'] and $question_info['anonymous'] == 1)
				{
					$focus_users[$key] = array(
						'uid' => 0,
						'user_name' => '匿名用户',
						'avatar_file' => get_avatar_url(0, 'mid'),
					);
				}
				else
				{
					$focus_users[$key] = array(
						'uid' => $val['uid'],
						'user_name' => $val['user_name'],
						'avatar_file' => get_avatar_url($val['uid'], 'mid'),
						'url' => get_js_url('/people/' . $val['url_token'])
					);
				}
			}
		}
		
		H::ajax_json_output($focus_users);
	}

	public function save_invite_action()
	{
		$question_id = intval($_POST['question_id']);
		$recipients_uid = intval($_POST['uid']);
		
		if (!$question_id || !$recipients_uid)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "问题或用户不存在"));
		}
		
		if ($recipients_uid == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "不能邀请自己回复问题"));
		}
		
		if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '你的剩余积分已经不足以进行此操作'));
		}
		
		if ($this->model('answer')->has_answer_by_uid($question_id, $recipients_uid))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '该用户已经回答过问题'));
		}
		
		$question_info = $this->model('question')->get_question_info_by_id($question_id);
		
		if ($question_info['published_uid'] == $recipients_uid)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '不能邀请问题的发起者'));
		}
		
		if ($this->model('question')->check_question_invite($question_id, 0, $recipients_uid))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "该用户已接受过邀请"));
		}
		
		if ($this->model('question')->check_question_invite($question_id, $this->user_id, $recipients_uid))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "已邀请过该用户"));
		}
		
		$this->model('question')->add_invite($question_id, $this->user_id, $recipients_uid);
		
		$this->model('account')->increase_user_statistics(account_class::INVITE_COUNT, 0, $recipients_uid);

		$this->model('notify')->send($this->user_id, $recipients_uid, notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, $question_id, array(
			'from_uid' => $this->user_id, 
			'question_id' => $question_id
		));
			
		$this->model('email')->action_email(email_class::TYPE_QUESTION_INVITE, $recipients_uid, get_js_url('/question/' . $question_id), array(
			'user_name' => $this->user_info['user_name'], 
			'question_title' => $question_info['question_content']
		));
			
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function update_answer_action()
	{
		$answer_id = intval($_GET['answer_id']);
		$answer_content = trim($_POST['answer_content'], "\r\n\t");
		
		if (! $answer_info = $this->model('answer')->get_answer_by_id($answer_id))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'input' => 'answer_content'
			), "-2", "回复不存在"));
		}
		
		if ($_POST['do_delete'])
		{
			if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
			{
				H::ajax_json_output(AWS_APP::RSM(null, - 1, "对不起，你没有删除权限。"));
			}
			
			$this->model('answer')->remove_answers_by_ids($answer_id);
			
			// 通知回复的作者
			if ($this->user_id != $answer_info['uid'])
			{
				$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_REMOVE_ANSWER, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'from_uid' => $this->user_id, 
					'question_id' => $answer_info['question_id']
				));
			}
			
			$this->model('question')->save_last_answer($answer_info['question_id']);
			
			H::ajax_json_output(AWS_APP::RSM(null, 1, "删除回复成功"));
		}
		
		if (!$answer_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "请输入回复内容"));
		}
		
		if (strlen($answer_content) < get_setting('answer_length_lower'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "回复内容字数不得少于 " . get_setting('answer_length_lower') . ' 字节'));
		}
		
		if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($answer_content))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "你所在的用户组不允许发布站外链接"));
		}
		
		if ($answer_info['uid'] != $this->user_id and ! $this->user_info['permission']['is_administortar'] and ! $this->user_info['permission']['is_moderator'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "你没有权限编辑这个回复"));
		}
		
		if ($answer_info['uid'] == $this->user_id and (time() - $answer_info['add_time'] > get_setting('answer_edit_time') * 60) and get_setting('answer_edit_time'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '发布时间超过 ' . get_setting('answer_edit_time') . ' 分钟的回复不允许编辑'));
		}
		
		$this->model('answer')->update_answer($answer_id, $answer_info['question_id'], $answer_content, $_POST['attach_access_key']);
		
		// 记录日志
		ACTION_LOG::save_action($this->user_id, $answer_info['question_id'], ACTION_LOG::CATEGORY_ANSWER, ACTION_LOG::MOD_ANSWER, htmlspecialchars($answer_content), $answer_info['answer_content']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'target_id' => $_GET['target_id'], 
			'display_id' => $_GET['display_id']
		), 1, null));
	}

	function agree_answer_action()
	{		
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);
		
		if ($this->model('answer')->agree_answer($this->user_id, $_POST['answer_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'agree'
			)), 1, "赞同发送成功");
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'disagree'
			)), 1, "赞同发送成功");
		}
	}

	public function question_share_txt_action()
	{
		$question_id = intval($_GET['question_id']);
		
		$q_info = $this->model('question')->get_question_info_by_id($question_id);
		
		$q_info['question_content'] = cjk_substr($q_info['question_content'], 0, 100, 'UTF-8', '...');
		
		$url = get_js_url("/question/" . $question_id . "?fromuid-" . $this->user_id);
		
		$username = $this->user_info['user_name'] ? $this->user_info['user_name'] : '我';
		
		$email_message = (array)AWS_APP::config()->get('email_message');
		
		foreach ($email_message[email_class::TYPE_QUESTION_SHARE] as $key => $val)
		{
			$$key = str_replace('[#user_name#]', $username, $val);
			$$key = str_replace('[#site_name#]', get_setting('site_name'), $$key);
			$$key = str_replace('[#question_title#]', $q_info['question_content'], $$key);
		}
		
		$q_info['question_detail'] = trim(str_replace(array(
			"\r", 
			"\n", 
			"\t"
		), ' ', cjk_substr($q_info['question_detail'], 0, 90, 'UTF-8', '...')));
		
		$q_info['question_detail'] = preg_replace('/\[attach\]([0-9]+)\[\/attach\]/i', '', $q_info['question_detail']);
		
		$data = array(
			'sns_share' => addcslashes($q_info['question_content'] . ' ' . $q_info['question_detail'], '\''), 
			'mail' => $message, 
			'message' => "我看到一个不错的问题，想和你分享：“" . $q_info['question_content'] . "” " . $url, 
			'title' => $q_info['question_content'], 
			'url' => $url,
			'sina_akey' => get_setting('sina_akey') ? get_setting('sina_akey') : '3643094708',
			'qq_app_key' => get_setting('qq_app_key') ? get_setting('qq_app_key') : '801158211',
		);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'share_txt' => $data
		), 1));
	}

	public function answer_share_txt_action()
	{
		$answer_id = intval($_GET['answer_id']);
		
		$a_info = $this->model('answer')->get_answer_by_id($answer_id);
		
		$u_info = $this->model('account')->get_user_info_by_uid($a_info['uid']);
		
		$q_info = $this->model('question')->get_question_info_by_id($a_info['question_id']);
		
		$a_info['answer_content'] = trim(cjk_substr($a_info['answer_content'], 0, 100, 'UTF-8', '...'), '\r\n\t');
		
		$a_info['answer_content'] = str_replace(array(
			"\r", 
			"\n", 
			"\t"
		), ' ', $a_info['answer_content']);
		
		$url = get_js_url('/question/' . $a_info['question_id'] . '?fromuid-' . $this->user_id . '__item_id-' . $answer_id . '#!answer_' . $answer_id);
		
		$username = $this->user_info['user_name'] ? $this->user_info['user_name'] : '我';
		
		$email_message = (array)AWS_APP::config()->get('email_message');
		
		foreach ($email_message[email_class::TYPE_ANSWER_SHARE] as $key => $val)
		{
			$$key = str_replace('[#user_name#]', $username, $val);
			$$key = str_replace('[#site_name#]', get_setting('site_name'), $$key);
			$$key = str_replace('[#question_title#]', $q_info['question_content'], $$key);
			$$key = str_replace('[#answer_user#]', $u_info['user_name'], $$key);
			$$key = str_replace('[#answer_content#]', cjk_substr($a_info['answer_content'], 0, 300, 'UTF-8', '...'), $$key);
		}
		
		$data = array(
			'sns_share' => addcslashes(cjk_substr($q_info['question_content'] . ' @' . $u_info['user_name'] . ": " . $a_info['answer_content'], 0, 120, 'UTF-8', '...'), '\''), 
			'mail' => $message, 
			'message' => "我看到一个不错的问题，想和你分享：“" . $q_info['question_content'] . "” " . $u_info['user_name'] . "： " . cjk_substr($a_info['answer_content'], 0, 300, 'UTF-8', '...') . ' ' . $url, 
			'title' => $q_info['question_content'], 
			'url' => $url,
			'sina_akey' => get_setting('sina_akey') ? get_setting('sina_akey') : '3643094708',
			'qq_app_key' => get_setting('qq_app_key') ? get_setting('qq_app_key') : '801158211',
		);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'share_txt' => $data
		), 1));
	}

	public function topic_share_txt_action()
	{
		$topic_id = intval($_GET['topic_id']);
		
		$topic_info = $this->model('topic')->get_topic_by_id($topic_id);
		
		$url = get_js_url("/topic/" . $topic_id . "?fromuid-" . $this->user_id);
		
		$email_message = (array)AWS_APP::config()->get('email_message');
		
		$username = $this->user_info['user_name'] ? $this->user_info['user_name'] : '我';
		
		foreach ($email_message[email_class::TYPE_TOPIC_SHARE] as $key => $val)
		{
			$$key = str_replace('[#user_name#]', $username, $val);
			$$key = str_replace('[#site_name#]', get_setting('site_name'), $$key);
			$$key = str_replace('[#topic_title#]', $topic_info['topic_title'], $$key);
		}
		
		$topic_info['topic_description'] = str_replace(array(
			"\r", 
			"\n", 
			"\t"
		), ' ', $topic_info['topic_description']);
		
		$data = array(
			'sns_share' => addcslashes($topic_info['topic_title'] . ' ' . $topic_info['topic_description'], '\''), 
			'mail' => $message, 
			'message' => "我看到一个不错的话题，想和你分享：“" . $topic_info['topic_title'] . "” " . $url,
			'title' => $topic_info['topic_title'], 
			'url' => $url, 
			'sina_akey' => get_setting('sina_akey') ? get_setting('sina_akey') : '3643094708',
			'qq_app_key' => get_setting('qq_app_key') ? get_setting('qq_app_key') : '801158211',
		);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'share_txt' => $data
		), 1));
	}

	public function send_share_email_action()
	{
		if (! $_POST['email_message'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), '-1', '请输入邮件内容'));
		}
		
		if (! H::valid_email($_POST['email_address']))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), '-1', '邮件地址格式错误'));
		}
		
		if ($_POST['email_address'] == $this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), '-1', '不能发送给自己'));
		}
		
		$email_hash = base64_encode(H::encode_hash(array(
			'email' => $_POST['email_address']
		)));
		
		$uid = $this->user_id ? $this->user_id : 'guest';
		
		switch ($_POST['model_type'])
		{
			case 'answer' :
				$action = email_class::TYPE_ANSWER_SHARE;
				$a_info = $this->model('answer')->get_answer_by_id($_POST['target_id']);
				$q_info = $this->model('question')->get_question_info_by_id($a_info['question_id']);
				$link_title = $q_info['question_content'];
				$url = get_js_url('/question/' . $a_info['question_id'] . '?fromuid-' . $uid . '__item_id-' . $_POST['target_id'] . '__fromemail-' . $email_hash . '#answer_' . $_POST['target_id']);
				break;
			
			case 'question' :
				$action = email_class::TYPE_QUESTION_SHARE;
				$q_info = $this->model('question')->get_question_info_by_id($_POST['target_id']);
				$link_title = $q_info['question_content'];
				$url = get_js_url('/question/' . $q_info['question_id'] . '?fromuid-' . $uid . '__fromemail-' . $email_hash);
				break;
			
			case 'topic' :
				$action = email_class::TYPE_TOPIC_SHARE;
				$t_info = $this->model('topic')->get_topic_by_id($_POST['target_id']);
				$link_title = $t_info['topic_title'];
				$url = get_js_url('/topic/' . $t_info['topic_id'] . '?fromuid-' . $uid . '__fromemail-' . $email_hash);
				break;
			
			default :
		}
		
		$username = $this->user_info['user_name'] ? $this->user_info['user_name'] : '我';
		
		$email_message = (array)AWS_APP::config()->get('email_message');
		
		foreach ($email_message[$action] as $key => $val)
		{
			$$key = str_replace('[#user_name#]', $username, $val);
			$$key = str_replace('[#site_name#]', get_setting('site_name'), $$key);
			$$key = str_replace('[#question_title#]', $q_info['question_content'], $$key);
			$$key = str_replace('[#topic_title#]', $t_info['topic_title'], $$key);
		}
		
		$this->model('email')->send($_POST['email_address'], $subject, nl2br($_POST['email_message']), $url, $link_title);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, '分享发送成功'));
	}

	function discuss_action()
	{
		if ($_GET['feature_id'])
		{
			$_GET['topic_id'] = $this->model('feature')->get_topics_by_feature_id($_GET['feature_id'], false, false);
		}
		
		if ($_GET['sort_type'] == 'unresponsive')
		{
			$_GET['answer_count'] = '0';
		}
		
		if ($_GET['per_page'])
		{
			$per_page = intval($_GET['per_page']);
		}
		else
		{
			$per_page = get_setting('contents_per_page');
		}
		
		$question_list = $this->model('question')->get_questions_list($_GET['page'], $per_page, $_GET['sort_type'], $_GET['topic_id'],$_GET['category'], $_GET['answer_count'], $_GET['day']);
		
		TPL::assign('question_list', $question_list);
		
		if ($_GET['template'] == 'mobile')
		{
			TPL::output("mobile/ajax/list");
		}
		else
		{
			TPL::output("question/ajax/list");
		}
	}

	public function save_answer_comment_action()
	{
		if (! $_GET['answer_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "回复不存在"));
		}
		
		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "请输入评论内容"));
		}
		
		if ((get_setting('comment_limit') > 0) && (cjk_strlen($_POST['message']) > get_setting('comment_limit')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "评论内容字数不得超过" . get_setting('comment_limit') . "个"));
		}
		
		$answer = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		
		$question_info = $this->model('question')->get_question_info_by_id($answer['question_id']);
		
		if ($question_info['lock'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '不能评论锁定的问题。'));
		}
		
		if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($_POST['message']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "你所在的用户组不允许发布站外链接"));
		}
		
		$message = $this->model('question')->parse_at_user($_POST['message'], false, false, true);
		
		if ($comment_id = $this->model('answer')->insert_answer_comment($_GET['answer_id'], $this->user_id, $message))
		{
			if ($answer['uid'] != $this->user_id)
			{
				$this->model('notify')->send($this->user_id, $answer['uid'], notify_class::TYPE_ANSWER_COMMENT, notify_class::CATEGORY_QUESTION, $answer['question_id'], array(
					'comment_type' => 2, 
					'from_uid' => $this->user_id, 
					'question_id' => $answer['question_id'], 
					'item_id' => $_GET['answer_id'], 
					'comment_id' => $comment_id
				));
			}
			
			$message_arr = $this->model('question')->parse_at_user($message, false, true);
			
			if ($message_arr['user'])
			{
				foreach ($message_arr['user'] as $uid)
				{
					if ($uid == $answer['uid'])
					{
						continue;
					}
					
					$this->model('notify')->send($this->user_id, $uid, notify_class::TYPE_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $answer['question_id'], array(
						'comment_type' => 2, 
						'from_uid' => $this->user_id, 
						'question_id' => $answer['question_id'], 
						'item_id' => $_GET['answer_id'], 
						'comment_id' => $comment_id
					));
				}
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => $_GET['answer_id'], 
			'type_name' => 'answer'
		), 1, "评论成功"));
	}

	public function get_answer_comments_action()
	{
		$comments = $this->model('answer')->get_answer_comments($_GET['answer_id']);
		
		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));
		
		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}
		
		$answer = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		
		TPL::assign('question', $this->model('question')->get_question_info_by_id($answer['question_id']));
		
		TPL::assign('comments', $comments);
		
		TPL::output("question/ajax/comments");
	}

	public function save_question_comment_action()
	{
		if (! $_GET['question_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "问题不存在"));
		}
		
		if (trim($_POST['message']) == '')
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "请输入评论内容"));
		}
		
		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);
		
		if ($question_info['lock'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '不能评论锁定的问题。'));
		}
		
		if ((get_setting('comment_limit') > 0) && (cjk_strlen($_POST['message']) > get_setting('comment_limit')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "评论内容字数不得超过" . get_setting('comment_limit') . "个"));
		}
		
		$message = $this->model('question')->parse_at_user($_POST['message'], false, false, true);

		if ($comment_id = $this->model('question')->insert_question_comment($_GET['question_id'], $this->user_id, $message))
		{
			$question = $this->model('question')->get_question_info_by_id($_GET['question_id']);
			
			if ($question['published_uid'] != $this->user_id)
			{
				$this->model('notify')->send($this->user_id, $question['published_uid'], notify_class::TYPE_QUESTION_COMMENT, notify_class::CATEGORY_QUESTION, $question['question_id'], array(
					'comment_type' => 1, 
					'from_uid' => $this->user_id, 
					'question_id' => $question['question_id'], 
					'comment_id' => $comment_id
				));
			}
			
			$message_arr = $this->model('question')->parse_at_user($message, false, true);
			
			if ($message_arr['user'])
			{
				foreach ($message_arr['user'] as $uid)
				{
					if ($uid == $question['published_uid'])
					{
						continue;
					}
					
					if ($uid != $this->user_id)
					{
						$this->model('notify')->send($this->user_id, $uid, notify_class::TYPE_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $question['question_id'], array(
							'comment_type' => 1, 
							'from_uid' => $this->user_id, 
							'question_id' => $question['question_id'], 
							'comment_id' => $comment_id
						));
					}
				}
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'item_id' => $_GET['question_id'], 
			'type_name' => 'question'
		), 1, "评论成功"));
	}

	public function get_question_comments_action()
	{
		$comments = $this->model('question')->get_question_comments($_GET['question_id']);
		
		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));
		
		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
		}
		
		TPL::assign('question', $this->model('question')->get_question_info_by_id($_GET['question_id']));
		
		TPL::assign('comments', $comments);
		
		TPL::output("question/ajax/comments");
	}

	public function answer_vote_action()
	{
		$answer_id = intval($_POST['answer_id']);
		$value = intval($_POST['value']);
		
		$answer_info = $this->model('answer')->get_answer_by_id($answer_id);
		
		if ($answer_info['uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "不能对自己发表的回复投票"));
		}
		
		if (! in_array($value, array(
			- 1, 
			1
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "投票值错误，无法投票。"));
		}
		
		$reputation_factor = $this->model('account')->get_group_by_id($this->user_info['reputation_group'], 'reputation_factor');
		
		$retval = $this->model('answer')->change_answer_vote($answer_id, $value, $this->user_id, $reputation_factor);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	public function cancel_question_invite_action()
	{
		$question_id = intval($_GET['question_id']);
		$recipients_uid = intval($_GET['recipients_uid']);
		
		$this->model('question')->cancel_question_invite($question_id, $this->user_id, $recipients_uid);
		
		$this->model('account')->increase_user_statistics(account_class::INVITE_COUNT, 0, $recipients_uid);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, "取消邀请成功"));
	}

	public function question_invite_delete_action()
	{
		$question_invite_id = intval($_POST['question_invite_id']);
		
		$this->model('question')->delete_question_invite($question_invite_id, $this->user_id);
		
		$this->model('account')->increase_user_statistics(account_class::INVITE_COUNT, 0, $this->user_id);
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, "删除邀请成功"));
	}
	
	public function question_thanks_action()
	{
		if ($this->user_info['integral'] < 0 AND get_setting('integral_system_enabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '你的剩余积分已经不足以进行此操作'));
		}
		
		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '问题不存在'));
		}
		
		if ($question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '不能感谢自己的问题'));
		}
		
		if ($this->model('question')->question_thanks($_POST['question_id'], $this->user_id, $this->user_info['user_name']))
		{
			$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_QUESTION_THANK, notify_class::CATEGORY_QUESTION, $_POST['question_id'], array(
				'question_id' => intval($_POST['question_id']),
				'from_uid' => $this->user_id
			));
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));
		}
	}
	
	public function question_answer_rate_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);
		
		if ($this->user_id == $answer_info['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '不能评价自己发表的回复'));
		}
		
		if ($_POST['type'] == 'thanks' && $this->model('answer')->user_rated('thanks', $_POST['answer_id'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '已感谢过该回复，请不要重复感谢。'));
		}
		
		if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y' and $_POST['type'] == 'thanks')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '你的剩余积分已经不足以进行此操作'));
		}
		
		if ($this->model('answer')->user_rate($_POST['type'], $_POST['answer_id'], $this->user_id, $this->user_info['user_name']))
		{
			if ($answer_info['uid'] != $this->user_id)
			{
				$this->model('notify')->send($this->user_id, $answer_info['uid'], notify_class::TYPE_ANSWER_THANK, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'question_id' => $answer_info['question_id'],
					'from_uid' => $this->user_id, 
					'item_id' => $answer_info['answer_id']
				));
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));
		}
		else
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));
		}
	}

	public function focus_action()
	{
		$question_id = intval($_GET['question_id']);
		
		if ($question_id == 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "问题不存在"));
		}
		
		if (! $this->model('question')->get_question_info_by_id($question_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "问题不存在"));
		}
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'type' => $this->model('question')->add_focus_question($question_id, $this->user_id)
		), 1, "关注成功"));
	}

	/**
	 *
	 * 添加回复
	 *
	 * @return boolean true|false
	 */
	public function save_answer_action()
	{
		$question_id = intval($_POST['question_id']);
		$answer_content = $_POST['answer_content'];
		$answer_content = trim($answer_content, "\r\n\t");
		
		if (! $question_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '问题不存在'));
		}
		
		if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '你的剩余积分已经不足以进行此操作'));
		}
		
		$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']);
		
		if ($question_info['lock'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定问题不能回复。'));
		}
		
		if (! $answer_content)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "请输入回复内容"));
		}
		
		// 判断是否是问题发起者
		if (get_setting('answer_self_question') == 'N' and $question_info['published_uid'] == $this->user_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "不能回复自己发布的问题，你可以修改问题内容"));
		}
		
		// 判断是否已回复过问题
		if ((get_setting('answer_unique') == 'Y') && $this->model('answer')->check_answer_question($_POST['question_id'], $this->user_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "一个问题只能回复一次，你可以编辑回复过的回复"));
		}
		
		if (strlen($answer_content) < get_setting('answer_length_lower'))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "回复内容字数不得少于 " . get_setting('answer_length_lower') . ' 字节'));
		}
		
		if (! $this->user_info['permission']['publish_url'] && FORMAT::outside_url_exists($answer_content))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "你所在的用户组不允许发布站外链接"));
		}
		
		if (human_valid('answer_valid_hour') and ! core_captcha::validate($_POST['seccode_verify'], false))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "请填写正确的验证码"));
		}
		
		// !注: 来路检测后面不能再放报错提示
		if (! valid_post_hash($_POST['post_hash']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '表单来路不正确或内容已提交, 请刷新页面重试'));
		}
		
		core_captcha::clear();
		
		if ($_POST['auto_focus'])
		{
			if (! $this->model('question')->has_focus_question($question_id, $this->user_id))
			{
				$this->model('question')->add_focus_question($question_id, $this->user_id, $_POST['anonymous']);
			}
		}
		
		$this->model('draft')->delete_draft($question_id, 'answer', $this->user_id);
		
		if ($this->publish_approval_valid())
		{
			$this->model('publish')->publish_approval('answer', array(
				'question_id' => $question_id,
				'answer_content' => $answer_content,
				'anonymous' => $_POST['anonymous'],
				'attach_access_key' => $_POST['attach_access_key']
			), $this->user_id, $_POST['attach_access_key']);
				
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/publish/wait_approval/question_id-' . $question_id . '__is_mobile-' . $_POST['is_mobile'])
			), 1, null));
		}
		else
		{
			$answer_id = $this->model('publish')->publish_answer($question_id, $answer_content, $this->user_id, $_POST['anonymous'], $_POST['attach_access_key']);
			
			if ($_POST['is_mobile'])
			{
				$url = get_js_url('/mobile/question/id-' . $question_id . '__item_id-' . $answer_id . '__rf-false');
			}
			else
			{
				$url = get_js_url('/question/id-' . $question_id . '__item_id-' . $answer_id . '__rf-false');
			}
				
			H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
			), 1, null));
		}
	}

	/**
	 * 
	 * 根据话题ID和问题ID，删除关联
	 * 
	 * @return boolean true|false
	 */
	public function delete_topic_action()
	{
		$topic_id = intval($_POST['topic_id']);
		$question_id = intval($_POST['question_id']);
		
		if ($topic_id == 0 || $question_id == 0)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '指定的问题不存在'));
		}
		
		$this->model('topic')->delete_question_topic($topic_id, $question_id);
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'topic_id' => $topic_id
		), 1, null));
	}

	/**
	 * 
	 * 根据问题ID增加话题，并直接关联
	 * 
	 * @return boolean true|false
	 */
	public function save_topic_action()
	{
		$topic_title = trim(htmlspecialchars($_POST['topic_title']));
		$question_id = intval($_GET['question_id']);
		
		if (!$question_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "问题不存在"));
		}
		
		if (empty($topic_title))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "请输入话题标题"));
		}
		
		if (preg_match('/\//i', $topic_title))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题不能包含 "/"'));
		}
		
		if (! $this->model('topic')->get_topic_id_by_title($topic_title) && (get_setting('topic_title_limit') > 0) && (cjk_strlen($topic_title) > get_setting('topic_title_limit')))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '话题标题字数不得超过' . get_setting('topic_title_limit') . '个'));
		}
		
		$question_info = $this->model('question')->get_question_info_by_id($question_id);
		
		if ($question_info['lock'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定问题不能添加话题。'));
		}
		
		if ($question_id > 0)
		{
			$topic_id = $this->model('topic')->save_topic($question_id, $topic_title, $this->user_id, 0, 1);
		}
		
		if (! $topic_id)
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', "话题已锁定, 不能添加话题"));
		}
		
		$this->model('question')->save_link($topic_id, $question_id);
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'topic_id' => $topic_id, 
			'topic_url' => get_js_url('topic/' . $topic_id)
		), 1, "添加话题成功"));
	}

	function log_action()
	{
		if (! $question_info = $this->model('question')->get_question_info_by_id($_GET['id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, "指定问题不存在"));
		}
		
		$log_list = ACTION_LOG::get_action_by_event_id($_GET['id'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}", ACTION_LOG::CATEGORY_QUESTION, implode(',', array(
			ACTION_LOG::ADD_QUESTION, 
			ACTION_LOG::MOD_QUESTON_TITLE, 
			ACTION_LOG::MOD_QUESTION_DESCRI, 
			ACTION_LOG::ADD_TOPIC, 
			ACTION_LOG::DELETE_TOPIC, 
			ACTION_LOG::REDIRECT_QUESTION, 
			ACTION_LOG::MOD_QUESTION_CATEGORY, 
			ACTION_LOG::MOD_QUESTION_ATTACH, 
			ACTION_LOG::DEL_REDIRECT_QUESTION
		)));
		
		//处理日志记录
		$log_list = $this->model('question')->analysis_log($log_list, $question_info['published_uid'], $question_info['anonymous']);
		
		if (! $unverified_modify_all = $question_info['unverified_modify'])
		{
			$unverified_modify_all = array();
		}
		
		$unverified_modify = array();
		
		foreach ($unverified_modify_all as $key => $val)
		{
			$unverified_modify = array_merge($unverified_modify, $val);
		}
		
		TPL::assign('unverified_modify', $unverified_modify);
		TPL::assign('question_info', $question_info);
		
		TPL::assign('list', $log_list);
		
		TPL::output('question/ajax/log');
	}

	function redirect_action()
	{
		$question_info = $this->model('question')->get_question_info_by_id($_POST['item_id']);
		
		if ($question_info['lock'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '锁定的问题不能设置重定向。'));
		}
		
		if (!$this->user_info['permission']['redirect_question'] && ! ($this->user_info['permission']['is_administortar'] or $this->user_info['permission']['is_moderator']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', '没有权限设置重定向。'));
		}
		
		$this->model('question')->redirect($this->user_id, $_POST['item_id'], $_POST['target_id']);
		
		if ($_POST['target_id'] AND $_POST['item_id'] AND $question_info['published_uid'] != $this->user_id)
		{
			$this->model('notify')->send($this->user_id, $question_info['published_uid'], notify_class::TYPE_REDIRECT_QUESTION, notify_class::CATEGORY_QUESTION, $_POST['item_id'], array(
				'from_uid' => $this->user_id, 
				'question_id' => intval($_POST['item_id'])
			));
		}
		
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	function email_invite_action()
	{
		$email = $_POST['email'];
		
		if (! H::valid_email($email))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '请填写正确的 Email'));
		}
		
		if ($email == $this->user_info['email'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '不能邀请自己'));
		}
		
		if ($this->model('question')->check_email_invite($_GET['question_id'], $this->user_id, $email))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '此 Email 已接收过邀请'));
		}
		
		$this->model('question')->add_invite($_GET['question_id'], $this->user_id, 0, $_POST['email']);

		$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);
			
		$email_hash = base64_encode(H::encode_hash(array(
			'email' => $_POST['email']
		)));
			
		$this->model('email')->action_email(email_class::TYPE_INVITE_QUESTION, $email, get_js_url('/question/' . $_GET['question_id'] . '?fromuid-' . $this->user_id . '__fromemail-' . $email_hash), array(
			'user_name' => $this->user_info['user_name'], 
			'question_title' => $question_info['question_content']
		));
			
		H::ajax_json_output(AWS_APP::RSM(array(
			'question_id' => $_GET['question_id']
		), 1, '邀请成功'));
	}

	function remove_comment_action()
	{
		if (! in_array($_GET['type'], array(
			'answer', 
			'question'
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '错误的请求。'));
		}
		
		if (! $_GET['comment_id'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '评论ID错误。'));
		}
		
		$comment = $this->model($_GET['type'])->get_comment_by_id($_GET['comment_id']);
		
		if (! $this->user_info['permission']['is_moderator'] && ! $this->user_info['permission']['is_administortar'] && $this->user_id != $comment['uid'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '你没有权限删除该评论。'));
		}
		
		$this->model($_GET['type'])->remove_comment($_GET['comment_id']);
		
		if ($_GET['type'] == 'answer')
		{
			$this->model('answer')->update_answer_comments_count($comment['answer_id']);
		}
		else if ($_GET['type'] == 'question')
		{
			$this->model('question')->update_question_comments_count($comment['question_id']);
		}
			
		H::ajax_json_output(AWS_APP::RSM(null, 1, null));
	}

	function answer_force_fold_action()
	{
		if (! $this->user_info['permission']['is_moderator'] && ! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '你所在的用户组没有权限强制折叠回复'));
		}
		
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);
		
		if (! $answer_info)
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '回复不存在'));
		}
		
		if (! $answer_info['force_fold'])
		{
			$this->model('answer')->update_answer_by_id($_POST['answer_id'], array(
				'force_fold' => 1
			));
			
			if (! $this->model('integral')->fetch_log($answer_info['uid'], 'ANSWER_FOLD_' . $answer_info['answer_id']))
			{
				$this->model('integral')->process($answer_info['uid'], 'ANSWER_FOLD_' . $answer_info['answer_id'], get_setting('integral_system_config_answer_fold'), '回复折叠 #' . $answer_info['answer_id']);
				
				ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_ANSWER . ' AND associate_id = ' . $answer_info['answer_id']);	// 删除动作
			
				ACTION_LOG::delete_action_history('associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ANSWER_QUESTION . ' AND associate_attached = ' . $answer_info['answer_id']);	// 删除动作
			}
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'fold'
			), 1, "强制折叠回复"));
		}
		else
		{
			$this->model('answer')->update_answer_by_id($_POST['answer_id'], array(
				'force_fold' => 0
			));
			
			H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'unfold'
			), 1, "撤销折叠回复"));
		}
	}

	function lock_action()
	{
		$question_id = intval($_POST['question_id']);
		
		if (! $this->user_info['permission']['is_moderator'] && ! $this->user_info['permission']['is_administortar'])
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '你没有操作锁定/解锁问题的权限'));
		}
		
		if (! $question_info = $this->model('question')->get_question_info_by_id($question_id))
		{
			H::ajax_json_output(AWS_APP::RSM(null, - 1, '问题不存在'));
		}
		
		$this->model('question')->lock_question($question_id, !$question_info['lock']);
		
		H::ajax_json_output(AWS_APP::RSM(array(
			'url' => ''
		), - 1, ''));
	}

	public function get_report_reason_action()
	{
		if ($report_reason = explode("\n", get_setting('report_reason')))
		{
			$data = array();
			
			foreach ($report_reason as $key => $val)
			{
				$val = trim($val);
				
				if ($val)
				{
					$data[] = $val;
				}
			}
		}
		
		H::ajax_json_output(AWS_APP::RSM($data, 1));
	}

	public function save_report_action()
	{
		$reason = trim($_POST['reason']);
		
		if (empty($reason))
		{
			H::ajax_json_output(AWS_APP::RSM(array(
				'tips_id' => 'share_send'
			), - 1, '请填写举报理由'));
		}
		
		$this->model('question')->save_report($this->user_id, $_POST['type'], $_POST['target_id'], htmlspecialchars($reason), $_SERVER['HTTP_REFERER']);
		
		$recipient_uid = get_setting('report_message_uid') ? get_setting('report_message_uid') : 1;
			
		$this->model('message')->send_message($this->user_id, $recipient_uid, null, '有新的举报, 请登录后台查看处理: ' . get_setting('base_url') . '/?/admin/question/report_list/');
			
		H::ajax_json_output(AWS_APP::RSM(null, 1, '举报成功'));
	}
}