
ALTER TABLE `qa_posts`
	CHANGE COLUMN `content` `content` LONGTEXT NULL AFTER `title`;
ALTER TABLE `qa_contentwords`
    CHANGE COLUMN `count` `count` INT UNSIGNED NOT NULL AFTER `wordid`;

	-- --------------------------------------------------------
	-- 主机:                           127.0.0.1
	-- 服务器版本:                        5.7.20-log - MySQL Community Server (GPL)
	-- 服务器操作系统:                      Win32
	-- HeidiSQL 版本:                  9.4.0.5125
	-- --------------------------------------------------------

	/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
	/*!40101 SET NAMES utf8 */;
	/*!50503 SET NAMES utf8mb4 */;
	/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
	/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

	CREATE TABLE `zhihu_fetch` (
		`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`type` TINYINT(3) UNSIGNED NOT NULL,
		`postid` INT(10) UNSIGNED NOT NULL,
		`state` TINYINT(4) NOT NULL COMMENT '0 未处理 1 已转载 2 放弃 3 等待回复',
		`qid` BIGINT(20) UNSIGNED NOT NULL,
		`aid` BIGINT(20) UNSIGNED NOT NULL,
		`title` TEXT NOT NULL,
		`detail` LONGTEXT NOT NULL,
		`comment` VARCHAR(255) NOT NULL COMMENT '管理员备注',
		`edit_time` DATETIME NOT NULL,
		`fetch_time` DATETIME NOT NULL,
		PRIMARY KEY (`id`)
	)
	COLLATE='utf8_general_ci'
	ENGINE=InnoDB
	AUTO_INCREMENT=2240
	;

	-- 数据导出被取消选择。
	-- 导出  表 question2answer.zhihu_user 结构
	CREATE TABLE IF NOT EXISTS `zhihu_user` (
	  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `userid` int(10) unsigned NOT NULL,
	  `username` varchar(150) NOT NULL,
	  `showname` varchar(150) NOT NULL,
	  `fetch_time` datetime NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

	-- 数据导出被取消选择。
	/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
	/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
	/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
