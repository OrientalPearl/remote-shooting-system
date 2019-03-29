<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-13
 */

namespace Admin\Controller;

use Common\Util\GreenPage;
use Think\Storage;
use Think\Model;
use Admin\Event\HaffmanEvent;
use Admin\Event\DeviceResourceStat;

/**
 * Class StatisticsController
 * @package Admin\Controller
 */
class StatisticsController extends AdminBaseController
{	
	
	public function index()
	{
		$this->redirect('Admin/Statistics/usertraffic');
	}
	
	public function is_table_exists($table){
		$Model = new Model();
		if($table){
			$sql = "show tables like '$table'";
			$res = $Model->query($sql);
			return $res ? true : false;
		}
	}
	
	public function get_wherecondition2() {

		$where = HaffmanEvent::get_wherecondition();
		
		return $where;
	}
	
	public function get_device_showname_by_devicemac($device_mac)
	{
		if($device_mac){
			$Model = D("Device");
			$sql = "SELECT * 
                    FROM  `wrt_device` 
                    WHERE  `mac` =  '$device_mac'
                    LIMIT 0 , 30";

			$res = $Model->query($sql);
			
			if($res){
				return $res[0]['shop_name'];
			}else{
				return '-';
			}
		}
	}
	
	function format_time($time = NULL) {
		$hour = '00';
		$min = '00';
		$sec = '00';
		if ($time) {
			$time = floatval ( trim ( $time ) );
			$hour = sprintf ( '%02d', intval ( $time / 3600 ) );
			$min = sprintf ( '%02d', intval ( ($time % 3600) / 60 ) );
			$sec = sprintf ( '%02d', intval ( $time % 60 ) );
		}
		return $hour . ':' . $min . ':' . $sec;
	}
    
	function format_data($data){
		foreach ($data as $key => $value){
	
			if(isset($value['device_mac'])&&trim($value['device_mac'])!==''){
				# if($this->get_device_showname_by_devicemac(trim($value['device_mac']))=='-'){
				#	unset($data[$key]);
				#	continue;
				#}
				if($data[$key]['online'])
				{
				    $data[$key]['online'] = $this->format_time($data[$key]['online']);
				}
				$data[$key]['shop_name'] = $this->get_device_showname_by_devicemac(trim($value['device_mac']));
			}else{
				$data[$key]['shop_name'] = '';
			}
			
			$Model = new Model();
	
			if(array_key_exists('category', $value) ){
				if( isset($value['category'])&&trim($value['category'])!==''){
					$sql = "select category from wrt_category where category_id=".trim($value['category']);
					//$sql = "SELECT *
					//FROM  `wrt_category `
					//WHERE  `category_id` =  'trim($value['category'])'
					//LIMIT 0 , 30";
					//dump($sql);
					$res = $Model->query($sql);
					//dump($res);
					//die;
					if($res){
						$data[$key]['category'] = $res[0]['category'];
					}else{
						$data[$key]['category'] = '未知行业';
						//                    unset($data[$key]);
					}
					
				}else{
					unset($data[$key]);
				}
			}else{
				//                unset($data[$key]);
				#$data[$key]['category'] = '未知行业';
			}
		}
		return $data;
	}
	
    public function get_field_name($field='device_user'){
        $field_key = array(
			'device_user'=>'device_mac,SUM(total_cnt) as total_user_cnt,SUM(auth_cnt) as total_auth_cnt',
			'category_user'=>'category,SUM(total_cnt) as total_user_cnt,SUM(auth_cnt) as total_auth_cnt',
			'device_online_time'=>'device_mac,SUM(online_time) as total_online_time' 
			);

			return $field_key[$field];

    }


    public function get_device_online_time($field='device_online_time',$timetype='last7'){
    
    	$Model = new Model();
    	$timetype='last7';
    	 
    	$db_filed = $this->get_field_name($field);
    
    
    	$groupby= 'group  BY `device_mac`';
    	 
    
    	$orderField = "total_online_time";
    	$orderDirection = 'desc';
    	//$this->input->get_post('orderDirection') && $orderDirection = $this->input->get_post('orderDirection');
    
    	$orderby = "order by $orderField $orderDirection";
    
    	$where = ' where 1';
    	$endtime = date('Y-m-d 23:59:59',strtotime('-1 days'));
    	$endtime = strtotime($endtime);
    	//$endtime = 1421424000;
    	
    	$starttime = NULL;
    	switch ($timetype){
    		case "last7":
    			$starttime = $endtime-86400*7;
    			break;
    		case 'last14':
    			$starttime = $endtime-86400*14;
    			break;
    		case 'last30':
    			$starttime = $endtime-86400*30;
    			break;
    		default:
    			$starttime = $endtime-86400*7;
    			break;
    	}
    	//dump($starttime);
    	//dump($endtime);
    	//die;
    	$data = array('time'=>'','data'=>'');
    	$where .= " and time>$starttime and time <=$endtime";
    
    	//$utype = $this->get_user_type (); // 管理员类型：1、系统，2、设备
    	$utype = 1;
    	#if 0
    	$device_mac_sql = '';
    	if($utype==2){
    		$device_mac_sql = "SELECT mac FROM `wrt_device` a JOIN `wrt_user` b ON a.user_id = b.id WHERE b.name = '".$this->get_current_uname()."'";
    	}
    	$device_mac_sql && $where .= " and `device_mac` in($device_mac_sql) ";
        #endif
    
    	$sql = "SELECT  $db_filed  FROM `device_online_time_stat` $where $groupby  $orderby";
    	//        $res['data'] = $this->get_all($sql);
    	//dump($sql);
    	$list = $Model->query($sql);
    	//dump($list);
    	$res['data'] = $this->format_data($list);
    	$res['totalCount'] = count($res['data']);
    	//dump($res['totalCount']);
    	//die;
    	return $res;
    }
    
    /**
     * 设备用户数、认证用户数
     */
    public function get_device_user($field='device_user',$timetype='last7'){
    
    	$db_filed = $this->get_field_name($field);
    	$Model = new Model();
    
    	$groupby= 'group  BY `device_mac`';
    	if($field == 'device_user'){
    		$groupby = 'group  BY `device_mac`';
    	}
    	if($field == 'category_user'){
    		$groupby = 'group  BY `category`';
    	}
    
    	$orderField = "total_user_cnt";
    	$orderDirection = 'desc';
    
    	//$this->input->get_post('orderField') && $orderField = $this->input->get_post('orderField');
    
    	switch ($orderField){
    		case 'auth_cnt_order_by':
    			$orderField = 'total_auth_cnt';
    			break;
    		case 'user_cnt_order_by':
    			$orderField = 'total_user_cnt';
    			break;
    		default:
    			$orderField = 'total_auth_cnt';
    			break;
    	}
    	//$this->input->get_post('orderDirection') && $orderDirection = $this->input->get_post('orderDirection');
    
    	$orderby = "order by $orderField $orderDirection";
    
    
    	$where = ' where 1';
    	$endtime = date('Y-m-d 23:59:59',strtotime('-1 days'));
    	$endtime = strtotime($endtime);
    	//$endtime = 1421424000;
    	$starttime = NULL;
    	switch ($timetype){
    		case "last7":
    			$starttime = $endtime-86400*7;
    			break;
    		case 'last14':
    			$starttime = $endtime-86400*14;
    			break;
    		case 'last30':
    			$starttime = $endtime-86400*30;
    			break;
    		default:
    			$starttime = $endtime-86400*7;
    			break;
    	}
    	$data = array('time'=>'','data'=>'');
    	$where .= " and time>$starttime and time <=$endtime";
    
    	/*
    	$utype = $this->get_user_type (); // 管理员类型：1、系统，2、设备
    	$device_mac_sql = '';
    	if($utype==2){
    		$device_mac_sql = "SELECT mac FROM `wrt_device` a JOIN `wrt_user` b ON a.user_id = b.id WHERE b.name = '".$this->get_current_uname()."'";
    	}
    	$device_mac_sql && $where .= " and `device_mac` in($device_mac_sql) ";
       */
    
    	$sql = "SELECT  $db_filed   FROM `sy2rt_user_cnt_stat` $where $groupby  $orderby";
    	//dump($sql);
    	$list = $Model->query($sql);
    	//dump($list);
    	//die;
    	$res['data'] = $this->format_data($list);
    	$res['totalCount'] = count($res['data']);
    	//dump($res['data']);
    	//die;
    	return $res;
    }
    
    public function onlinetimestat() {
    
        $Model = new Model();
        $dateStart = date('Y-m-d');
        $dateEnd = date('Y-m-d');
        
        I('post.start_time') && $dateStart = I('post.start_time');
        I('post.end_time') && $dateEnd = I('post.end_time');

    	$device_mac_page = I('post.device_mac');
    	$dateStart2 = $_GET['start_time'];
    	$dateEnd2 = $_GET['end_time'];
    	$device_mac_page2 = $_GET['device_mac'];
    	
    	if($dateStart2)
    	{
    		$dateStart = $dateStart2;
    	}
    	 
    	if($dateEnd2)
    	{
    		$dateEnd = $dateEnd2;
    	}
    	
    	if($device_mac_page2)
    	{
    		$device_mac_page = $device_mac_page2;
    	}
    	
    	if(!$dateStart)
    	{
    	    $dateStart = date('Y-m-d 23:59:59',strtotime('-1 days'));
    	    $dateStart = $dateStart;
    	    $dateEnd = $dateStart;
    	}
    	//dump($dateStart);
    	//dump($dateEnd);
    	$dateStart = strtotime($dateStart);
    	$dateEnd = strtotime($dateEnd);
        //die;
    	if($dateStart==$dateEnd){
    		$table = "wrt_user".'_'.date('Y_m_d',$dateStart);
    		//dump($table);
    		//die;
    		if($this->is_table_exists($table)){
    			$table_arr = array($table);
    		}else{
    			$table_arr = array();
    		}
    	}elseif ($dateStart<$dateEnd){
    		$table_arr = array();
    		for($i = $dateStart;$i<=$dateEnd;$i=$i+86400){
    			
    			$table = "wrt_user".'_'.date('Y_m_d',$i);
    			//dump($table);
    			if($this->is_table_exists($table)){
    				$table_arr[] = $table;
    			}
    		}
    	}
    	

       $utype == 1;
        //$utype = $this->get_user_type (); // 管理员类型：1、系统，2、设备

        
        $where = $this->get_wherecondition2 ();
        trim ( $device_mac_page) && $device_mac = trim ( $device_mac_page);

        if (isset ( $device_mac ) && $device_mac) {
            $where .= " and `device_mac` = '$device_mac'";
        }
    	
    	$sql = '';
    	$sql_cnt = '';
    	if($table_arr){
    		$fields = "offline_cause,mac,online_time,if(offline_time=0,unix_timestamp(),offline_time) as offline_time,device_mac,if(offline_time=0,'在线',from_unixtime(offline_time)) as offline_time_show";
    		foreach ($table_arr as $key => $value){
    			$sql == '' ? $sql .= " SELECT $fields FROM `$value` $where " : $sql .= " union all SELECT $fields  FROM `$value` $where ";
    		}
    
    		$sql && $sql = "SELECT from_unixtime(online_time) as online_time,offline_time_show,device_mac,mac,SUM(offline_time - online_time) AS online from (" . $sql . ") as tmp GROUP BY device_mac,mac ORDER BY online DESC ";
    		$sql && $sql_cnt = "select count(1) as cnt from ( SELECT * from (" . $sql . ") as tmp GROUP BY device_mac,mac) as tmp1";
    		$sql && $sql .= " $limit";
    	}

    	
    	if($sql){
    		//            $data ['data'] = $this->get_all ( $sql );
    		//dump($sql);
    		$list = $Model->query($sql);
    		//dump($list);
    		//die;
    		$data['data'] = $this->format_data($list);
    		$res_cnt = $Model->query( $sql_cnt );
    		$data ['totalCount'] = $res_cnt[0] ['cnt'];
    		//dump($data ['totalCount']);
    		//dump($data['data']);
    		//die;
    	}else{
    		$data ['data'] = array();
    		$data ['totalCount'] = 0;
    	}
    	$count = $data ['totalCount'];
    	//die;
    	if ($count != 0) {
    		$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
    		$pager_bar = $Page->show();
    		$limit = $Page->firstRow . ',' . $Page->listRows;
    		$sql && $sql .= " limit $limit ";
    		//dump($sql);
    		//die;
    		$list = $Model->query($sql);
    		$data['data'] = $this->format_data($list);
    	}
    	
    	$parameter = 'start_time='.urlencode($_GET['start_time']);
    	$parameter = 'end_time='.urlencode($_GET['end_time']);
    	$parameter = 'device_mac='.urlencode($_GET['device_mac']);
    	$this->assign('listname', '用户在线时长');
    	$this->assign('pager_bar', $pager_bar);
    	$this->assign('list', $data['data']);
    	$this->display('onlinetimestat');
    }
	
    /**
                'Statistics/usertraffic' => '用户流量',
    			'Statistics/onlinetimestat' => '用户在线时长', 
    			'Statistics/devusercntstat' => '设备用户数',
    			'Statistics/categoryusercntstat' => '行业用户数',
    			'Statistics/devonlinetimestat' => '设备使用时长',
     */
    
    public function usertraffic()
    {
    	$page = I('get.page', C('PAGER'));
    	$Model = new Model();
    	
    	$dateStart = date('Y-m-d');
    	$dateEnd = date('Y-m-d');

    	I('post.start_time') && $dateStart = I('post.start_time');
    	I('post.end_time') && $dateEnd = I('post.end_time');
    	
    	//dump($dateStart);
    	//dump($dateEnd);
    	//die;
    	
    	$dateStart2 = $_GET['start_time'];
    	$dateEnd2 = $_GET['end_time'];
    	 
    	if($dateStart2)
    	{
    		$dateStart = $dateStart2;
    	}
    	
    	if($dateEnd2)
    	{
    		$dateEnd = $dateEnd2;
    	}
    	 
    	//dump($dateStart);
    	//dump($dateEnd);
    	if(!$dateStart)
    	{
    	    $dateStart = date('Y-m-d 23:59:59',strtotime('-1 days'));
    	    $dateStart = $dateStart;
    	    $dateEnd = $dateStart;
    	}
    	//dump($dateStart);
    	//dump($dateEnd);
    	$dateStart = strtotime($dateStart);
    	$dateEnd = strtotime($dateEnd);
    	//$endtime = 1421424000;
        //die;
    	if($dateStart==$dateEnd){
    		$table = "wrt_user".'_'.date('Y_m_d',$dateStart);
    		//dump($table);
    		//die;
    		if($this->is_table_exists($table)){
    			$table_arr = array($table);
    		}else{
    			$table_arr = array();
    		}
    	}elseif ($dateStart<$dateEnd){
    		$table_arr = array();
    		for($i = $dateStart;$i<=$dateEnd;$i=$i+86400){
    			
    			$table = "wrt_user".'_'.date('Y_m_d',$i);
    			//dump($table);
    			if($this->is_table_exists($table)){
    				$table_arr[] = $table;
    			}
    		}
    	}
    	$where = $this->get_wherecondition2 ();
    	//dump($where);
    	//die;
    	$sql = '';
    	$sql_cnt = '';
    	if($table_arr){
    		foreach ($table_arr as $key => $value){
    			$sql == '' ? $sql .= " SELECT * FROM `$value` $where " : $sql .= " union all SELECT * FROM `$value` $where ";
    		}
    	
    		$sql && $sql = "SELECT mac,device_mac,SUM(dn_bytes+up_bytes) AS flow from (" . $sql . ") as tmp GROUP BY device_mac,mac ORDER BY flow DESC ";
    		$sql && $sql_cnt = "select count(1) as cnt from ( SELECT * from (" . $sql . ") as tmp GROUP BY device_mac,mac) as tmp1";
    	
    		$sql && $sql .= " $limit";
    	}
    	//dump($sql);
    	//die;
    	if($sql){
    		//            $data ['data'] = $this->get_all ( $sql );
    		//dump($sql);
    		$list = $Model->query($sql);
    		//dump($list);
    		//die;
    		$data['data'] = $this->format_data($list);
    		$res_cnt = $Model->query( $sql_cnt );
    		$data ['totalCount'] = $res_cnt[0] ['cnt'];
    		//dump($data ['totalCount']);
    		//dump($data['data']);
    		//die;
    	}else{
    		$data ['data'] = array();
    		$data ['totalCount'] = 0;
    	}
    	$count = $data ['totalCount'];
    	//die;
    	//dump($count);
    	if ($count != 0) {
    		$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
    		$pager_bar = $Page->show();
    		$limit = $Page->firstRow . ',' . $Page->listRows;
    		
    		$sql && $sql .= " limit $limit ";
    		//dump($sql);
    		//die;
    		$list = $Model->query($sql);
    		$data['data'] = $this->format_data($list);
    	}
    	
    	$parameter = 'start_time='.urlencode($_GET['start_time']);
    	$parameter = 'end_time='.urlencode($_GET['end_time']);
    	
    	$this->assign('listname', '用户流量');
    	$this->assign('pager_bar', $pager_bar);
    	$this->assign('list', $data['data']);
    	$this->display('usertraffic');
    	
    }
    
	
	public function devusercntstat()
    {    	
        $page = I('get.page', C('PAGER'));

        $type = 'device_user';//设备资源类型
	 	
	 	$timetype = I('post.timetype');
	 		
	 	$type2 = $_GET['timetype'];
	 	 
	 	if($type2)
	 	{
	 		$timetype = $type2;
	 	}
	 	 
	 	//$data = array();
	 	//$res = $this->get_device_user($type,$timetype);
	 	$db_filed = $this->get_field_name($type);
	 	$Model = new Model();
	 	
	 	$groupby= 'group  BY `device_mac`';
	 	if($field == 'device_user'){
	 		$groupby = 'group  BY `device_mac`';
	 	}
	 	if($field == 'category_user'){
	 		$groupby = 'group  BY `category`';
	 	}
	 	
	 	$orderField = "total_user_cnt";
	 	$orderDirection = 'desc';
	 	
	 	//$this->input->get_post('orderField') && $orderField = $this->input->get_post('orderField');
	 	
	 	switch ($orderField){
	 		case 'auth_cnt_order_by':
	 			$orderField = 'total_auth_cnt';
	 			break;
	 		case 'user_cnt_order_by':
	 			$orderField = 'total_user_cnt';
	 			break;
	 		default:
	 			$orderField = 'total_auth_cnt';
	 			break;
	 	}
	 	//$this->input->get_post('orderDirection') && $orderDirection = $this->input->get_post('orderDirection');
	 	
	 	$orderby = "order by $orderField $orderDirection";
	 	
	 	
	 	//$where = ' where 1';
	 	$where = $this->get_wherecondition2();
	 	$endtime = date('Y-m-d 23:59:59',strtotime('-1 days'));
	 	$endtime = strtotime($endtime);
	 	//$endtime = 1421424000;
	 	$starttime = NULL;
	 	switch ($timetype){
	 		case "last7":
	 			$starttime = $endtime-86400*7;
	 			break;
	 		case 'last14':
	 			$starttime = $endtime-86400*14;
	 			break;
	 		case 'last30':
	 			$starttime = $endtime-86400*30;
	 			break;
	 		default:
	 			$starttime = $endtime-86400*7;
	 			break;
	 	}
	 	$data = array('time'=>'','data'=>'');
	 	$where .= " and time>$starttime and time <=$endtime";
	 	

	

	 	$sql = "SELECT  $db_filed   FROM `sy2rt_user_cnt_stat` $where $groupby  $orderby";
	 	//dump($sql);
	 	//die;
	 	$list = $Model->query($sql);
	 	//dump($list);
	 	//die;
	 	$res['data'] = $this->format_data($list);
	 	$res['totalCount'] = count($res['data']);
	 	//$data['data'] = $res['data'];
	 	$count = $res['totalCount'];
	 	//dump($count);
	 	
	 	//var_dump($data['data']);
	 	//die;
	 	if ($count != 0) {
	 		$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
	 		$pager_bar = $Page->show();
	 		$limit = $Page->firstRow . ',' . $Page->listRows;
	 		//dump($limit);
	 		
	 		$sql = "SELECT  $db_filed   FROM `sy2rt_user_cnt_stat` $where $groupby $orderby  limit $limit ";
	 		//dump($sql);
	 		$list = $Model->query($sql);
	 		
	 		//dump($list);
	 		//die;
	 		$res['data'] = $this->format_data($list);
	 		$res['totalCount'] = count($res['data']);
	 		$data['data'] = $res['data'];
	 		//dump($data['data']);
	 		//die;
	 	}
	 	 
	 	$parameter = 'timetype='.urlencode($_GET['timetype']);
	 	$this->assign('listname', '设备用户数');
	 	$this->assign('pager_bar', $pager_bar);
	 	$this->assign('list', $data['data']);
	 	$this->display('devusercntstat');
    }
	
	public function categoryusercntstat()
    {    	
        $page = I('get.page', C('PAGER'));

    	$type = 'category_user';//设备资源类型
    	$timetype = I('post.timetype');
    	$timetype2 = $_GET['timetype'];
    	
    	if($timetype2)
    	{
    		$timetype = $timetype2;
    	}
    	
    	//dump($timetype);
    	 	//$data = array();
	 	//$res = $this->get_device_user($type,$timetype);
	 	$db_filed = $this->get_field_name($type);
	 	$Model = new Model();
	    $groupby = 'group  BY `category`';

	 	
	 	$orderField = "total_user_cnt";
	 	$orderDirection = 'desc';
	 	
	 	$orderby = "order by $orderField $orderDirection";
	 	
	 	
	 	//$where = ' where 1';
	 	$where = $this->get_wherecondition2();
	 	$endtime = date('Y-m-d 23:59:59',strtotime('-1 days'));
	 	$endtime = strtotime($endtime);
	 	//$endtime = 1421424000;
	 	$starttime = NULL;
	 	switch ($timetype){
	 		case "last7":
	 			$starttime = $endtime-86400*7;
	 			break;
	 		case 'last14':
	 			$starttime = $endtime-86400*14;
	 			break;
	 		case 'last30':
	 			$starttime = $endtime-86400*30;
	 			break;
	 		default:
	 			$starttime = $endtime-86400*7;
	 			break;
	 	}
	 	$data = array('time'=>'','data'=>'');
	 	$where .= " and time>$starttime and time <=$endtime";
	 	
	 	//$utype = HaffmanEvent::getUserType();
	 	//$uname = HaffmanEvent::getUserName();
	 	//$utype = 1;
	 	// $utype = $this->get_user_type (); // 管理员类型：1、系统，2、设备
	 	//$device_mac_sql = '';
	 	//if($utype==2){
	 	//$device_mac_sql = "SELECT mac FROM `wrt_device` a JOIN `wrt_user` b ON a.user_id = b.id WHERE b.name = '".$uname."'";
	 	//}
	 	//$device_mac_sql && $where .= " and `device_mac` in($device_mac_sql) ";
	 	

	 	$sql = "SELECT  $db_filed   FROM `sy2rt_user_cnt_stat` $where $groupby  $orderby";
	 	//dump($sql);
	 	//die;
	 	$list = $Model->query($sql);
	 	//dump($list);
	 	//die;
	 	$res['data'] = $this->format_data($list);
	 	$res['totalCount'] = count($res['data']);
	 	//$data['data'] = $res['data'];
	 	$count = $res['totalCount'];
	 	//dump($count);
	 	
	 	//var_dump($data['data']);
	 	//die;
	 	if ($count != 0) {
	 		$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
	 		$pager_bar = $Page->show();
	 		$limit = $Page->firstRow . ',' . $Page->listRows;
	 		//dump($limit);
	 		
	 		$sql = "SELECT  $db_filed   FROM `sy2rt_user_cnt_stat` $where $groupby $orderby  limit $limit ";
	 		//dump($sql);
	 		$list = $Model->query($sql);
	 		
	 		//dump($list);
	 		//die;
	 		$res['data'] = $this->format_data($list);
	 		$res['totalCount'] = count($res['data']);
	 		$data['data'] = $res['data'];
	 		//dump($data['data']);
	 		//die;
	 	}
	 	
	 	$parameter = 'timetype='.urlencode($_GET['timetype']);
	 	$this->assign('listname', '行业用户数');
	 	$this->assign('pager_bar', $pager_bar);
	 	$this->assign('list', $data['data']);
	 	$this->display('categoryusercntstat');
    }
	
	public function devonlinetimestat()
    {    	
    	$page = I('get.page', C('PAGER'));



	 	$type = 'device_online_time';//设备资源类型
	 	
	 	$timetype = I('post.timetype');
	 	$type2 = $_GET['timetype'];
	 	 
	 	if($type2)
	 	{
	 		$timetype = $type2;
	 	}	
	 	
	 	//$data = array();
	 	//$res = $this->get_device_online_time($type,$timetype);
	 	$Model = new Model();
	 	//$timetype='last7';
	 	$field='device_online_time';
	 	$db_filed = $this->get_field_name($field);
	 	
	 	
	 	$groupby= 'group  BY `device_mac`';
	 	
	 	
	 	$orderField = "total_online_time";
	 	$orderDirection = 'desc';
	 	//$this->input->get_post('orderDirection') && $orderDirection = $this->input->get_post('orderDirection');
	 	
	 	$orderby = "order by $orderField $orderDirection";
	 	
	 	//$where = ' where 1';
	 	$where = $this->get_wherecondition2();
	 	$endtime = date('Y-m-d 23:59:59',strtotime('-1 days'));
	 	$endtime = strtotime($endtime);
	 	$endtime = 1421424000;
	 	 
	 	$starttime = NULL;
	 	switch ($timetype){
	 		case "last7":
	 			$starttime = $endtime-86400*7;
	 			break;
	 		case 'last14':
	 			$starttime = $endtime-86400*14;
	 			break;
	 		case 'last30':
	 			$starttime = $endtime-86400*30;
	 			break;
	 		default:
	 			$starttime = $endtime-86400*7;
	 			break;
	 	}
	 	//dump($starttime);
	 	//dump($endtime);
	 	//die;
	 	$data = array('time'=>'','data'=>'');
	 	$where .= " and time>$starttime and time <=$endtime";
	 	
	 	//$utype = HaffmanEvent::getUserType();
	 	//$uname = HaffmanEvent::getUserName();
	 	//$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
	 	//dump($utype);
	 	//dump($uname);
	 	//die;
	 	
	 	//$utype = $this->get_user_type (); // 管理员类型：1、系统，2、设备
	 	//$utype = 1;

	 	//$device_mac_sql = '';
	 	//if($utype==2){
	 	//	$device_mac_sql = "SELECT mac FROM `wrt_device` a JOIN `wrt_user` b ON a.user_id = b.id WHERE b.name = '".$uname."'";
	 	//}
	 	//$device_mac_sql && $where .= " and `device_mac` in($device_mac_sql) ";

	 	
	 	$sql = "SELECT  $db_filed  FROM `device_online_time_stat` $where $groupby  $orderby";
	 	//        $res['data'] = $this->get_all($sql);
	 	//dump($sql);
	 	$list = $Model->query($sql);
	 	//dump($list);
	 	$res['data'] = $this->format_data($list);
	 	$res['totalCount'] = count($res['data']);
	 	$data['data'] = $res['data'];
	 	$count = $res['totalCount'];
	 	
	 	//var_dump($data['data']);
	 	//die;
	 	if ($count != 0) {
	 		$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
	 		$pager_bar = $Page->show();
	 		$limit = $Page->firstRow . ',' . $Page->listRows;
	 		
	 		$sql && $sql .= " limit $limit ";
	 		//dump($sql);
	 		//die;
	 		$list = $Model->query($sql);
	 		$data['data'] = $this->format_data($list);
	 	}

	 	$parameter = 'timetype='.urlencode($_GET['timetype']);
	 	$this->assign('listname', '设备使用时长');
	 	$this->assign('pager_bar', $pager_bar);
	 	$this->assign('list', $data['data']);
	 	$this->display('devonlinetimestat');
    }
}