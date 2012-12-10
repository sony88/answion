<?php

$config[1] = array(
	'id' => 1,
	'title' => '全局',
	'cname' => 'system',
	'children' => array(
		array(
			'id' => 100,
			'title' => '管理首页',
			'url' => 'admin/',
		),
		
		array(
			'id' => 101,
			'title' => '站点信息',
			'url' => 'admin/setting/type-site_info',
		),
		array(
			'id' => 102,
			'title' => '注册与访问',
			'url' => 'admin/setting/type-reg_visit',
		),
		array(
			'id' => 103,
			'title' => '站点功能',
			'url' => 'admin/setting/type-site_func',
		),
		array(
			'id' => 104,
			'title' => '内容设置',
			'url' => 'admin/setting/type-content',
		),
		array(
			'id' => 106,
			'title' => '积分与威望设置',
			'url' => 'admin/setting/type-integral_reputation',
		),
		array(
			'id' => 111,
			'title' => '用户权限',
			'url' => 'admin/setting/type-user_privilege',
		),
		array(
			'id' => 107,
			'title' => '邮件设置',
			'url' => 'admin/setting/type-email',
		),
		array(
			'id' => 108,
			'title' => '开放平台',
			'url' => 'admin/setting/type-open',
		),
/*
		array(
			'id' => 109,
			'title' => '性能优化',
			'url' => 'admin/setting/type-cache',
		),
*/
	)
);

$config[2] = array(
	'id' => 2,
	'title' => '界面',
	'cname' => 'topic',
	'children' => array(
		array(
			'id' => 201,
			'title' => '界面设置',
			'url' => 'admin/setting/type-view_set',
		),
		array(
			'id' => 202,
			'title' => '导航设置',
			'url' => 'admin/nav_menu/',
		),
		array(
			'id' => 203,
			'title' => '编辑器',
			'url' => 'admin/setting/type-editor',
		)
	)
);

$config[3] = array(
	'id' => 3,
	'title' => '内容',
	'cname' => 'question',
	'children' => array(
		array(
			'id' => 300,
			'title' => '内容审核',
			'url' => 'admin/question/approval_list/',
		),
		
		array(
			'id' => 301,
			'title' => '问题列表',
			'url' => 'admin/question/question_list/',
		),
		array(
			'id' => 302,
			'title' => '分类设置',
			'url' => 'admin/category/list/',
		),
		array(
			'id' => 303,
			'title' => '话题列表',
			'url' => 'admin/topic/list/',
		),
		array(
			'id' => 304,
			'title' => '专题管理',
			'url' => 'admin/feature/list/',
		),
		array(
			'id' => 306,
			'title' => '用户举报',
			'url' => 'admin/question/report_list/',
		),
		array(
			'id' => 305,
			'title' => '词语过滤',
			'url' => 'admin/setting/type-sensitive_words',
		),
	)
);

$config[4] = array(
	'id' => 4,
	'title' => '用户',
	'cname' => 'user',
	'children' => array(
		array(
			'id' => 401,
			'title' => '在线会员',
			'url' => 'admin/user_manage/online_list/',
		),
		array(
			'id' => 402,
			'title' => '会员列表',
			'url' => 'admin/user_manage/list/',
		),
		array(
			'id' => 403,
			'title' => '用户组',
			'url' => 'admin/user_manage/group_list/',
		),
		array(
			'id' => 405,
			'title' => '添加用户',
			'url' => 'admin/user_manage/user_add/',
		),
		array(
			'id' => 406,
			'title' => '批量邀请',
			'url' => 'admin/user_manage/invites/',
		),
		array(
			'id' => 407,
			'title' => '职位设置',
			'url' => 'admin/user_manage/job_list/',
		),
		array(
			'id' => 408,
			'title' => '查看禁封用户',
			'url' => 'admin/user_manage/forbidden_list/',
		)
	)
);


$config[6] = array(
	'id' => 6,
	'title' => '运营',
	'cname' => 'question',
	'children' => array(
		array(
			'id' => 602,
			'title' => '数据统计',
			'url' => 'admin/statistic/',
		),
		array(
			'id' => 603,
			'title' => '今日话题',
			'url' => 'admin/setting/type-today_topics',
		)
	)
);

$config[7] = array(
	'id' => 7,
	'title' => '邮件群发',
	'cname' => 'question',
	'children' => array(
		array(
			'id' => 701,
			'title' => '用户群管理',
			'url' => 'admin/edm/groups/',
		),
		array(
			'id' => 702,
			'title' => '任务管理',
			'url' => 'admin/edm/tasks/',
		),
	)
);

$config[5] = array(
	'id' => 5,
	'title' => '工具',
	'cname' => 'maintain',
	'children' => array(
		array(
			'id' => 501,
			'title' => '更新缓存',
			'url' => 'admin/tool/cache_clean/',
		),
		array(
			'id' => 502,
			'title' => '更新搜索索引',
			'url' => 'admin/tool/update_search_index/',
		),
		array(
			'id' => 503,
			'title' => '数据库',
			'url' => 'admin/tool/database/',
		),
		array(
			'id' => 504,
			'title' => '更新威望数据',
			'url' => 'admin/tool/reputation_rebuild/',
		),
		array(
			'id' => 505,
			'title' => '转换 BBcode',
			'url' => 'admin/tool/bbcode_to_markdown/',
		),
	)
);

