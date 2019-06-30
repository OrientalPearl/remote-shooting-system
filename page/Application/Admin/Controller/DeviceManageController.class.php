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
use Common\Logic\LogLogic;

/**
 * Class SystemController
 * @package Admin\Controller
 */
class DeviceManageController extends AdminBaseController
{	
	private function getsize($size, $format = 'KB') 
	{
		$p = 0;
		if ($format == 'KB') {
			$p = 1;
		} elseif ($format == 'MB') {
			$p = 2;
		} elseif ($format == 'GB') {
			$p = 3;
		}
		$size /= pow(1024, $p);
		return number_format($size, 3).$format;
	}
		
	private function devGetDiskUsage($serial = '')
	{
		//$list = scandir("/mnt/photos/" . $serial);
		//$list = glob("/mnt/photos/" . $serial . "/*.jpg");
		$ret = 0;

		$folder = "/mnt/photos/$serial/raw";
		$cmd = "du -sh " . $folder . "|awk '{print $1}'";

		exec($cmd, $output, $ret);
		
		//dump($output);
		//dump($ret);
		//exit;
		
		if ($ret == 0)
			return $output[0];
		
	}

	public function index()
	{
		$this->redirect('Admin/DeviceManage/devlist');
	}
	
	public function devlistSearch($search_serial='', $search_user_id='', $search_version='', $search_status='')
    {
        $_SESSION['search_field'] = array('serial'=>$search_serial,
            'user_id'=>$search_user_id,
            'version'=>$search_version,
			'status'=>$search_status);

        self::devlist();
    }
	
	// 设备列表
    /**
     *
     */
    public function devlist()
    {    
		//dump($_SESSION['current_device_serial']);
        $page = I('get.page', C('PAGER'));   
		
        $search_serial = $_SESSION['search_field']['serial'];
        $search_user_id = $_SESSION['search_field']['user_id'];
        $search_version = $_SESSION['search_field']['version'];
        $search_status = $_SESSION['search_field']['status'];
		
        //$map['_string'] = HaffmanEvent::getQueryString('user_id'); 
        
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
        if ($search_status == '1') {
			$map['device_ip'] = 1;
        } else if ($search_status == '2') {
        	$map['device_ip'] = 0;
        } else {
        }

        if ($search_user_id != '') {
			$map['user_id'] = $search_user_id;
        }
        
        if ($search_version != '')
        	$map['version'] = $search_version;
        
        if ($search_serial != '')
        	$map['serial'] = $search_serial;
        
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		
        $Device = D('ysf_device');
        $count = $Device->where($map)->count(); // 查询满足要求的总记录数

        if ($count != 0) {
            $Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
            $pager_bar = $Page->show();
            $limit = $Page->firstRow . ',' . $Page->listRows;
            $list = $Device->where($map)->limit($limit)->order('user_id')->select();           
        }
        
		
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		$user = $_SESSION[C('USER_AUTH_INFO')];

		//get disk usage
		$list_final = array();
		foreach ($list as $key=>$value) {
			$value['disk_usage'] = $this->devGetDiskUsage($value['serial']);
			$list_final[] = $value;
		}
		//print_r($list_final);
		
        $this->assign('page', $page);
        $this->assign('listname', '设备');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $list_final);
        $this->assign('total_count', $count);
        $this->assign('list_user', $list_user);
		$this->assign('user', $user);
        $this->display('devlist');
		
		$_SESSION['search_field']['serial'] = '';
        $_SESSION['search_field']['user_id'] = '';
        $_SESSION['search_field']['version'] = '';
        $_SESSION['search_field']['status'] = '';
    }

	// 设备事件日志
    /**
     *
     */
	 
	public function deveventlogSearch($search_serial='')
    {
		//var_dump();
        $_SESSION['search_field'] = array('serial'=>$search_serial);

        self::deveventlog();
    }
	
	public function deveventlog()
	{
	 	$page = I('get.page', C('PAGER'));
		
		$serial = $_SESSION['search_field']['serial'];
		
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		//var_dump($cur_user_info);
	 	
	 	if ($serial != '')
	 	{
	 		$map['serial'] = $serial;
		 }

		 if ($cur_user_info['type'] != 1)
		 	$map['user_id'] = $cur_user_info['id'];
		 
		 $Device = D('ysf_event_log');
		 $count = $Device->where($map)->count(); // 查询满足要求的总记录数
		 
		 if ($count != 0) {
			 $Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
			 $pager_bar = $Page->show();
			 $limit = $Page->firstRow . ',' . $Page->listRows;
			 $list = $Device->where($map)->limit($limit)->order('serial')->order('time desc')->select();           
		 }

	 	 
	 	$this->assign('listname', '事件日志');
	 	$this->assign('pager_bar', $pager_bar);
	 	$this->assign('list', $list);
	 	$this->assign('total_count', $count);
	 	$this->display('deveventlog');
	}
	
	// 用户列表页面操作
    /**
     *
     */
    public function deviceHandle()
    {    	
    	if (I('post.delAll') == 1) {
			$post_ids = I('post.uid_chk_box');
			
			//print_r($post_ids);exit;
			
            $res_info = '';
            foreach ($post_ids as $post_id) {
				if (0 != self::deviceDel($post_id, 0))
				{
					$res_info .= '<br />'.$post_id;
				}
            }
			
			if ($res_info == '')
				$this->success('批量删设备成功');
			else
				$this->error('批量删设备如下设备失败：'.$res_info);
        }        
		
		if (I('post.deviceAdd') == 1) {
            $this->redirect('Admin/DeviceManage/deviceAdd');
        }
    }
	
	public function deviceAdd($serial = "")
	{
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		if ($cur_user_info['type'] != 1)
			$map['id'] = $cur_user_info['id'];
			
		$list_user = M('ysf_user')->where($map)->select();
		
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$this->assign('list_user', $list_user);
		$this->assign('action_name', 'addDevice');
		$this->assign('action', '新增设备');
		$this->assign('user', $user);
		$this->display('deviceEdit');
	}
	
	public function devAddHandle()
	{
		$serial = I('post.serial');
		$area = I('post.area');
		$remark = I('post.remark');
		$user_id = I('post.user_id');

		$auto_upgrade = I('post.auto_upgrade');
		$disk_size = I('post.disk_size') * 1000; //MB
		$bwlimit = I('post.bwlimit') * 1000 / 8; //KBps
		$upload_limit_day = I('post.upload_limit_day');

		$user_db = M('ysf_user');
		$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
		
		//wrong when add by superadmin
		$haffman_key_new = HaffmanEvent::getAssignHaffmanKey();
		
		$device_db = M('ysf_device');
		if ($device_db->where('serial=\'%s\'', $serial)->select()) {
			$this->error('设备已存在');
		}
		
		$data = array (
				'serial' => $serial,
				'area' => $area,
				'remark' => $remark,
				'user_id' => $user_id,
				'user_name' => $user_name,
				'auto_upgrade' => $auto_upgrade,
				'disk_size' => $disk_size,
				'upload_limit_day' => $upload_limit_day,
				'bwlimit' => $bwlimit,
				'bwlimit_sync_seq' => 1,
				'haffman_key'=>$haffman_key_new
		);
			
		$device_db->add($data);
		//echo $device_db->_sql();
		//exit;
		LogLogic::addLog('新增设备 '.$serial);
	
		$this->success('新增成功', U('Admin/DeviceManage/devlist'));
	}
	
	public function deviceEdit($serial = "")
	{
		if ($serial == "")
			$this->error('设备未找到');
		
		$dev_info = M('ysf_device')->where('serial=\'%s\'', $serial)->select();
	
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		if ($cur_user_info['type'] != 1)
			$map['id'] = $cur_user_info['id'];
			
		$list_user = M('ysf_user')->where($map)->select();
		
		$user = $_SESSION[C('USER_AUTH_INFO')];

		$dev_info[0]['disk_size'] = $dev_info[0]['disk_size'] / 1000;
		$dev_info[0]['bwlimit'] = $dev_info[0]['bwlimit'] * 8 / 1000;
		
		$this->assign('list_user', $list_user);
		$this->assign('devinfo', $dev_info[0]);
		$this->assign('action_name', 'editDevice');
		$this->assign('action', '修改设备');
		$this->assign('user', $user);
		$this->display('deviceEdit');
	}
	
	public function devEditHandle()
	{
		$serial = I('post.serial');
		$area = I('post.area');
		$remark = I('post.remark');
		$user_id = I('post.user_id');
		

		$auto_upgrade = I('post.auto_upgrade');
		$disk_size = I('post.disk_size') * 1000;
		$bwlimit = I('post.bwlimit') * 1000 / 8;
		$upload_limit_day = I('post.upload_limit_day');

		$user_db = M('ysf_user');
		$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
	
		if (!$old_dev)
		{
			$this->error('设备'.$serial.'不存在');
		}
		
		
		//wrong when add by superadmin
		$haffman_key_new = HaffmanEvent::getAssignHaffmanKey();
		if ($bwlimit != $old_dev[0]['bwlimit']) {
			$data['bwlimit'] = $bwlimit;
			$data['bwlimit_sync_seq'] = $old_dev[0]['bwlimit_sync_seq'] + 1;
		}
		
		if ($upload_limit_day != $old_dev[0]['upload_limit_day']){

			$data['upload_limit_day'] = $upload_limit_day;
			$data['upload_limit_day_sync_seq'] = $old_dev[0]['upload_limit_day_sync_seq'] + 1;
		}

		$data['auto_upgrade'] = $auto_upgrade;
		$data['disk_size'] = $disk_size;
		$data['area'] = $area;
		$data['remark'] = $remark;

		$dev_db->where('serial=\'%s\'', $serial)->save($data);
			
		LogLogic::addLog('更新设备'.$serial);
	
		$this->success('修改成功', U('Admin/DeviceManage/devlist'));
	}
	
	public function deviceDel($serial = "", $jump = 1)
	{
		if ($serial == "")
		{
			if ($jump)
				$this->error('参数为空');
			else
				return -1;
		}
			
		$dev_db = M('ysf_device');			
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		
		if ($old_dev)
		{
			$task_db = M('ysf_task');
			$dev_count = $task_db->where('serial=%s and type=1', $serial)->count();
			
			//echo $task_db->_sql();
			
			if ($dev_count > 0)
				$this->error('请先删除此设备下的手动任务列表');
			
			$at_db = M('ysf_task');
			$dev_count = $task_db->where('serial=%s and type=0', $serial)->count();
			if ($dev_count > 0)
				$this->error('请先删除此设备下的自动任务列表');
			
			$dev_db->where('serial=\'%s\'', $serial)->delete();
			
			LogLogic::addLog('删除设备'.$serial.'成功');
			
			if ($jump)
				$this->success('删除设备' . $serial.'成功');
			else 
				return 0;
			
		}
		else{
			LogLogic::addLog('删除设备' .$serial.'失败：设备未找到');
			
			if ($jump)
				$this->error('删除设备' .$serial.'失败：设备未找到');
			else
				return -2;
		}
	}
	
	public function deviceCurrent($serial = "", $jump = 1)
	{
		if ($serial == "")
		{
			if ($jump)
				$this->error('参数为空');
			else
				return -1;
		}
			
		$user_db = M('ysf_user');
		$data = array (
				'current_device_serial' => $serial
		);
		
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		$user_db->where('id=%s', $cur_user_info['id'])->save($data);
		
		$_SESSION['current_device_serial'] = $serial;
		
		LogLogic::addLog('设置当前操作设备'.$serial.'成功');
		
		if ($jump)
			$this->success('设置当前操作设备' . $serial.'成功');
		else 
			return 0;
	}
	
	public function deviceStatis($field='mem', $device_mac = "", $shop_name = '', $ssid = '')
	{
		$data['hourly'] = DeviceResourceStat::getDevUseRate('hourly', $field, $device_mac);
		$data['dayly'] = DeviceResourceStat::getDevUseRate('dayly', $field, $device_mac);
		$data['monthly'] = DeviceResourceStat::getDevUseRate('monthly', $field, $device_mac);
		
		$menu = array(				
			'mem'=>array(
					'field'=>'mem',
					'name'=>'内存使用率','y_title'=>'使用率','step'=>'200','Suffix'=>'%',
					'url'=>U('Admin/DeviceManage/deviceStatis', array('field'=>'mem', 'device_mac'=>$device_mac, 'shop_name' => $shop_name, 'ssid' => $ssid))
				),
				
			'cpu'=>array(
					'field'=>'cpu',
					'name'=>'cpu使用率','y_title'=>'使用率','step'=>'20','Suffix'=>'%',
					'url'=>U('Admin/DeviceManage/deviceStatis', array('field'=>'cpu', 'device_mac'=>$device_mac, 'shop_name' => $shop_name, 'ssid' => $ssid))
			),
				
			'traffic'=>array(
					'field'=>'traffic',
					'name'=>'流量统计','y_title'=>'流量(Byte)','step'=>'20','Suffix'=>'',
					'url'=>U('Admin/DeviceManage/deviceStatis', array('field'=>'traffic', 'device_mac'=>$device_mac, 'shop_name' => $shop_name, 'ssid' => $ssid))
			),
				
			'session'=>array(
					'field'=>'session',
					'name'=>'会话数','y_title'=>'会话数(个)','step'=>'20','Suffix'=>'',
					'url'=>U('Admin/DeviceManage/deviceStatis', array('field'=>'session', 'device_mac'=>$device_mac, 'shop_name' => $shop_name, 'ssid' => $ssid))
			),
				
			'new_session_rate'=>array(
					'field'=>'new_session_rate',
					'name'=>'新建会话数速率','y_title'=>'速率(个/s)','step'=>'20','Suffix'=>'',
					'url'=>U('Admin/DeviceManage/deviceStatis', array('field'=>'new_session_rate', 'device_mac'=>$device_mac, 'shop_name' => $shop_name, 'ssid' => $ssid))
			),
				
			'online_user_num'=>array(
					'field'=>'online_user_num',
					'name'=>'在线用户数','y_title'=>'用户数(个)','step'=>'20','Suffix'=>'',
					'url'=>U('Admin/DeviceManage/deviceStatis', array('field'=>'online_user_num', 'device_mac'=>$device_mac, 'shop_name' => $shop_name, 'ssid' => $ssid))
			)
		);
		
		//最近一小时
		$data['hourly']['Suffix'] = $menu[$field]['Suffix'];
		
		if ($field == 'traffic') {
			$series = "{name:'总',marker:{symbol: 'square'},data:[".$data['hourly']['data']."]},";
			$series .= "{name:'上行',marker:{symbol: 'square'},data:[".$data['hourly']['data1']."]},";
			$series .= "{name:'下行',marker:{symbol: 'square'},data:[".$data['hourly']['data2']."]}";
		} else {
			$series = "{name:'";			
			$series .= $menu[$field]['name'];
			$series .="',marker:{symbol: 'square'},data:[".$data['hourly']['data']."]}";
		}
		
		$data['hourly']['title'] = '最近一小时';
		$data['hourly']['y_title'] = $menu[$field]['y_title'];	
		$data['hourly']['series'] = $series;
		$data['hourly']['step'] = $menu[$field]['step'];
		
		//最近一天
		$data['dayly']['Suffix'] = $menu[$field]['Suffix'];
		
		if ($field == 'traffic') {
			$series = "{name:'总',marker:{symbol: 'square'},data:[".$data['dayly']['data']."]},";
			$series .= "{name:'上行',marker:{symbol: 'square'},data:[".$data['dayly']['data1']."]},";
			$series .= "{name:'下行',marker:{symbol: 'square'},data:[".$data['dayly']['data2']."]}";
		} else {
			$series = "{name:'";
			$series .= $menu[$field]['name'];
			$series .="',marker:{symbol: 'square'},data:[".$data['dayly']['data']."]}";
		}
		
		$data['dayly']['title'] = '最近一天';
		$data['dayly']['y_title'] = $menu[$field]['y_title'];
		$data['dayly']['series'] = $series;
		$data['dayly']['step'] = $menu[$field]['step'];
		
		//最近一月
		$data['monthly']['Suffix'] = $menu[$field]['Suffix'];
		
		if ($field == 'traffic') {
			$series = "{name:'总',marker:{symbol: 'square'},data:[".$data['monthly']['data']."]},";
			$series .= "{name:'上行',marker:{symbol: 'square'},data:[".$data['monthly']['data1']."]},";
			$series .= "{name:'下行',marker:{symbol: 'square'},data:[".$data['monthly']['data2']."]}";
		} else {
			$series = "{name:'";
			$series .= $menu[$field]['name'];
			$series .="',marker:{symbol: 'square'},data:[".$data['monthly']['data']."]}";
		}
		
		$data['monthly']['title'] = '最近一月';
		$data['monthly']['y_title'] = $menu[$field]['y_title'];	
		$data['monthly']['series'] = $series;
		
		$this->assign('action', '设备统计');
		$this->assign('device_mac', $device_mac);
		$this->assign('ssid', $ssid);
		$this->assign('shop_name', $shop_name);
		$this->assign('tabactive', $field);
		$this->assign('tabmenu', $menu);
		$this->assign('data', $data);
		$this->display('deviceStatis');
	}
	
	public function devExportXls()
	{	
		vendor("PHPExcel.PHPExcel");
		vendor('PHPExcel.PHPExcel.IOFactory');
		
		$map['_string'] = HaffmanEvent::getQueryString('id');
		
		$Device = D('Device');
		$data = $Device->where($map)->select(); 		
		
		$category = D('Category')->select();

		$objPHPExcel = new \PHPExcel();
		$objPHPExcel->getProperties()->setTitle("export")->setDescription("none");
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '设备列表') ;
		$objPHPExcel->getActiveSheet()->mergeCells('A1:G1');
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
		//$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
		// Field names in the first row
		$fields = array('mac'=>'设备mac','device_ip'=>'设备ip','version'=>'固件版本','token'=>'token','shop_name'=>'商店名称','area_info'=>'区域','category'=>'行业');
		$col = 0;
		foreach ($fields as $field)
		{
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 2, $field);
			$col++;
		}
		// Fetching the table data
		$row = 3;
		foreach($data as $k => $v)
		{
			$col = 0;
			foreach ($fields as $key1 => $value1)
			{
				$tmp = '';
				if($key1=='device_ip'){
					$tmp = long2ip($v[$key1]);
				}else if ($key1=='category') {
					foreach ($category as $key => $c) {
						if ($c['id'] == $v[$key1])
							$tmp = $c['category'];
					}
				}else{
					$tmp = $v[$key1];
				}

				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $tmp);
				$col++;
			}
			$row++;
		}
	
	
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
	
		$objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('B2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('B2')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('C2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('C2')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('D2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('D2')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('E2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('E2')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('F2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('F2')->getFont()->setBold(true);
		$objPHPExcel->getActiveSheet()->getStyle('G2')->getFont()->setSize(14);
		$objPHPExcel->getActiveSheet()->getStyle('G2')->getFont()->setBold(true);
	
	
	
		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		
		//$objWriter = IOFactory::createWriter($objPHPExcel, 'Excel5');
		//发送标题强制用户下载文件
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="device_list-'.uniqid().'-'.date('dMy').'.xls"');
		header('Cache-Control: max-age=0');
		$objWriter->save('php://output');
	}
}
