<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-25
 */

namespace Admin\Event;
use Think\Model;
use Admin\Event\HaffmanEvent;


/**
 * Class PostsEvent
 * @package Admin\Event
 */
class DeviceResourceStat
{
	private function getFieldName($field='mem') {
		$field_key = array(
			'mem'=>'mem_use_rate',
			'cpu'=>'cpu_use_rate',
			'traffic'=>'traffic,traffic_up,traffic_dn',
			'session'=>'session',
			'new_session_rate'=>'new_session_rate',
			'online_user_num'=>'online_user_num'
		);
		
		return $field_key[$field];		
	}
	
	private function getWhereCondition($device_mac='') {
		return HaffmanEvent::getDevUnderCtrQueryString($device_mac);
	}
	
	private function getDevUseRateQuery($device_mac='') {
		$where = ' where 1';
		
		$utype = HaffmanEvent::getUserType(); // 管理员类型：1、系统，2、设备
		$device_mac_sql = '';
		if($utype==2){
			$device_mac_sql = "SELECT mac FROM __DEVICE__ a JOIN __USER__ b ON a.user_id = b.id WHERE b.name = ".HaffmanEvent::getUserName();;
		}
		
		$device_mac_sql && $where .= " and `device_mac` in($device_mac_sql) ";
		
		if($utype==1&&!$device_mac){
			die('无设备mac参数');
		}
		
		return $where;
	}
	
	private function getTablesByDateRange($dateStart='', $dateEnd='') {
		if ($dateStart == '')
			$dateStart = date('Y-m-d');
		if ($dateEnd == '')
			$dateEnd = date('Y-m-d');
	
		$dateStart = strtotime($dateStart);
		$dateEnd = strtotime($dateEnd);
	
		if($dateStart==$dateEnd){
			$table = $this->_wrt_user_table.'_'.date('Y_m_d',$dateStart);
			if($this->is_table_exists($table)){
				return array($table);
			}else{
				return array();
			}
		}elseif ($dateStart<$dateEnd){
			$table_arr = array();
			for($i = $dateStart;$i<=$dateEnd;$i=$i+86400){
				$table = $this->_wrt_user_table.'_'.date('Y_m_d',$i);
				if($this->is_table_exists($table)){
					$table_arr[] = $table;
				}
			}
			return  $table_arr;
		}
	}
	
	private function formatTrafficDataHourly(&$data,$res) {
	    if($res){
			foreach($res as $key => $value){
				$data['time'] .= "'".date('H:i:s',$value['time'])."',";
				$data['data'] .= $value['traffic'].',';
				$data['data1'] .= $value['traffic_up'].',';
				$data['data2'] .= $value['traffic_dn'].',';
			}
			$data['time'] = trim($data['time'],',');
			$data['data'] = trim($data['data'],',');
			$data['data1'] = trim($data['data1'],',');
			$data['data2'] = trim($data['data2'],',');
	    }
	}
	
	private function formatTrafficDataDayly(&$data,$res,$day_time){
		if($res){
			$time_i = 0;
			$res_i = 0;
				
			for ($time_i = 0; $time_i < count($day_time) - 1; $time_i++) {
				if (isset($res[$res_i]) && $res[$res_i]['time'] == $day_time[$time_i]) {		
					$data['time'] .= "'".date('Y-m-d H:i:00',$res[$res_i]['time'])."',";
					$data['data'] .= $res[$res_i]['traffic'].',';
					$data['data1'] .= $res[$res_i]['traffic_up'].',';
					$data['data2'] .= $res[$res_i]['traffic_dn'].',';
					$res_i++;
				} else	{
					$data['time'] .= "'".date('Y-m-d H:i:00',$day_time[$time_i])."',";
					$data['data'] .= '0'.',';
					$data['data1'] .= '0'.',';
					$data['data2'] .= '0'.',';
				}
			}
		}else{
			foreach($day_time as $t_key => $t_value){
				$data['time'] .= "'".date('Y-m-d H:i:00',$t_value)."',";
				$data['data'] .= '0'.',';
				$data['data1'] .= '0'.',';
				$data['data2'] .= '0'.',';
			}
		}
	
		$data['time'] = trim($data['time'],',');
		$data['data'] = trim($data['data'],',');
		$data['data1'] = trim($data['data1'],',');
		$data['data2'] = trim($data['data2'],',');
	}
	
	private function formatTrafficDataMonthly(&$data,$res,$month_time) {
		if($res) {
			$time_i = 0;
			$res_i = 0;
				
			for ($time_i = 0; $time_i < count($month_time) - 1; $time_i++)
			{
				if (isset($res[$res_i]) && $res[$res_i]['time'] == $month_time[$time_i])
				{
				$data['time'] .= "'".date('Y-m-d H:00:00',$res[$res_i]['time'])."',";
						$data['data'] .= $res[$res_i]['traffic'].',';
						$data['data1'] .= $res[$res_i]['traffic_up'].',';
						$data['data2'] .= $res[$res_i]['traffic_dn'].',';
						$res_i++;
				}
				else
				{
				$data['time'] .= "'".date('Y-m-d H:00:00',$month_time[$time_i])."',";
				$data['data'] .= '0'.',';
						$data['data1'] .= '0'.',';
						$data['data2'] .= '0'.',';
				}
			}
		} else {
			foreach($month_time as $t_key => $t_value) {
				$data['time'] .= "'".date('Y-m-d H:00:00',$t_value)."',";
				$data['data'] .= '0'.',';
						$data['data1'] .= '0'.',';
								$data['data2'] .= '0'.',';
			}
		}
	
		$data['time'] = trim($data['time'],',');
		$data['data'] = trim($data['data'],',');
		$data['data1'] = trim($data['data1'],',');
		$data['data2'] = trim($data['data2'],',');
	}
	
	function getHourTime() {
		$hour_time_arr = array ();
		$min = date ( 'i' ) - date ( 'i' ) % 5;
		$end_time = strtotime ( date ( "Y-m-d H:$min:00", time () ) );
		// 12* 24
		for($i = 288; $i >= 0; $i --) {
			$day_time_arr [$i] = $end_time;
			$end_time = $end_time - 300;
		}
		return $day_time_arr;
	}
	
	function getDayTime() {
		$day_time_arr = array ();
		$min = date ( 'i' ) - date ( 'i' ) % 5;
		$end_time = strtotime ( date ( "Y-m-d H:$min:00", time () ) );
		// 12* 24
		for($i = 288; $i >= 0; $i --) {
			$day_time_arr [$i] = $end_time;
			$end_time = $end_time - 300;
		}
		return $day_time_arr;
	}
	
	function getMonthTime(){
		$month_time_arr = array();
		$end_time = strtotime(date('Y-m-d H:00:00',time()));
		//24*30
		for($i=720;$i>=0;$i--){
			$month_time_arr[$i] =  $end_time;
			$end_time = $end_time - 3600;
		}
		return $month_time_arr;
	}
	
	/**
	 * 设备资源查询--hourly
	 */
	private function getDevUseRateHourly($field='mem', $device_mac='') {
		
		$db_field = self::getFieldName($field);

		$data = array('time'=>'','data'=>'');
		
		$where = self::getDevUseRateQuery($device_mac);
		$device_mac && $where .= " and `device_mac`= '$device_mac'";
		$sql = "SELECT `time`, $db_field FROM `".C(DB_NAME)."`.`device_stat_hourly` $where ORDER BY `time` asc ";
		$res = M()->query($sql);
		$start_time_tmp = time()-3600;
		
		if($field == "traffic"){
		    $data = array('time'=>'','data'=>'','data1'=>'','data2'=>'');
		    $res && $start_time_tmp = $res[0]['time'];
		    $time_arr['year'] = date('Y',$start_time_tmp);
    		$time_arr['mon'] = date('m',$start_time_tmp)-1;
    		$time_arr['day'] = date('d',$start_time_tmp);
    		$time_arr['hour'] = date('H',$start_time_tmp);
    		$time_arr['min'] = date('i',$start_time_tmp);
    		$time_arr['sec'] = date('s',$start_time_tmp);
    		$data['start_time_tmp'] = $time_arr;
		
		    self::formatTrafficDataHourly($data,$res);
		    return $data;
		}
		
		if($res){
			$start_time_tmp = $res[0]['time'];
			foreach($res as $key => $value){
				$data['time'] .= "'".date('H:i:s',$value['time'])."',";
				$data['data'] .= $value[$db_field].',';
			}
			$data['time'] = trim($data['time'],',');
			$data['data'] = trim($data['data'],',');
		}
		
		$time_arr['year'] = date('Y',$start_time_tmp);
		$time_arr['mon'] = date('m',$start_time_tmp)-1;
		$time_arr['day'] = date('d',$start_time_tmp);
		$time_arr['hour'] = date('H',$start_time_tmp);
		$time_arr['min'] = date('i',$start_time_tmp);
		$time_arr['sec'] = date('s',$start_time_tmp);
		$data['start_time_tmp'] = $time_arr;

		return $data;
	}
	
	/**
	 * 设备资源查询--dayly
	 */
	private function getDevUseRateDayly($field='mem', $device_mac=''){
		$db_field = self::getFieldName($field);
		$data = array('time'=>'','data'=>'');
		
		$where = self::getDevUseRateQuery($device_mac);
		$device_mac && $where .= " and `device_mac`= '$device_mac'";
		
		$min = date ( 'i' ) - date ( 'i' ) % 5;
		$end_time_tmp = strtotime ( date ( "Y-m-d H:$min:00", time () ) ); 
		$start_time_tmp = $end_time_tmp - 86400;
		
		$where  .= " and time >= $start_time_tmp and time <= $end_time_tmp ";
		$sql = "SELECT   `time` ,$db_field FROM `".C(DB_NAME)."`.`device_stat_dayly` $where ORDER BY `time` asc ";
		$res = M()->query($sql);
		$day_time = self::getDayTime();
				
		$time_arr['year'] = date('Y',$start_time_tmp);
		$time_arr['mon'] = date('m',$start_time_tmp)-1;
		$time_arr['day'] = date('d',$start_time_tmp);
		$time_arr['hour'] = date('H',$start_time_tmp);
		$time_arr['min'] = date('i',$start_time_tmp);
		$time_arr['sec'] = date('s',$start_time_tmp);
		$data['start_time_tmp'] = $time_arr;
		
	    if($field == "traffic"){
		    $data = array('time'=>'','data'=>'','data1'=>'','data2'=>'');

    		$time_arr['year'] = date('Y',$start_time_tmp);
    		$time_arr['mon'] = date('m',$start_time_tmp)-1;
    		$time_arr['day'] = date('d',$start_time_tmp);
    		$time_arr['hour'] = date('H',$start_time_tmp);
    		$time_arr['min'] = date('i',$start_time_tmp);
    		$time_arr['sec'] = date('s',$start_time_tmp);
    		$data['start_time_tmp'] = $time_arr;
		
		    self::formatTrafficDataDayly($data,$res,$day_time);
		    return $data;
		}
		
		if($res){
			$time_i = 0;
			$res_i = 0;
			
			for ($time_i = 0; $time_i < count($day_time) - 1; $time_i++)
			{
				if (isset($res[$res_i]) && $res[$res_i]['time'] == $day_time[$time_i])
				{
					$data['time'] .= "'".date('Y-m-d H:i:00',$res[$res_i]['time'])."',";
					$data['data'] .= $res[$res_i][$db_field].',';
					$res_i++;
				}
				else
				{			
					$data['time'] .= "'".date('Y-m-d H:i:00',$day_time[$time_i])."',";
					$data['data'] .= '0'.',';
				}
				
			}
		}else{
			foreach($day_time as $t_key => $t_value){
				$data['time'] .= "'".date('Y-m-d H:i:00',$t_value)."',";
				$data['data'] .= '0'.',';
			}
		}

		
		$data['time'] = trim($data['time'],',');
		$data['data'] = trim($data['data'],',');
		
		$time_arr['year'] = date('Y',$start_time_tmp);
		$time_arr['mon'] = date('m',$start_time_tmp)-1;
		$time_arr['day'] = date('d',$start_time_tmp);
		$time_arr['hour'] = date('H',$start_time_tmp);
		$time_arr['min'] = date('i',$start_time_tmp);
		$time_arr['sec'] = date('s',$start_time_tmp);
		$data['start_time_tmp'] = $time_arr;
		return $data;
	} 
	
	/**
	 * 设备资源查询--monthly
	 */
	private function getDevUseRateMonthly($field='mem', $device_mac=''){
	
		$db_field = self::getFieldName($field);
		$data = array('time'=>'','data'=>'');
		
		$where = self::getDevUseRateQuery($device_mac);
		 
		$device_mac  && $where .= " and `device_mac`= '$device_mac'";
		$sql = "SELECT   `time` ,$db_field FROM `".C(DB_NAME)."`.`device_stat_monthly` $where ORDER BY `time` asc ";
		$res = M()->query($sql);
		$month_arr = self::getMonthTime();
		
		$start_time_tmp = $month_arr[0];
		
	    if($field == "traffic"){
		    $data = array('time'=>'','data'=>'','data1'=>'','data2'=>'');
		    
    		$time_arr['year'] = date('Y',$start_time_tmp);
    		$time_arr['mon'] = date('m',$start_time_tmp)-1;
    		$time_arr['day'] = date('d',$start_time_tmp);
    		$time_arr['hour'] = date('H',$start_time_tmp);
    		$time_arr['min'] = date('i',$start_time_tmp);
    		$time_arr['sec'] = date('s',$start_time_tmp);
    		$data['start_time_tmp'] = $time_arr;
		
		    self::formatTrafficDataMonthly($data,$res,$month_arr);
		    return $data;
		}		
		
		if($res){
			$time_i = 0;
			$res_i = 0;
			foreach($res as $key => $value){
				$time_tmp = strtotime(date(date('Y-m-d H:00:00',$value['time'])));
				if($time_tmp>$month_arr[$time_i]){
					for($j=$month_arr[$time_i];$j<=$time_tmp;$j=$j+3600){
						if($j==$time_tmp){
							$data['time'] .= "'".date('Y-m-d H:00:00',$value['time'])."',";
							$data['data'] .= $value[$db_field].',';
						}else{
							$data['time'] .= "'".date('Y-m-d H:00:00',$j)."',";
							$data['data'] .= '0'.',';
						}
						$time_i = $time_i + 1;						
					}
				}elseif ($time_tmp<$month_arr[$time_i]){
					for($j=$time_tmp;$j<=$month_arr[$time_i];$j=$j+3600){
						$data['time'] .= "'".date('Y-m-d H:00:00',$j)."',";
						$data['data'] .= '0'.',';
					}
				}else{
					$data['time'] .= "'".date('Y-m-d H:00:00',$value['time'])."',";
					$data['data'] .= $value[$db_field].',';
					$time_i = $time_i + 1;
				}
			}
		}else{
			foreach($month_arr as $t_key => $t_value){
				$data['time'] .= "'".date('Y-m-d H:00:00',$t_value)."',";
				$data['data'] .= '0'.',';
			}
		}
		$data['time'] = trim($data['time'],',');
		$data['data'] = trim($data['data'],',');
		
		$time_arr['year'] = date('Y',$start_time_tmp);
		$time_arr['mon'] = date('m',$start_time_tmp)-1;
		$time_arr['day'] = date('d',$start_time_tmp);
		$time_arr['hour'] = date('H',$start_time_tmp);
		$time_arr['min'] = date('i',$start_time_tmp);
		$time_arr['sec'] = date('s',$start_time_tmp);
		$data['start_time_tmp'] = $time_arr;
		
		return $data;
	}
	
	public function getDevUseRate($type='hourly', $field='mem', $device_mac='') {		
		switch ($type) {
			case 'dayly':
				return self::getDevUseRateDayly($field, $device_mac);	
			case 'monthly':
				return self::getDevUseRateMonthly($field, $device_mac);
			default:
				return self::getDevUseRateHourly($field, $device_mac);
		}
		
		return NULL;
	}
    
    public function getFlow() {
        $data = $this->get_page_config ();
        $current_page = $this->get_current_page ();
        $numPerPage = $this->get_num_per_page();
        $limit = ' limit  ' . ($current_page - 1) * $numPerPage . ',' . $numPerPage;
        $where = self::getWhereCondition();

        $tables = self::getTablesByDateRange(); 
        $sql = '';
        $sql_cnt = '';
        if($tables){
            foreach ($tables as $key => $value){
                $sql == '' ? $sql .= " SELECT * FROM `$value` $where " : $sql .= " union all SELECT * FROM `$value` $where ";
            }

            $sql && $sql = "SELECT mac,SUM(dn_bytes+up_bytes) AS flow from (" . $sql . ") as tmp GROUP BY mac ORDER BY flow DESC ";
            $sql && $sql_cnt = "select count(1) as cnt from ( SELECT * from (" . $sql . ") as tmp GROUP BY mac) as tmp1";

            $sql && $sql .= " $limit";
        }
        if($sql){
            $data ['data'] = $this->get_all ( $sql );
            $res_cnt = $this->get_one ( $sql_cnt );
            $data ['totalCount'] = $res_cnt ['cnt'];
        }else{
            $data ['data'] = array();
            $data ['totalCount'] = 0;
        }
        return $data;
    }

    public function getOnlineTime() {
        $data = $this->get_page_config ();
        $current_page = $this->get_current_page ();
        $numPerPage = $this->get_num_per_page();
        $limit = ' limit  ' . ($current_page - 1) * $numPerPage . ',' . $numPerPage;
        $where = self::getWhereCondition();

        $tables = self::getTablesByDateRange(); 
        $sql = '';
        $sql_cnt = '';
        if($tables){
            foreach ($tables as $key => $value){
                $sql == '' ? $sql .= " SELECT * FROM `$value` $where " : $sql .= " union all SELECT * FROM `$value` $where ";
            }

            $sql && $sql = "SELECT mac,SUM(offline_time - online_time) AS online from (" . $sql . ") as tmp GROUP BY mac ORDER BY online DESC ";
            $sql && $sql_cnt = "select count(1) as cnt from ( SELECT * from (" . $sql . ") as tmp GROUP BY mac) as tmp1";
            $sql && $sql .= " $limit";
        }
        if($sql){
            $data ['data'] = $this->get_all ( $sql );
            $res_cnt = $this->get_one ( $sql_cnt );
            $data ['totalCount'] = $res_cnt ['cnt'];
        }else{
            $data ['data'] = array();
            $data ['totalCount'] = 0;
        }
        return $data;
    }
}