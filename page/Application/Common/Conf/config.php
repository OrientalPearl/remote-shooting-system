<?php
return array(
    //'配置项'=>'配置值'

    // 数据库配置
    'DB_TYPE'              => 'mysql',
    'DB_HOST'              => 'localhost',
    'DB_NAME'              => 'ysf',
    'DB_USER'              => 'root',
    'DB_PWD'               => '123456',
    'DB_PORT'              => '3306',
    'DB_PREFIX'            => 'wrt_',
	'DB_CHARSET'           => 'utf8',
 //   'DB_DSN' => 'pgsql:host=localhost;port=5432;dbname=green;',
    'AUTOLOAD_NAMESPACE'   => array('Addons' => './Addons/'), //扩展模块列表

    'SHOW_PAGE_TRACE'      => false,

    'SESSION_OPTIONS'      => array(), // session 配置数组 支持type name id path expire domain 等参数
    'SESSION_PREFIX'       => '', // session 前缀
    
    'USER_AUTH_KEY'        => 'uaid', 
	'USER_AUTH_INFO'       => 'uinfo',
	
	'USER_AUTH_TIMEOUT'    => '1800',

	'ENCRYPTION_KEY'       => 'net!@#$%n123',//加密密钥
	
	'WRT_TITLE'            => 'YSF',

    'AUTH_CODE'            => "ZTS", //安装完毕之后不要改变，否则所有密码都会出错
    'ADMIN'                => 'admin',//如果管理员不是admin 需要修改此项
    'TOKEN_ON'             => false, //TOKEN_ON

    'DATA_CACHE_TYPE'      => get_opinion('DATA_CACHE_TYPE', false, 'File'), // 数据缓存类型,支持:File||Memcache|Xcache
    'DATA_CACHE_SUBDIR'    => true, // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)

    'URL_CASE_INSENSITIVE' => true, //URL大小写不敏感
    'URL_MODEL'            => 0,

    'TMPL_PARSE_STRING'    => array(
        '__EXTEND__' => Extend_PATH,
        //'__PUBLIC__' => 'PUBLIC', // 强制修正__PUBLIC__
        //'__ROOT__' => '',// 强制修正__ROOT__
    ),

    'TAGLIB_BUILD_IN'      => 'Green,Cx',

    'LOAD_EXT_CONFIG'      => 'config_opinion,config_node,config_custom,config_oem', // 加载扩展配置文件 config_alias,config_db,config_system

    'DEFAULT_MODULE'       => 'Admin',

    'VAR_FILTERS'       => 'remove_xss',
    'DEFAULT_FILTER'       => 'htmlspecialchars',


    'MODULE_ALLOW_LIST'    => array('Home', 'Admin', 'Weixin', 'Install','Api','Zel','Oauth'), //配置你原来的分组列表
    'MODULE_DENY_LIST'     => array('Common'),

);
