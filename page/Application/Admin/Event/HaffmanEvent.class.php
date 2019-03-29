<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-13
 */

namespace Admin\Event;
use Think\Model;


/**
 * Class PostsEvent
 * @package Admin\Event
 */
class HaffmanEvent
{
	private function binary_plus($binstr1, $binstr2) {
		$bin_arr1 = str_split($binstr1);
		$bin_arr2 = str_split($binstr2);
		$arr_len1 = count($bin_arr1);
		$arr_len2 = count($bin_arr2);
		$sum_arr = array();
	
		if ($arr_len1 < $arr_len2) {
			$short_arr = &$bin_arr1;
		} else {
			$short_arr = &$bin_arr2;
		}
	
		#将两个数组的长度补到一样长，短数组在前面补0
		for ($i = 0; $i < abs($arr_len1 - $arr_len2); $i++) {
			array_unshift($short_arr, 0);
		}
	
		$carry = 0;    #进位标记
		for ($i = count($bin_arr1) - 1; $i >= 0; $i--) {
		$result = $bin_arr1[$i] + $bin_arr2[$i] + $carry;
		switch ($result) {
			case 0:
			array_unshift($sum_arr, 0);
				$carry = 0;
				break;
				case 1:
				array_unshift($sum_arr, 1);
				$carry = 0;
				break;
				case 2:
				array_unshift($sum_arr, 0);
				$carry = 1;
				break;
				case 3:
				array_unshift($sum_arr, 1);
				$carry = 1;
				break;
				default:
				die();
			}
		}
	
		if($carry == 1) {
			array_unshift($sum_arr, 1);
		}
	
		return implode("", $sum_arr);
	}
	
	public function getQueryString($option='') {
		$haffman_key_mask_len = C('haffman_key_mask_len');

		$query_string = '';
		
		$uid = ( int )$_SESSION [C('USER_AUTH_KEY')];
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
		
		if ($utype == 1) { // 1、系统
			if ($user['privilege_level'] == 1)
			{
				$query_string = '1';
			}
			else
			{
				$haffman_key_show_prefix_len = intval($haffman_key_mask_len[$user['privilege_level']]);
				$current_haffman_key_show_prefix = substr($user['haffman_key'], 0, $haffman_key_show_prefix_len);
				$query_string .= "LEFT(`haffman_key`,$haffman_key_show_prefix_len) = '" . $current_haffman_key_show_prefix . "'";
			}
		} else { // 设备管理员
			$query_string .= $option."=$uid";
		}
		
		return $query_string;
	}
	
	public function get_wherecondition() {
		$where = ' where 1';
		$haffman_key_mask_len = C('haffman_key_mask_len');
		$uid = ( int )$_SESSION [C('USER_AUTH_KEY')];
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
		
	
		$device_mac_sql = '';
		if ($utype == 1) {
			// 1、系统
			if ($user['privilege_level'] != 1) {
				$haffman_key_show_prefix_len = intval($haffman_key_mask_len[$user['privilege_level']]);
			    $current_haffman_key_show_prefix = substr($user['haffman_key'], 0, $haffman_key_show_prefix_len);
	
				$where_mac = " where LEFT(`haffman_key`,$haffman_key_show_prefix_len) = '" . $current_haffman_key_show_prefix . "'";
				$device_mac_sql = "select mac from `wrt_device` $where_mac";
			}
		}else{//设备管理员
			$device_mac_sql = "SELECT mac FROM `wrt_device` a JOIN `wrt_user` b ON a.user_id = b.id WHERE b.id = '".$uid."'";
		}
		$device_mac_sql && $where .= " and `device_mac` in ( $device_mac_sql ) ";
	
		return $where;
	}
	
	public function getDevUnderCtrQueryString($device_mac='') {
		$haffman_key_mask_len = C('haffman_key_mask_len');

		$query_string = '1';
		
		if ($device_mac != '') {
			$query_string .= " and `device_mac` = '".$device_mac."'";
		} else {
			$uid = ( int )$_SESSION [C('USER_AUTH_KEY')];
			$user = $_SESSION[C('USER_AUTH_INFO')];
		
			$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
		
			$device_mac_sql = '';
			if ($utype == 1) { // 1、系统
				if ($user['privilege_level'] != 1)
				{
					$haffman_key_show_prefix_len = intval($haffman_key_mask_len[$user['privilege_level']]);
					$current_haffman_key_show_prefix = substr($user['haffman_key'], 0, $haffman_key_show_prefix_len);
					
					$where_mac = " where LEFT(`haffman_key`,$haffman_key_show_prefix_len) = '" . $current_haffman_key_show_prefix . "'";
					$device_mac_sql = "select mac from `".C(DB_NAME)."`.`wrt_device` $where_mac";
				}
			} else { // 设备管理员
				$device_mac_sql = "SELECT mac FROM __DEVICE__ a JOIN `wrt_user` b ON a.user_id = b.id WHERE b.name = '".$user[0]['name']."'";
			}
			
			$device_mac_sql && $query_string .= " and `device_mac` in ( $device_mac_sql ) ";
			
		}	
		return $query_string;
	}
	
	public function getAssignHaffmanKey($option='') {
		$haffman_key_mask_len = C('haffman_key_mask_len');
		
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$haffman_key_show_prefix_len = intval($haffman_key_mask_len[$user['privilege_level'] + 1]);
		$current_haffman_key_prefix = substr($user['haffman_key'], 0, $haffman_key_show_prefix_len);//当前用户的haffman_key前缀
		
		$current_assign_id = $user['current_assign_id'];//获取当前用户分配到的haffman_key 的id
		$user['current_assign_id']++;
		
		//马上更新id
		$user_db=M('User');
		$user_db->current_assign_id = '(current_assign_id+1)';
		$user_db->save();
		$_SESSION[C('USER_AUTH_INFO')] = $user;
		
		
		$current_assign_id_bin = base_convert($current_assign_id, 10, 2);//转成二进制
		$assign_haffman_key_prefix = self::binary_plus($current_haffman_key_prefix, $current_assign_id_bin);//分配的haffman_key前缀
		$assign_haffman_key = sprintf('%-064s',$assign_haffman_key_prefix);//格式化成64位
		
		return $assign_haffman_key;
	}
	
	public function getWblQueryString($option='') {
		$query_string = '';
	
		$uid = ( int )$_SESSION [C('USER_AUTH_KEY')];
		$user = $_SESSION[C('USER_AUTH_INFO')];
	
		$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
		
		$query_string = self::getQueryString('user_id');
		
		$query_string & $query_string = "select mac from `".C(DB_NAME)."`.`wrt_device` where $query_string";
		$query_string & $query_string = "`device_mac` in ( $query_string )";
	
		return $query_string;
	}
	
	public function chkHaffmanKey($haffman_key)
	{
		$haffman_key_mask_len = C('haffman_key_mask_len');

		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
		
		if ($utype == 1) { // 1、系统
			$haffman_key_show_prefix_len = intval($haffman_key_mask_len[$user['privilege_level']]);
			if (substr($user['haffman_key'], 0, $haffman_key_show_prefix_len) == substr($haffman_key, 0, $haffman_key_show_prefix_len))
				return true;
			
		} else { // 设备管理员
			if ($user['haffman_key'] == $haffman_key)
				return true;
		}
		
		return false;
	}
	
	public function getUserType() {
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$utype = intval($user['type']); // 管理员类型：1、系统，2、设备
		return $utype;
	}
	
	public function getUserName() {
		$user = $_SESSION[C('USER_AUTH_INFO')];
	
		$utype = intval($user['name']);
		return $utype;
	}
}