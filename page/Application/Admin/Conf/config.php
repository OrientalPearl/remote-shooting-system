<?php
/**
 * Created by GreenStudio GCS Dev Team.
 * File: config.php
 * User: Timothy Zhang
 * Date: 14-1-15
 * Time: 下午11:23
 */


$menu_arr = array(

    'admin_big_menu_icon' => array(
		'ShootingManage' => 'fa-tasks',
		//'ShootingTask' => 'fa-tasks',
    	'System' => 'fa-gear',
        'DeviceManage' => 'fa-gavel',
		//'ShootingControl' => 'fa-adjust',
		//'PictureManage' => 'fa-picture-o',
        'Statistics' => 'fa-bar-chart-o',
    ),


    'admin_big_menu' => array(
		'ShootingManage' =>array('name'=>'拍摄管理','weight'=>1),
    	//'ShootingTask' =>array('name'=>'拍摄任务','weight'=>1),
        //'Index' => array('name'=>'首页','weight'=>1),
    	'System' => array('name'=>'系统设置','weight'=>1),
    	'DeviceManage' =>array('name'=>'设备管理','weight'=>1),
    	//'ShootingControl' =>array('name'=>'拍摄控制','weight'=>1),
    	//'PictureManage' =>array('name'=>'图片管理','weight'=>1),
    	//'Statistics' =>array('name'=>'统计','weight'=>1),
    ),

    'admin_sub_menu' => array(
        //'Index' => array(
        //    'Index/index' => array('name'=>'欢迎','weight'=>1),
        //),
    		
    	'System' => array(
    			'System/admin' => array('name'=>'管理员配置','weight'=>1),
    			'System/devupdate' => array('name'=>'设备自动升级','weight'=>3),
    			'System/monitor' => array('name'=>'系统监控','weight'=>3),
    			'System/sysoplog' => array('name'=>'系统操作日志','weight'=>1),
    			'System/updateindex' => array('name'=>'系统升级','weight'=>3),
				'System/email' => array('name'=>'邮箱配置','weight'=>3),
    	),
    		
    	'DeviceManage' => array(
    			'DeviceManage/devlist' => array('name'=>'设备列表','weight'=>1),
    			'DeviceManage/deveventlogSearch' => array('name'=>'设备事件日志','weight'=>1),
    	),
      	/*
    	'ShootingControl' => array(
    			'ShootingControl/parameterlist' => array('name'=>'参数控制','weight'=>1),
    	),
		'ShootingTask' => array(
    			'ShootingTask/task' => array('name'=>'任务列表','weight'=>1),
    	),
		'PictureManage' => array(
    			'PictureManage/picturelist' => array('name'=>'图片列表','weight'=>1),
		),
		*/
    	'ShootingManage' => array(
			'ShootingControl/parameterlist' => array('name'=>'拍摄控制','weight'=>1),
			'ShootingTask/task' => array('name'=>'拍摄任务','weight'=>1),
			'PictureManage/picturelist' => array('name'=>'图片管理','weight'=>1),
		),

    	'Statistics' => array(
    			'Statistics/devusercntstat' => array('name'=>'设备用户数','weight'=>1),
    	),
    	
    ),





);

$config_admin = array(

    'URL_MODEL' => 0,



    /*
     * RBAC认证配置信息
    */
    'USER_AUTH_ON' => true,
    'USER_AUTH_TYPE' => 2, // 默认认证类型 1 登录认证 2 实时认证
    'USER_AUTH_KEY' => 'authId', // 用户认证SESSION标记
    'ADMIN_AUTH_KEY' => 'ADMIN',
    'USER_AUTH_MODEL' => 'User', // 默认验证数据表模型
    'AUTH_PWD_ENCODER' => 'md5', // 用户认证密码加密方式encrypt
    'USER_AUTH_GATEWAY' => '?s=/Admin/Login/index', // 默认认证网关
    'NOT_AUTH_MODULE' => 'Public', // 默认无需认证模块
    'REQUIRE_AUTH_MODULE' => '', // 默认需要认证模块
    'NOT_AUTH_ACTION' => '', // 默认无需认证操作
    'REQUIRE_AUTH_ACTION' => '', // 默认需要认证操作
    'GUEST_AUTH_ON' => false, // 是否开启游客授权访问
    'GUEST_AUTH_ID' => 0, // 游客的用户ID
    'RBAC_ROLE_TABLE' => GreenCMS_DB_PREFIX . 'role',
    'RBAC_USER_TABLE' => GreenCMS_DB_PREFIX . 'role_users',
    'RBAC_ACCESS_TABLE' => GreenCMS_DB_PREFIX . 'access',
    'RBAC_NODE_TABLE' => GreenCMS_DB_PREFIX . 'node',

    //'DEFAULT_THEME' => get_opinion("DEFAULT_ADMIN_THEME", true, "AdminLTE"),

    'DEFAULT_THEME' => "AdminLTE",
    //     'DEFAULT_THEME' => "Metronic",
    

	'haffman_key_mask_len' => array (
		'1' => 1,# 超级管理员下面的管理员 16位
		'2' => 16,# 超级管理员下面的管理员 16位
		'3' => 28,# 二级超级管理员下面的 管理员28位
		'4' => 40,# 三级级超级管理员下面的 管理员40位
		'5' => 52,# 四级超级管理员下面的管理员52位
		'6' => 64 # 五级超级管理员下面的管理员64位
	),
);


return array_merge($config_admin, $menu_arr);