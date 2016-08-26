/*
SQLyog 企业版 - MySQL GUI v8.14 
MySQL - 5.0.67-log : Database - 51Wom
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `t_action_log` */

USE 51wom;

-- DROP TABLE IF EXISTS `t_action_log`;

CREATE TABLE `t_action_log` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iType` tinyint(4) NOT NULL COMMENT '类型(1=前端，2=后台)',
  `sUserName` varchar(100) NOT NULL COMMENT '当前用户',
  `sUrl` varchar(200) NOT NULL COMMENT '请求地址',
  `sIP` varchar(15) NOT NULL COMMENT '当前IP',
  `sParam` mediumtext NOT NULL COMMENT '参数内容',
  `sSQL` mediumtext NOT NULL COMMENT '执行SQL',
  `iStatus` tinyint(4) NOT NULL,
  `iCreateTime` int(11) NOT NULL,
  `iUpdateTime` int(11) NOT NULL,
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=438 DEFAULT CHARSET=utf8;

/*Table structure for table `t_ad` */

-- DROP TABLE IF EXISTS `t_ad`;

CREATE TABLE `t_ad` (
  `iAdID` int(11) NOT NULL auto_increment COMMENT '广告ID',
  `iUserID` int(11) NOT NULL COMMENT '创建帐号ID',
  `sAdName` varchar(50) NOT NULL COMMENT '广告名称',
  `iPlanMinMoney` int(11) NOT NULL COMMENT '最小投放预算',
  `iPlanMaxMoney` int(11) NOT NULL COMMENT '最大投放预算',
  `iPlanTime` int(11) NOT NULL COMMENT '投放时间',
  `iMediaType` tinyint(4) NOT NULL COMMENT '媒体类型(1=公众号,2=朋友圈,3=新浪微博,4=新闻论坛)',
  `iAdType` tinyint(4) NOT NULL COMMENT '广告类型（3=全选，1=硬广,2=软广)',
  `sCatID` varchar(50) NOT NULL COMMENT '媒体分类(多选，逗号隔开)',
  `sCityID` varchar(50) NOT NULL COMMENT '所在城市(多选，逗号隔开)',
  `iTotalMoney` decimal(10,2) NOT NULL COMMENT '总价',
  `iPayID` int(11) NOT NULL COMMENT '对应t_cash_out表的iAutoID',
  `iPayStatus` tinyint(4) NOT NULL COMMENT '支付状态(0=未付款，1=已付款)',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除，1=待审核，2=审核通过，3=审核未通过，4=完成，5=未填写完成)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAdID`)
) ENGINE=MyISAM AUTO_INCREMENT=97 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='广告投放记录';

/*Table structure for table `t_ad_friend` */

-- DROP TABLE IF EXISTS `t_ad_friend`;

CREATE TABLE `t_ad_friend` (
  `iAdID` int(11) NOT NULL auto_increment COMMENT '广告ID',
  `iAdPos` tinyint(4) NOT NULL COMMENT '投放形式',
  `iPlanTime` int(11) NOT NULL COMMENT '投放时间',
  `sForwardUrl` varchar(200) NOT NULL COMMENT '转发链接',
  `sForwardText` varchar(500) NOT NULL COMMENT '转发文字',
  `sForwardImg` varchar(500) NOT NULL COMMENT '转发配图(多张逗号隔开)',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAdID`)
) ENGINE=MyISAM AUTO_INCREMENT=76 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1;

/*Table structure for table `t_ad_media` */

-- DROP TABLE IF EXISTS `t_ad_media`;

CREATE TABLE `t_ad_media` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iAdID` int(11) NOT NULL COMMENT '广告ID',
  `iAUserID` int(11) NOT NULL COMMENT '广告用户ID',
  `iMUserID` int(11) NOT NULL COMMENT '媒体用户ID',
  `iMediaID` int(11) NOT NULL COMMENT '媒体ID',
  `iAdPos` tinyint(4) NOT NULL COMMENT '广告位',
  `iMoney` decimal(10,2) NOT NULL COMMENT '价格',
  `iPlanTime` int(11) NOT NULL COMMENT '执行时间',
  `iChoose` tinyint(4) NOT NULL default '1' COMMENT '是否选中(0=未选中，1=已选中）',
  `sPreviewUrl` varchar(200) NOT NULL COMMENT '预览地址',
  `iCheck` tinyint(4) NOT NULL default '0' COMMENT '广告是否审核通过',
  `sEffectImg` varchar(50) NOT NULL COMMENT '投放效果图',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=已删除，1=待付款，2=待接单，3=待提交预览，4=待内容确认，5=待投放，6=待提交效果，7=待确认效果，8=已完成，11=拒绝接单)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAutoID`),
  UNIQUE KEY `iAdID` (`iAdID`,`iMediaID`),
  KEY `iMediaID` (`iMediaID`)
) ENGINE=MyISAM AUTO_INCREMENT=109 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='广告投放选择的自媒体';

/*Table structure for table `t_ad_news` */

-- DROP TABLE IF EXISTS `t_ad_news`;

CREATE TABLE `t_ad_news` (
  `iAdID` int(11) NOT NULL COMMENT '广告ID',
  `iAdPos` tinyint(4) NOT NULL COMMENT '投放形式',
  `iPlanTime` int(11) NOT NULL COMMENT '投放时间',
  `sTitle` varchar(200) NOT NULL COMMENT '标题',
  `sContent` mediumtext NOT NULL COMMENT '内容',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '结束时间',
  PRIMARY KEY  (`iAdID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='广告投放的新闻信息';

/*Table structure for table `t_ad_weibo` */

-- DROP TABLE IF EXISTS `t_ad_weibo`;

CREATE TABLE `t_ad_weibo` (
  `iAdID` int(11) NOT NULL COMMENT '广告ID',
  `iAdPos` tinyint(4) NOT NULL COMMENT '投放形式',
  `iPlanTime` int(11) NOT NULL COMMENT '投放时间',
  `sForwardUrl` varchar(200) NOT NULL COMMENT '转发链接',
  `sForwardText` varchar(500) NOT NULL COMMENT '转发文字',
  `sForwardImg` varchar(500) NOT NULL COMMENT '转发配图(多张逗号隔开)',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAdID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='广告投放微博信息';

/*Table structure for table `t_ad_weixin` */

-- DROP TABLE IF EXISTS `t_ad_weixin`;

CREATE TABLE `t_ad_weixin` (
  `iAdID` int(11) NOT NULL COMMENT '广告ID',
  `iAdPos` tinyint(4) NOT NULL COMMENT '广告位',
  `iPlanTime` int(11) NOT NULL COMMENT '显示时间',
  `sImportUrl` varchar(200) NOT NULL COMMENT '导入URL',
  `sWordFile` varchar(50) NOT NULL COMMENT '上传Word',
  `sTitle` varchar(100) NOT NULL COMMENT '标题',
  `sCoverImg` varchar(50) NOT NULL COMMENT '封面图',
  `iIsCover` tinyint(4) NOT NULL default '1' COMMENT '是否封面',
  `sAbstract` varchar(500) NOT NULL COMMENT '摘要',
  `sContent` mediumtext NOT NULL COMMENT '内容',
  `sOriginalUrl` varchar(200) NOT NULL COMMENT '原链接',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAdID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='广告投放的微信信息';

/*Table structure for table `t_admin` */

-- DROP TABLE IF EXISTS `t_admin`;

CREATE TABLE `t_admin` (
  `iAdminID` int(11) NOT NULL auto_increment COMMENT '唯一编号',
  `sAdminName` varchar(50) NOT NULL default '' COMMENT '用户名',
  `sPassword` varchar(50) NOT NULL default '' COMMENT '用户密码',
  `sEditPassword` varchar(100) default NULL COMMENT '修改操作密码',
  `sMobile` varchar(20) NOT NULL default '' COMMENT '手机号',
  `sEmail` varchar(250) NOT NULL default '' COMMENT '电子邮件',
  `sRealName` varchar(50) NOT NULL default '' COMMENT '真实姓名',
  `iCityID` int(11) NOT NULL default '0' COMMENT '默认城市',
  `sCityID` varchar(255) NOT NULL default '' COMMENT '管理的城市(多选,逗号隔开)',
  `sRoleID` varchar(255) NOT NULL default '' COMMENT '用户角色(多选,逗号隔开)',
  `sPosition` varchar(20) NOT NULL default '' COMMENT '用户职位',
  `iStatus` tinyint(1) NOT NULL default '1' COMMENT '当前状态(0:不正常,1:正常)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建日期',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '最后修改日期',
  PRIMARY KEY  (`iAdminID`),
  UNIQUE KEY `sUserName` (`sAdminName`)
) ENGINE=MyISAM AUTO_INCREMENT=4271 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='后台用户表';

/*Table structure for table `t_badword` */

-- DROP TABLE IF EXISTS `t_badword`;

CREATE TABLE `t_badword` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `sWord` varchar(20) NOT NULL COMMENT '敏感词',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=无效,1=有效)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='敏感词';

/*Table structure for table `t_banner` */

-- DROP TABLE IF EXISTS `t_banner`;

CREATE TABLE `t_banner` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `title` varchar(250) NOT NULL COMMENT '标题',
  `imgurl` varchar(250) NOT NULL COMMENT '图片地址',
  `link` varchar(250) NOT NULL COMMENT '链接',
  `rank` int(11) NOT NULL COMMENT '顺序',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=无效,1=有效)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='banner滚动表';

/*Table structure for table `t_city` */

-- DROP TABLE IF EXISTS `t_city`;

CREATE TABLE `t_city` (
  `iCityID` int(10) NOT NULL auto_increment COMMENT '城市ID',
  `sCityName` varchar(20) NOT NULL default '' COMMENT '城市名称',
  `sCityCode` varchar(50) NOT NULL default '' COMMENT '城市代码',
  `iFrontShow` tinyint(4) NOT NULL default '1' COMMENT '前台启用',
  `iBackendShow` tinyint(4) NOT NULL default '1' COMMENT '后台启用',
  `iOrder` int(11) NOT NULL COMMENT '排序',
  `iStatus` tinyint(1) NOT NULL default '1' COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '最后更新时间',
  PRIMARY KEY  (`iCityID`),
  UNIQUE KEY `sCityName` (`sCityName`),
  KEY `sPinyin` (`sCityCode`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='城市表';

/*Table structure for table `t_domain` */

-- DROP TABLE IF EXISTS `t_domain`;

CREATE TABLE `t_domain` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '分类ID',
  `iType` tinyint(4) NOT NULL COMMENT '类型(1=公众号标签,2=公众号分类,3=推荐级别,4=朋友圈标签，5=新浪微博标签，6=新闻论坛标签，7=月友圈分类，8=新浪微博分类，9=新闻论坛标签，10=自媒体圈子，11=行业,12=提现帐号类型)',
  `sName` varchar(50) NOT NULL COMMENT '分类名',
  `iParentID` int(11) NOT NULL default '0' COMMENT '父类ID',
  `iOrder` int(11) NOT NULL default '0' COMMENT '排序',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=无效,1=有效)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTim` int(11) NOT NULL default '0' COMMENT '最后更新时间',
  PRIMARY KEY  (`iAutoID`),
  UNIQUE KEY `iType` (`iType`,`sName`)
) ENGINE=MyISAM AUTO_INCREMENT=219 DEFAULT CHARSET=utf8;

/*Table structure for table `t_file` */

-- DROP TABLE IF EXISTS `t_file`;

CREATE TABLE `t_file` (
  `iAutoID` int(11) unsigned NOT NULL auto_increment COMMENT '流水号',
  `sKey` char(40) NOT NULL default '' COMMENT '文件Key',
  `sExt` char(10) NOT NULL default '' COMMENT '文件扩展',
  `iHostID` tinyint(2) NOT NULL default '1' COMMENT '主机号',
  `iCreateTime` int(11) unsigned NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) unsigned NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  UNIQUE KEY `uiq_skey` (`sKey`)
) ENGINE=MyISAM AUTO_INCREMENT=83 DEFAULT CHARSET=utf8;

/*Table structure for table `t_file_meta` */

-- DROP TABLE IF EXISTS `t_file_meta`;

CREATE TABLE `t_file_meta` (
  `iAutoID` int(11) unsigned NOT NULL auto_increment COMMENT '流水号',
  `sKey` char(40) NOT NULL default '' COMMENT '文件Key',
  `sName` varchar(255) NOT NULL default '' COMMENT '文件名',
  `sMimeType` varchar(100) NOT NULL default '' COMMENT '文件类型',
  `iBID` tinyint(4) NOT NULL default '0' COMMENT '业务ID',
  `iSize` int(11) NOT NULL COMMENT '文件大小',
  `iWidth` int(11) NOT NULL default '0' COMMENT '图片宽度',
  `iHeight` int(11) NOT NULL default '0' COMMENT '图片高度',
  `iIP` int(11) unsigned NOT NULL default '0' COMMENT 'IP地址',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  UNIQUE KEY `uniq_skey` (`sKey`)
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8;

/*Table structure for table `t_finance` */

-- DROP TABLE IF EXISTS `t_finance`;

CREATE TABLE `t_finance` (
  `iAutoID` bigint(11) NOT NULL auto_increment COMMENT '流水号',
  `iUserID` int(11) NOT NULL COMMENT '用户ID',
  `iPayment` tinyint(4) NOT NULL default '1' COMMENT '收支情况(1=收入,2=支出)',
  `iSource` int(11) NOT NULL COMMENT '事件来源(1=自主充值，2=付款充值,3=拒单退款,4=>取现,5=>广告费用)',
  `sRealName` varchar(20) NOT NULL COMMENT '申请人',
  `iPayType` tinyint(4) NOT NULL COMMENT '支付类型(0=无，1=支付宝，2=微信，3=银行卡)',
  `iPayMoney` decimal(10,2) NOT NULL COMMENT '本次金额',
  `iUserMoney` decimal(10,2) NOT NULL COMMENT '用户余额',
  `sOpenName` varchar(20) default NULL COMMENT '银行开户姓名',
  `sBankName` varchar(50) NOT NULL COMMENT '开户银行(细到分行)',
  `sPayAccount` varchar(200) NOT NULL COMMENT '支付帐号(充值时为充值的支付帐号，提现时为提现到的帐号)',
  `iPayStatus` tinyint(4) NOT NULL COMMENT '支付状态（0=未支付,1=已支付),充值时为用户是否支付，提现时为已方是否支付',
  `sPayOrder` varchar(50) NOT NULL COMMENT '支付流水号(只有充值的时候有)',
  `sMyOrder` varchar(50) NOT NULL COMMENT '下单ID',
  `sRemark` varchar(200) NOT NULL COMMENT '备注',
  `sVoucher` varchar(200) default NULL COMMENT '凭证上传',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  KEY `sMyOrder` (`sMyOrder`)
) ENGINE=MyISAM AUTO_INCREMENT=97 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1;

/*Table structure for table `t_follower` */

-- DROP TABLE IF EXISTS `t_follower`;

CREATE TABLE `t_follower` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `sTitle` varchar(20) NOT NULL COMMENT '粉丝期间',
  `iMinPrice` int(11) NOT NULL COMMENT '最小粉丝数',
  `iMaxPrice` int(11) NOT NULL COMMENT '最大粉丝数',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='搜索数字段配置';

/*Table structure for table `t_home_case` */

-- DROP TABLE IF EXISTS `t_home_case`;

CREATE TABLE `t_home_case` (
  `iAutoID` int(11) unsigned NOT NULL auto_increment COMMENT '流水号',
  `iType` int(11) NOT NULL COMMENT '类型(1=电商案例,2=O2O案例,3=快销案例,4=品牌推广,5=汽车营销)',
  `sTitle` varchar(50) NOT NULL COMMENT '标题',
  `sImage` varchar(200) NOT NULL COMMENT '图片',
  `sUrl` varchar(200) NOT NULL COMMENT '链接地址',
  `sDesc` varchar(500) NOT NULL COMMENT '内容',
  `iUserNum` int(11) NOT NULL COMMENT '覆盖用户',
  `iReadNum` int(11) NOT NULL COMMENT '总阅读',
  `iZanNum` int(11) NOT NULL COMMENT '赞数',
  `iAvgZan` int(11) NOT NULL COMMENT '人均点赞',
  `sMediaName1` varchar(50) NOT NULL COMMENT '媒体名称',
  `sOpenName1` varchar(50) NOT NULL COMMENT '媒体帐号',
  `sFollowerNum1` varchar(20) NOT NULL COMMENT '关注数',
  `sMediaImage1` varchar(50) NOT NULL COMMENT '媒体图片',
  `sMediaUrl1` varchar(200) NOT NULL COMMENT '查看报价URL',
  `sMediaName2` varchar(50) NOT NULL COMMENT '媒体名称',
  `sOpenName2` varchar(50) NOT NULL COMMENT '媒体帐号',
  `sFollowerNum2` varchar(20) NOT NULL COMMENT '关注数',
  `sMediaImage2` varchar(50) NOT NULL COMMENT '媒体图片',
  `sMediaUrl2` varchar(200) NOT NULL COMMENT '查看报价URL',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '是否有效',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

/*Table structure for table `t_home_case_lo` */

-- DROP TABLE IF EXISTS `t_home_case_lo`;

CREATE TABLE `t_home_case_lo` (
  `iAutoID` int(11) unsigned NOT NULL auto_increment COMMENT '流水号',
  `iType` int(11) NOT NULL COMMENT '类型(1=电商案例,2=O2O案例,3=快销案例,4=品牌推广,5=汽车营销)',
  `sTitle` varchar(50) NOT NULL COMMENT '标题',
  `sImage` varchar(200) NOT NULL COMMENT '图片',
  `sUrl` varchar(200) NOT NULL COMMENT '链接地址',
  `sContent` text NOT NULL COMMENT '内容',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '是否有效',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;

/*Table structure for table `t_home_manager` */

-- DROP TABLE IF EXISTS `t_home_manager`;

CREATE TABLE `t_home_manager` (
  `iAutoID` int(11) unsigned NOT NULL auto_increment COMMENT '流水号',
  `sName` varchar(50) NOT NULL COMMENT '经理名',
  `sImage` varchar(200) NOT NULL COMMENT '经理头像',
  `sUrl` varchar(200) NOT NULL COMMENT '链接地址',
  `sDesc` varchar(1000) NOT NULL COMMENT '内容',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '是否有效',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

/*Table structure for table `t_kv` */

-- DROP TABLE IF EXISTS `t_kv`;

CREATE TABLE `t_kv` (
  `sKey` varchar(50) NOT NULL,
  `sData` varchar(500) NOT NULL,
  `iStatus` tinyint(1) NOT NULL default '1',
  `iCreateTime` int(11) NOT NULL default '0',
  `iUpdateTime` int(11) NOT NULL default '0',
  PRIMARY KEY  (`sKey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `t_media` */

-- DROP TABLE IF EXISTS `t_media`;

CREATE TABLE `t_media` (
  `iMediaID` int(11) NOT NULL auto_increment COMMENT '媒体ID',
  `iUserID` int(11) NOT NULL COMMENT '归属帐号',
  `iMediaType` tinyint(4) NOT NULL COMMENT '媒体类型(1：微信，2：微博，3：朋友圈，4：新闻)',
  `sMediaName` varchar(200) NOT NULL COMMENT '帐号名称',
  `sOpenName` varchar(50) NOT NULL COMMENT '公众号名',
  `iFollowerNum` int(11) NOT NULL COMMENT '粉丝数量',
  `sFollowerImg` varchar(50) NOT NULL COMMENT '粉丝截图',
  `sUrl` varchar(200) NOT NULL COMMENT '微博地址',
  `sAvatar` varchar(50) NOT NULL COMMENT '头像',
  `sQRCode` varchar(50) NOT NULL COMMENT '二维码',
  `iPrice1` int(11) NOT NULL default '0' COMMENT '报价(微信公众号单图文|朋友圈直发报价)',
  `iPrice2` int(11) NOT NULL default '0' COMMENT '报价(微信公众号第一条|朋友圈转发报价)',
  `iPrice3` int(11) NOT NULL default '0' COMMENT '报价(微信公众号第二条|朋友圈无)',
  `iPrice4` int(11) NOT NULL default '0' COMMENT '报价(微信公众号其它|朋友圈无)',
  `iReadNum` int(11) NOT NULL default '0' COMMENT '阅读量',
  `iScore` int(11) NOT NULL COMMENT '评分',
  `iRecommendLevel` tinyint(4) NOT NULL COMMENT '推荐级别',
  `iPersonCharge` int(10) unsigned NOT NULL default '0' COMMENT '负责人，资源添加人',
  `iAuditperson` int(10) unsigned NOT NULL default '0' COMMENT '审核人',
  `iAttribute` int(8) unsigned NOT NULL default '0' COMMENT '属性',
  `sWxLink` varchar(200) NOT NULL COMMENT '微信链接地址，搜狗的',
  `iVerifyState` tinyint(4) unsigned NOT NULL default '0' COMMENT '认证状态',
  `sIntroduction` text COMMENT '简介',
  `sTypeInfo` text COMMENT '用户所属类目',
  `sCooperateLevelInfo` text COMMENT '合作等级',
  `sCertifiedText` text COMMENT '认证信息介绍',
  `iClickNumber` int(8) unsigned NOT NULL default '0' COMMENT '点击量：clicknumber【点击详情数量加+1】',
  `iChoiceNumber` int(8) unsigned NOT NULL default '0' COMMENT '被选择执行的数量：choicenumber【微信资源选中放到任务单里时数量加+1】',
  `iArticleNumber` int(10) unsigned NOT NULL default '0' COMMENT '文章发布数量',
  `iZambiaNumber` int(10) unsigned NOT NULL default '0' COMMENT '文章点赞平均数',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=已删除,1=已审核,2=未审核,3=未发布完成)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  `iPrice5` int(11) NOT NULL,
  `iPrice6` int(11) NOT NULL,
  `iPrice7` int(11) NOT NULL,
  `iPrice8` int(11) NOT NULL,
  PRIMARY KEY  (`iMediaID`),
  UNIQUE KEY `iMediaType` (`iMediaType`,`sMediaName`),
  KEY `iUserID` (`iUserID`)
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='自媒体信息表';

/*Table structure for table `t_media_category` */

-- DROP TABLE IF EXISTS `t_media_category`;

CREATE TABLE `t_media_category` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iMediaID` int(11) NOT NULL COMMENT '媒体ID',
  `iCategoryID` int(11) NOT NULL COMMENT '分类ID',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  KEY `iMediaID` USING BTREE (`iMediaID`,`iCategoryID`)
) ENGINE=MyISAM AUTO_INCREMENT=69 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='自媒体关联分类表';

/*Table structure for table `t_media_city` */

-- DROP TABLE IF EXISTS `t_media_city`;

CREATE TABLE `t_media_city` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iMediaID` int(11) NOT NULL COMMENT '媒体ID',
  `iCityID` int(11) NOT NULL COMMENT '城市ID',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  KEY `iMediaID` USING BTREE (`iMediaID`,`iCityID`)
) ENGINE=MyISAM AUTO_INCREMENT=66 DEFAULT CHARSET=utf8 COMMENT='自媒体关联城市表';

/*Table structure for table `t_media_cricle` */

-- DROP TABLE IF EXISTS `t_media_cricle`;

CREATE TABLE `t_media_cricle` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iMediaID` int(11) NOT NULL COMMENT '媒体ID',
  `iCricleID` int(11) NOT NULL COMMENT '行业圈子ID',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  KEY `iMediaID` USING BTREE (`iMediaID`,`iCricleID`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='自媒体关联行业圈子表';

/*Table structure for table `t_media_tag` */

-- DROP TABLE IF EXISTS `t_media_tag`;

CREATE TABLE `t_media_tag` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iMediaID` int(11) NOT NULL COMMENT '媒体ID',
  `iTagID` int(11) NOT NULL COMMENT '标签ID',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`),
  KEY `iMediaID` USING BTREE (`iMediaID`,`iTagID`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8 COMMENT='自媒体标签关联表';

/*Table structure for table `t_menu` */

-- DROP TABLE IF EXISTS `t_menu`;

CREATE TABLE `t_menu` (
  `iMenuID` int(11) NOT NULL auto_increment COMMENT '菜单ID',
  `sMenuName` varchar(20) NOT NULL default '' COMMENT '菜单名',
  `iParentID` int(11) NOT NULL default '0' COMMENT '父菜单',
  `sUrl` varchar(100) NOT NULL default '' COMMENT 'URL地址',
  `sIcon` varchar(50) NOT NULL default '' COMMENT 'Icon图标',
  `iOrder` int(11) NOT NULL default '0' COMMENT '顺序',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=正常,1=删除)',
  `iCreateTime` int(10) NOT NULL default '0' COMMENT '添加时间',
  `iUpdateTime` int(10) NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`iMenuID`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='菜单表';

/*Table structure for table `t_meta` */

-- DROP TABLE IF EXISTS `t_meta`;

CREATE TABLE `t_meta` (
  `sPage` varchar(50) NOT NULL COMMENT 'Page',
  `sTitle` varchar(200) NOT NULL default '{Name}' COMMENT '标题',
  `sDescription` varchar(1000) NOT NULL default '{Desc}' COMMENT '描述',
  `sKeyword` varchar(500) NOT NULL default '{Tags}' COMMENT '关键字',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`sPage`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `t_permission` */

-- DROP TABLE IF EXISTS `t_permission`;

CREATE TABLE `t_permission` (
  `iPermissionID` int(11) NOT NULL auto_increment COMMENT '自动编号',
  `iMenuID` int(11) NOT NULL COMMENT '菜单ID',
  `sPermissionName` varchar(50) NOT NULL COMMENT '权限名',
  `sPath` varchar(100) NOT NULL COMMENT '路由地址',
  `iStatus` tinyint(1) NOT NULL default '0' COMMENT '当前状态',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '添加日期',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '最后更新时间',
  PRIMARY KEY  (`iPermissionID`),
  UNIQUE KEY `sRoute` (`sPath`)
) ENGINE=MyISAM AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='权限表';

/*Table structure for table `t_price` */

-- DROP TABLE IF EXISTS `t_price`;

CREATE TABLE `t_price` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `sTitle` varchar(20) NOT NULL COMMENT '价格期间',
  `iMinPrice` int(11) NOT NULL COMMENT '最小价格',
  `iMaxPrice` int(11) NOT NULL COMMENT '最大价格',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='价格段配制表';

/*Table structure for table `t_role` */

-- DROP TABLE IF EXISTS `t_role`;

CREATE TABLE `t_role` (
  `iRoleID` int(11) NOT NULL auto_increment COMMENT '角色ID',
  `sRoleName` varchar(50) NOT NULL COMMENT '角色名',
  `sDesc` varchar(255) NOT NULL default '' COMMENT '角色描述',
  `sPermission` varchar(10000) NOT NULL default '' COMMENT '角色权限',
  `sModule` varchar(10000) NOT NULL default '' COMMENT '模块权限',
  `iStatus` tinyint(1) NOT NULL default '0' COMMENT '当前状态(0:无效,1:有效)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建日期',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '最后修改日期',
  PRIMARY KEY  (`iRoleID`),
  KEY `iStatus` (`iStatus`)
) ENGINE=MyISAM AUTO_INCREMENT=118 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='角色表';

/*Table structure for table `t_user` */

-- DROP TABLE IF EXISTS `t_user`;

CREATE TABLE `t_user` (
  `iUserID` int(11) NOT NULL auto_increment COMMENT '帐号ID',
  `iType` tinyint(4) NOT NULL COMMENT '帐号类型(1=自媒体，2=广告主)',
  `sEmail` varchar(200) NOT NULL COMMENT '登录帐号(邮箱)',
  `sMobile` varchar(11) NOT NULL COMMENT '手机号',
  `sRealName` varchar(50) NOT NULL COMMENT '联系人名称',
  `sPassword` varchar(32) NOT NULL COMMENT '登录密码',
  `sPayPass` varchar(32) NOT NULL COMMENT '支付密码',
  `sCoName` varchar(100) NOT NULL COMMENT '公司名称',
  `sCoIndustry` varchar(50) NOT NULL COMMENT '所属行业(多个用逗号隔开)',
  `sCoAddress` varchar(200) NOT NULL COMMENT '公司地址',
  `sCoWebsite` varchar(200) NOT NULL COMMENT '公司网站',
  `sCoDesc` varchar(500) NOT NULL COMMENT '公司介绍',
  `sWeixin` varchar(50) NOT NULL COMMENT '微信',
  `sQQ` varchar(20) NOT NULL COMMENT 'QQ',
  `iMoney` decimal(10,2) NOT NULL COMMENT '余额',
  `iFirst` tinyint(4) NOT NULL COMMENT '是否首次',
  `iStatus` tinyint(4) NOT NULL default '2' COMMENT '状态(0=已删除，1=已激活，2=未激活)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iUserID`),
  UNIQUE KEY `sEmail` (`iType`,`sEmail`),
  UNIQUE KEY `sMobile` (`iType`,`sMobile`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='前台用户表';

/*Table structure for table `t_verify_code` */

-- DROP TABLE IF EXISTS `t_verify_code`;

CREATE TABLE `t_verify_code` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `iUserID` int(11) NOT NULL COMMENT '帐号ID',
  `iType` tinyint(4) NOT NULL COMMENT '类型(0=激活码,1=忘记密码，2=注册帐号...)',
  `sCode` varchar(50) NOT NULL COMMENT '验证码',
  `iStatus` tinyint(4) NOT NULL COMMENT '状态(0=已删除,1=正常)',
  `iCreateTime` int(11) NOT NULL COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL COMMENT '最后更新时间',
  PRIMARY KEY  (`iAutoID`),
  KEY `sVerifyCode` (`iUserID`,`iType`,`sCode`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 COMMENT='用户验证信息表';

/*Table structure for table `t_website` */

-- DROP TABLE IF EXISTS `t_website`;

CREATE TABLE `t_website` (
  `iWebSiteID` int(11) unsigned NOT NULL auto_increment,
  `iType` int(11) unsigned NOT NULL default '0' COMMENT '分类',
  `iParentID` int(11) unsigned NOT NULL default '0' COMMENT '上级分类ID',
  `sPage` varchar(50) NOT NULL,
  `sTitle` varchar(200) NOT NULL COMMENT '标题',
  `skeywords` text NOT NULL COMMENT '关键字',
  `sdescription` text NOT NULL COMMENT '介绍',
  `sContent` longtext NOT NULL COMMENT '内容',
  `iStatus` tinyint(4) unsigned NOT NULL default '1' COMMENT '状态(0=正常,1=删除)',
  `iCreateTime` int(11) unsigned NOT NULL,
  `iUpdateTime` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`iWebSiteID`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 COMMENT='网站内容';

/*Table structure for table `t_workmedia` */

-- DROP TABLE IF EXISTS `t_workmedia`;

CREATE TABLE `t_workmedia` (
  `iAutoID` int(11) NOT NULL auto_increment COMMENT '流水号',
  `wtype` int(11) NOT NULL COMMENT '类型',
  `title` varchar(250) NOT NULL COMMENT '名称',
  `imgurl` varchar(250) NOT NULL COMMENT '图片地址',
  `link` varchar(250) NOT NULL COMMENT '链接',
  `rank` int(11) NOT NULL COMMENT '顺序',
  `subscribe` int(11) NOT NULL COMMENT '订阅',
  `readnum` int(11) NOT NULL COMMENT '阅读数',
  `introduce` varchar(20) NOT NULL COMMENT '介绍',
  `iStatus` tinyint(4) NOT NULL default '1' COMMENT '状态(0=无效,1=有效)',
  `iCreateTime` int(11) NOT NULL default '0' COMMENT '创建时间',
  `iUpdateTime` int(11) NOT NULL default '0' COMMENT '更新时间',
  PRIMARY KEY  (`iAutoID`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='合作媒体';

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
