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
class ShootingControlController extends AdminBaseController
{	
	public function index()
	{
		$this->redirect('Admin/ShootingControl/parameterlist');
	}
	
	public function parameterlistSearch($search_serial='')
    {
        $_SESSION['search_field'] = array('serial'=>$search_serial);

        self::parameterlist();
    }

    public function parameterlist()
    {    
		//dump($_SESSION['current_device_serial']);
        $page = I('get.page', C('PAGER'));   
		
        $search_serial = $_SESSION['search_field']['serial'];
		
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
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
		
        $this->assign('page', $page);
        $this->assign('listname', '参数控制');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $list);
        $this->assign('total_count', $count);
        $this->display('parameter');
		
		$_SESSION['search_field']['serial'] = '';
    }

	public function parameterEdit($serial = "")
	{
		if ($serial == "")
			$this->error('设备未找到');
		
		$dev_info = M('ysf_device')->where('serial=\'%s\'', $serial)->select();
		
		$arr = explode(",", $dev_info[0]['aperture_range']);
		foreach ($arr as $one) {
			$list_aperture[]['aperture'] = trim($one);
		}
		
		$arr = explode(",", $dev_info[0]['shutter_range']);
		foreach ($arr as $one) {
			$list_shutter[]['shutter'] = trim($one);
		}
		
		$arr = explode(",", $dev_info[0]['iso_range']);
		foreach ($arr as $one) {
			$list_iso[]['iso'] = trim($one);
		}
		
		//print_r($dev_info[0]);
		
		$this->assign('devinfo', $dev_info[0]);
		$this->assign('action_name', 'editParameter');
		$this->assign('action', '修改参数');
		$this->assign('list_aperture', $list_aperture);
		$this->assign('list_shutter', $list_shutter);
		$this->assign('list_iso', $list_iso);
		$this->display('parameterEdit');
	}
	
	private function notifyCnmServerWithUdp($serial)
	{
		$handle = stream_socket_client("udp://127.0.0.1:50000", $errno, $errstr); 
		
		if(!$handle){
			die("ERROR: {$errno} - {$errstr}\n"); 
		}

		fwrite($handle, $serial . "\n"); 
		
		//$result = fread($handle, 1024); 
		
		fclose($handle); 
		
		//return $result; 
	}
	
	public function parameterEditHandle()
	{
		$serial = I('post.serial');
		$aperture_preview = I('post.aperture_preview');
		$shutter_preview = I('post.shutter_preview');
		$iso_preview = I('post.iso_preview');
		
		$aperture_min = I('post.aperture_min');
		$shutter_min = I('post.shutter_min');
		$iso_min = I('post.iso_min');
		
		$aperture_max = I('post.aperture_max');
		$shutter_max = I('post.shutter_max');
		$iso_max = I('post.iso_max');
		
		$type = I('post.button_type');

		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();

		if (!$old_dev)
		{
			$this->error('设备'.$serial.'不存在');
		}

		if ($old_dev[0]['device_ip'] == 0)
		{
			$this->error('设备'.$serial.'不在线');
		}
		
		if ($type == "button_preview") {

			//delete old preview
			$this->deletePreview($serial);

			//record current time
			$this->recordPreviewTime($serial);

			if ($old_dev[0]['status_preview'] == 1)
			{
				//$this->error('设备'.$serial.'正在预览');
			}

			$data = array (
					'serial' => $serial,
					'aperture_preview' => $aperture_preview,
					'shutter_preview' => $shutter_preview,
					'iso_preview' => $iso_preview,
					'status_preview' => 0,
					'preview_sync_seq' => $old_dev[0]['preview_sync_seq'] + 1
					);
					
		
			$dev_db->where('serial=\'%s\'', $serial)->save($data);
			
			$this->notifyCnmServerWithUdp($serial);

			LogLogic::addLog('设置拍摄预览'.$serial);

			$this->success('设置拍摄预览成功', U('Admin/ShootingControl/parameterEdit', array('serial' => $serial)));
		} 
		else { //button_apply
			$data = array (
					'serial' => $serial,
					'aperture_base' => $aperture_preview,
					'shutter_base' => $shutter_preview,
					'iso_base' => $iso_preview,
					'aperture_min' => $aperture_min,
					'shutter_min' => $shutter_min,
					'iso_min' => $iso_min,
					'aperture_max' => $aperture_max,
					'shutter_max' => $shutter_max,
					'iso_max' => $iso_max,
					'base_sync_seq' => $old_dev[0]['base_sync_seq'] + 1,
					'limits_sync_seq' => $old_dev[0]['limits_sync_seq'] + 1
					);

			$dev_db->where('serial=\'%s\'', $serial)->save($data);

			LogLogic::addLog('更新拍摄参数'.$serial);

			$this->success('修改拍摄成功', U('Admin/ShootingControl/parameterEdit', array('serial' => $serial)));
		}
	}
	
	public function parameterTaskHandle($serial = "", $tasks_status = "")
	{

		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();

		if (!$old_dev)
		{
			$this->error('设备'.$serial.'不存在');
		}

		if ($old_dev[0]['device_ip'] == 0)
		{
			$this->error('设备'.$serial.'不在线');
		}
		
		$data = array (
			'serial' => $serial,
			'tasks_status' => $tasks_status,
			'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
		);
		
		$dev_db->where('serial=\'%s\'', $serial)->save($data);

		if ($tasks_status) {
			LogLogic::addLog('开启拍摄任务'.$serial);
			$this->success('开启拍摄任务成功', U('Admin/ShootingControl/parameterlist'));
		}
		else {

			LogLogic::addLog('关闭拍摄任务'.$serial);
			$this->success('关闭拍摄任务成功', U('Admin/ShootingControl/parameterlist'));
		}

	}
	
	private function Base64EncodeImage($ImageFile) {
		if (file_exists($ImageFile) || is_file($ImageFile)){
			$base64_image = '';            
			$image_info = getimagesize($ImageFile);            
			$image_data = fread(fopen($ImageFile, 'r'), filesize($ImageFile));            
			$base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));            
			return $base64_image;        
		}
		else{            
			return false;        
		}
	}

	public function parameterPreviewHandle($serial = "")
	{
		if ($serial == "")
			$this->error('设备未找到');
		
		$dev_info = M('ysf_device')->where('serial=\'%s\'', $serial)->select();
		
		if (!$dev_info)
			$this->error('设备未找到');
		
		$image_file = "/mnt/photos/$serial/preview/preview.jpeg";
		
		$base64_image = $this->Base64EncodeImage($image_file);
		
		$this->assign('devinfo', $dev_info[0]);
		$this->assign('action_name', 'parameterPreview');
		$this->assign('action', '拍摄预览');
		$this->assign('image', $base64_image);
		$this->display('parameterPreview');
	}
	
	public function parameterPreviewSrc($serial = "")
	{
		if ($serial == "")
			exit;
		
		$base64_image = "";
		$image_file = "/mnt/photos/$serial/preview/preview.jpeg";
		$time_file = "/mnt/photos/$serial/preview/preview.time";

		if (file_exists($image_file)) {
			$base64_image = $this->Base64EncodeImage($image_file);
		}

		if (file_exists($time_file)) {
			$time_start = file_get_contents($time_file);
			$time_now = mktime();

			if ($time_now >= (3*60 + (int)$time_start)) {
				echo "0";
				exit;
			}
		}
		
		echo $base64_image;
		exit;
	}	
	
	private function deletePreview($serial = "")
	{
		if ($serial == "")
			exit;
		
		$image_file = "/mnt/photos/$serial/preview/preview.jpeg";
		unlink($image_file);
	}	
	
	private function recordPreviewTime($serial = "")
	{
		if ($serial == "")
			exit;
		
		$time_file = "/mnt/photos/$serial/preview/preview.time";
		file_put_contents($time_file, mktime());
	}
}
