<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-10
 */

namespace Common\Event;

use Common\Controller\BaseController;
use Common\Logic\UserLogic;
use Common\Logic\LogLogic;
use Common\Util\Encrypt;

/**
 * 用户事件
 * Class UserEvent
 * @package Common\Event
 */
class UserEvent extends BaseController
{
    public function initDatabase($isAdmin=false)
    {
        if ($isAdmin)
        {
            $sql = "show databases like 'vlinkoptics'";
            $res = M()->query($sql);

            if (count($res) == 0)
            {
                //create database vlinkoptics
                M()->query("create database if not exists vlinkoptics character set utf8;");

                //create table `vlinkoptics`.`worker`
                M()->query("create table if not exists `vlinkoptics`.`worker`(
                            `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                            `worker_number` INTEGER NOT NULL DEFAULT '0' COMMENT '工牌编号',
                            `name` VARCHAR(256) NOT NULL COMMENT '人名',
                            `sex` INTEGER NOT NULL DEFAULT '0' COMMENT '性别：0是男 其他是女',
                            `phone` VARCHAR(11) NOT NULL COMMENT '手机号',
                            `role` INTEGER NOT NULL DEFAULT '0' COMMENT '0：管理员，1：组长，2：员工',
                            `password` CHAR(32) NOT NULL COMMENT '密码',
							`group_id` INTEGER NOT NULL DEFAULT '0' COMMENT '员工及组长组别',
                            `last_login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
							`comment` VARCHAR(256) NOT NULL COMMENT 'comment',
							`skill` INTEGER NOT NULL DEFAULT '0' COMMENT '技能分',
                            `deleted` INTEGER NOT NULL DEFAULT '0' COMMENT '1：已删除',
                            PRIMARY KEY (`id`,`worker_number`)
                        )ENGINE=InnoDB;");

                M()->query("INSERT INTO `vlinkoptics`.`worker` (`worker_number`, `name`, `role`, `password`) VALUES (0,'admin',0,'".md5("123456")."');");

                //create table `vlinkoptics`.`product`
                M()->query("create table if not exists `vlinkoptics`.`product`(
                            `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                            `name` VARCHAR(256) NOT NULL COMMENT '产品名称',
							`comment` VARCHAR(256) NOT NULL COMMENT 'comment',
                            `deleted` INTEGER NOT NULL DEFAULT '0' COMMENT '1：已删除',
                            PRIMARY KEY (`id`)
                        )ENGINE=InnoDB;");

                //create table `vlinkoptics`.`station`
                M()->query("create table if not exists `vlinkoptics`.`station`(
                            `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                            `name` VARCHAR(256) NOT NULL COMMENT '工位名称',
                            `product_id` INTEGER NOT NULL DEFAULT '0' COMMENT '产品编号',
                            `comment` VARCHAR(256) NOT NULL COMMENT 'comment',
                            `deleted` INTEGER NOT NULL DEFAULT '0' COMMENT '1：已删除',
                            PRIMARY KEY (`id`)
                        )ENGINE=InnoDB;");

                //create table `vlinkoptics`.`group`
                M()->query("create table if not exists `vlinkoptics`.`group`(
                            `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                            `name` VARCHAR(256) NOT NULL COMMENT '分组名称',
							`comment` VARCHAR(256) NOT NULL COMMENT 'comment',
                            `deleted` INTEGER NOT NULL DEFAULT '0' COMMENT '1：已删除',
                            PRIMARY KEY (`id`)
                        )ENGINE=InnoDB;");

                //create table `vlinkoptics`.`grading_standard`
                M()->query("create table if not exists `vlinkoptics`.`grading_standard`(
                            `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                            `product_id` INTEGER NOT NULL DEFAULT '0' COMMENT '产品编号',
                            `station_id` INTEGER NOT NULL DEFAULT '0' COMMENT '工位编号',
                            `group_id` INTEGER NOT NULL DEFAULT '0' COMMENT '组别索引',
                            `yield_rate` INTEGER NOT NULL DEFAULT '0' COMMENT '标准产出率(个/小时)',
                            `bad_rate` INTEGER NOT NULL DEFAULT '0' COMMENT '不良率 百分比',
							`comment` VARCHAR(256) NOT NULL COMMENT 'comment',
                            UNIQUE KEY `uniq_product_station` (`product_id`,`station_id`,`group_id`),
                            PRIMARY KEY (`id`)
                        )ENGINE=InnoDB;");

                M()->query("create table if not exists `vlinkoptics`.`sysop_log`(
							`id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
							`op_id` INT(11) NOT NULL COMMENT '操作者id',
							`op_name` varchar(256) NOT NULL COMMENT '操作者名称',
							`user_ip` varchar(256) NOT NULL COMMENT '操作者ip',
							`log_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录日期', 
							`content` VARCHAR(512) NOT NULL COMMENT '操作内容',
                            PRIMARY KEY (`id`)
                        )ENGINE=InnoDB;");
            }
        }

        $sql = "show tables from vlinkoptics like 'yield-".date("Y-m")."'";
        $res = M()->query($sql);

        if (count($res) == 0)
        {
            M()->query("create table if not exists `vlinkoptics`.`yield-".date("Y-m")."`(
                `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                `worker_id` INTEGER NOT NULL DEFAULT '0' COMMENT '员工索引',
                `product_id` INTEGER NOT NULL DEFAULT '0' COMMENT '产品索引',
                `group_id` INTEGER NOT NULL DEFAULT '0' COMMENT '组别索引',
                `station_id` INTEGER NOT NULL DEFAULT '0' COMMENT '工位索引',
                `worker` VARCHAR(256) NOT NULL COMMENT '员工',
                `worker_number` INTEGER NOT NULL DEFAULT '0' COMMENT '工牌编号',
                `product` VARCHAR(256) NOT NULL COMMENT '产品',
                `group` VARCHAR(256) NOT NULL COMMENT '组别',
                `station` VARCHAR(256) NOT NULL COMMENT '工位',
                `yield_rate_standard` INTEGER NOT NULL DEFAULT '0' COMMENT '标准产出率(个/小时)',
                `yield` INTEGER NOT NULL DEFAULT '0' COMMENT '产出',
                `bad` INTEGER NOT NULL DEFAULT '0' COMMENT '不良',
                `tasktime` FLOAT NOT NULL DEFAULT '0' COMMENT '工时',
                `6s` INTEGER NOT NULL DEFAULT '0' COMMENT '6s',
                `mark` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '备注',
                `score` FLOAT NOT NULL DEFAULT '0' COMMENT '评分',
                `day` INTEGER NOT NULL DEFAULT '0' COMMENT '日', 
                `skill` INTEGER NOT NULL DEFAULT '0' COMMENT '技能分',
                `time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录日期', 
                `worker_day` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '',
                PRIMARY KEY (`id`)
            )ENGINE=InnoDB;");

            $sql = "show tables from vlinkoptics like 'yield-".date("Y-m")."'";
            $res = M()->query($sql);
        }
		
		$sql = "show tables from vlinkoptics like 'attendance-".date("Y-m")."'";
        $res = M()->query($sql);

        if (count($res) == 0)
        {
            M()->query("create table if not exists `vlinkoptics`.`attendance-".date("Y-m")."`(
                `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键id',
                `worker_number` INTEGER NOT NULL DEFAULT '0' COMMENT '工牌编号',
                `worker` VARCHAR(256) NOT NULL COMMENT '员工',
                `group` VARCHAR(256) NOT NULL COMMENT '组别',
				`am_time` FLOAT NOT NULL DEFAULT '0' COMMENT '上午出勤',
				`pm_time` FLOAT NOT NULL DEFAULT '0' COMMENT '下午出勤',
				`extra_time` FLOAT NOT NULL DEFAULT '0' COMMENT '加班小时',
				`day` INTEGER NOT NULL DEFAULT '0' COMMENT '日', 
                `time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT '记录日期', 
                `comment` VARCHAR(256) NOT NULL DEFAULT '' COMMENT '',
                PRIMARY KEY (`id`)
            )ENGINE=InnoDB;");
        }
/*
        $sql = "INSERT INTO `vlinkoptics`.`worker` (`worker_number`, `name`, `role`, `phone`, `password`,`group_id`) VALUES ";
        for ($i=6; $i <200; $i++) {
            if ($i != 6)
                $sql .=',';
            $sql .= "(".$i.","."$i".","."1,12402884323,'e10adc3949ba59abbe56e057f20f883e',3)";
        }

        var_dump($sql);
*/

    }
    /**
     * 认证用户，传入where查询 $map['user表字段']
     * @param $map
     * @return string
     */
    public function auth($map)
    {
    	if ($map['user_login'] == '' || $map['user_pass'] == '')
    		$this->jsonResult(0, "用户名和密码不能为空");
    	
        $parm = array('name' => $map['user_login']);
        $user = M('ysf_user');
        
        $authInfo = $user->where($parm)->select();
        
        $crypt = new Encrypt();
        
        if ($map['user_pass'] == $crypt->decode($authInfo[0]['pwd'], $authInfo[0]['salt']))
        {
        	$_SESSION[C('USER_AUTH_KEY')] = $authInfo[0]['id'];
        	$_SESSION[C('USER_AUTH_INFO')] = $authInfo[0];
			
			//$crypt = new Encrypt();
			//$encode_pwd_new = $crypt->encode( "admin@ysf", "0hP^9H&Qw(Xf" );
			//echo $encode_pwd_new;exit;
			
        	if ($authInfo[0]['privilege_level'] == 1)
        	{
        		//超级管理员
        		$_SESSION['weight'] = 3;
        	}
        	else
        	{
        		if ($authInfo[0]['type'] == 1)
        		{
        			//普通系统管理员
        			$_SESSION['weight'] = 2;
        		}
        		else
        		{
        			//设备管理员
        			$_SESSION['weight'] = 1;
        		}
        	}
        	
        	$_SESSION['logtime'] = time();
        	$_SESSION['current_device_serial'] = $authInfo[0]['current_device_serial'];
        	$data = array(
        		'id' =>	$authInfo[0]['id'],
        		'last_login_time' => date('Y-m-d H:i:s')
        	);
        	
        	$user->save($data);
        	
        	$log = new LogLogic();
        	$log->addLog("登录成功");        	
        	//return $this->jsonResult(1, "登录成功", U("Admin/Index/index"));
        	return $this->jsonResult(1, "登录成功", U("Admin/ShootingTask/task"));
        }
        else
        {
        	return $this->jsonResult(0, "用户名或者密码错误");
        }
    }


    /**
     * 退出
     * @return string
     */
    public function logout()
    {
    	$log = new LogLogic();
    	$log->addLog("退出登录");
        session_unset();
        session_destroy();

        return $this->jsonResult(1, "退出成功", U("Admin/Login/index"));

    }
}
