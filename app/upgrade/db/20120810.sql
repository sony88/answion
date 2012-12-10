ALTER TABLE `[#DB_PREFIX#]system_setting` CHANGE `varname` `varname` VARCHAR( 255 ) NOT NULL COMMENT '字段名';
UPDATE `[#DB_PREFIX#]system_setting` SET `varname` = 'request_route_custom' WHERE `varname` = 'request_route';
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('request_route', 's:2:"99";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('request_route_sys_1', 's:225:"/home/explore/category-(:num)===/category/(:num)\n/home/explore/===/explore/\n/home/explore/guest===/guest\n/people/list/===/users/\n/account/login/===/login/\n/account/logout/===/logout/\n/account/setting/(:any)/===/setting/(:any)/";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('request_route_sys_2', 's:315:"/question/(:any)===/q_(:any)\n/topic/(:any)===/t_(:any).html\n/people/(:any)===/p_(:any).html\n/home/explore/category-(:num)===/c_(:num).html\n/home/explore/===/explore/\n/home/explore/guest===/guest\n/people/list/===/users/\n/account/login/===/login/\n/account/logout/===/logout/\n/account/setting/(:any)/===/setting/(:any)/";');

CREATE TABLE `[#DB_PREFIX#]feature` (
	`id` INT( 11 ) NULL AUTO_INCREMENT ,
	`title` VARCHAR( 200 ) NULL DEFAULT NULL COMMENT '专题标题',
	`description` VARCHAR( 255 ) NULL DEFAULT NULL COMMENT '专题描述',
	`icon` VARCHAR( 255 ) NULL DEFAULT NULL COMMENT '专题图标',
	`topic_count` INT( 11 ) NULL DEFAULT '0' COMMENT '话题计数',
	`css` TEXT NULL DEFAULT NULL COMMENT '自定义CSS',
	PRIMARY KEY ( `id` )
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

CREATE TABLE `[#DB_PREFIX#]feature_topic` (
	`id` INT( 11 ) NULL AUTO_INCREMENT ,
	`feature_id` INT( 11 ) NULL DEFAULT '0' COMMENT '专题ID',
	`topic_id` INT( 11 ) NULL DEFAULT '0' COMMENT '话题ID',
	PRIMARY KEY ( `id` ),
	KEY `feature_id` (`feature_id`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_enabled', 's:1:"N";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_register', 's:4:"2000";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_profile', 's:3:"100";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_invite', 's:3:"200";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_best_answer', 's:3:"200";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_answer_fold', 's:3:"-50";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_new_question', 's:3:"-20";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_new_answer', 's:2:"-5";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_thanks', 's:3:"-10";');
INSERT INTO `[#DB_PREFIX#]system_setting` (`varname`, `value`) VALUES ('integral_system_config_invite_answer', 's:3:"-10";');

ALTER TABLE `[#DB_PREFIX#]users` ADD `integral` INT( 11 ) NULL DEFAULT '0';

CREATE TABLE `[#DB_PREFIX#]integral_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT '0',
  `action` varchar(16) DEFAULT NULL,
  `integral` int(11) DEFAULT NULL,
  `note` varchar(128) DEFAULT NULL,
  `balance` int(11) DEFAULT '0',
  `time` int(10) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `action` (`action`),
  KEY `time` (`time`)
) ENGINE=[#DB_ENGINE#] DEFAULT CHARSET=utf8;

INSERT INTO  `[#DB_PREFIX#]integral_log` (
 `uid` ,
 `action`,
 `integral`,
 `note`,
 `balance`,
 `time`
)
SELECT  `uid` ,  'REGISTER' ,  '2000',  '初始资本',  '2000' , `reg_time` FROM `[#DB_PREFIX#]users`;

UPDATE `[#DB_PREFIX#]users` SET `integral` = 2000;