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
class PictureManageController extends AdminBaseController
{	
	public function index(){
		$this->redirect('Admin/PictureManage/picturelist');
	}
	
	public function picturelistSearch($search_serial=''){
        $_SESSION['search_field'] = array('serial'=>$search_serial);

        self::picturelist();
    }

	private function getsize($size, $format = 'KB') {
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
	
	private function getPictureNum($serial = ''){
		$output = array();
		$ret = 0;
		
		exec("ls -l /mnt/photos/" . $serial . "/jpg/ | grep '^-' | wc -l", $output, $ret);
		
		//dump($output);
		//dump($ret);
		//exit;
		
		if ($ret == 0)
			return $output[0];
		return 0;		
	}
		
	private function getPictureList($serial = ''){
		//$list = scandir("/mnt/photos/" . $serial);
		//$list = glob("/mnt/photos/" . $serial . "/*.jpg");

		$folder = "/mnt/photos/$serial/jpg";
		
		//dump($folder);

		$files = array();
		$files_not_standard_format = array();
		$handle = opendir($folder);
		while (false !== ($file = readdir($handle))){
			//dump($file);
			if ($file != '.' && $file != '..'){
				$hz = strstr($file, ".");
				if ($hz == ".jpeg"){
					$one['file_name'] = $file;
					//$one['file_path'] = "/cms/Public/AdminLTE/img/pic/$serial/raw/$file";
					//$one['file_path_thumbnail'] = "/cms/Public/AdminLTE/img/pic/$serial/jpg/$file";
					//$one['file_path_real'] = "/mnt/photos/$serial/raw/$file";
					$rawfilename = str_replace('.jpeg', '.nef', $file);
					$one['file_size'] = $this->getsize(filesize("/mnt/photos/$serial/raw/$rawfilename"));
					$one['file_time'] = str_replace('.jpeg', '', $file);

					if (5 != substr_count($one['file_time'], "-"))
						$files_not_standard_format[] = $one; 
					else
						$files[] = $one; 
				} else if ($hz == ".jpg"){					
					$one['file_name'] = $file;
					//$one['file_path'] = "/cms/Public/AdminLTE/img/pic/$serial/raw/$file";
					//$one['file_path_thumbnail'] = "/cms/Public/AdminLTE/img/pic/$serial/jpg/$file";
					//$one['file_path_real'] = "/mnt/photos/$serial/raw/$file";
					$rawfilename = str_replace('.jpg', '.nef', $file);
					$one['file_size'] = $this->getsize(filesize("/mnt/photos/$serial/raw/$rawfilename"));
					$one['file_time'] = str_replace('.jpg', '', $file);
					
					if (5 != substr_count($one['file_time'], "-"))
						$files_not_standard_format[] = $one; 
					else
						$files[] = $one; 
				}
			}
		}
		array_multisort(array_column($files,'file_time'), SORT_DESC, $files);
		$files = array_merge($files, $files_not_standard_format);
		//print_r($files);
		//dump($files);exit;
		return $files;
	}
	
    public function picturelist(){    
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
		
		//print_r($list[0]);
		$dev = array();
		
		foreach($list as $key=>$one){
			
			$one['picture_count'] = $this->getPictureNum($one['serial']);
			$dev[$key] = $one;
		}
		
		//print_r($dev);
		
        $this->assign('page', $page);
        $this->assign('listname', '图片列表');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $dev);
        $this->assign('total_count', $count);
        $this->display('picture');
		
		$_SESSION['search_field']['serial'] = '';
    }

	public function pictureEdit($serial = ""){
		if ($serial == "")
			$this->error('设备未找到');
		
		$dev_info = M('ysf_device')->where('serial=\'%s\'', $serial)->select();
	
		//print_r($dev_info[0]);
		
		$piclist = $this->getPictureList($serial);

		$this->assign('devinfo', $dev_info[0]);
		$this->assign('action_name', 'editParameter');
		$this->assign('piclist', $piclist);
		$this->assign('action', '设备图片');
		$this->display('pictureEdit');
	}
	
	public function pictureEditHandle() {
		$serial = I('post.serial');
		$type = I('post.button_type');

		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();

		if (!$old_dev)
		{
			$this->error('设备'.$serial.'不存在');
		}

		
		if ($type == "button_all") {

			LogLogic::addLog('全部下载并删除'.$serial);
			$this->success('全部下载并删除成功', U('Admin/PictureManage/pictureEdit', array('serial' => $serial)));
		} 
		else if ($type == "button_part_delete"){

			LogLogic::addLog('删除选中项'.$serial);
			$this->success('删除选中项成功', U('Admin/PictureManage/pictureEdit', array('serial' => $serial)));
		}
		else if ($type == "button_part_download"){
			
			LogLogic::addLog('下载选中项'.$serial);
			$this->success('下载选中项成功', U('Admin/PictureManage/pictureEdit', array('serial' => $serial)));
		}
	}
	
	public function pictureTaskHandle($serial = "", $tasks_status = "") {
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();

		if (!$old_dev)
		{
			$this->error('设备'.$serial.'不存在');
		}
		
		$data = array (
			'serial' => $serial,
			'tasks_status' => $tasks_status
		);
		
		$dev_db->where('serial=\'%s\'', $serial)->save($data);

		if ($tasks_status) {
			LogLogic::addLog('开启拍摄任务'.$serial);
			$this->success('开启拍摄任务成功', U('Admin/PictureManage/picturelist'));
		}
		else {

			LogLogic::addLog('关闭拍摄任务'.$serial);
			$this->success('关闭拍摄任务成功', U('Admin/PictureManage/picturelist'));
		}

	}

	public function pictureShow($serial = "", $filename = "") {
		$filename = $_REQUEST['filename'];
		$serial = $_REQUEST['serial'];
		$pathname = "/mnt/photos/$serial/jpg/$filename";

		$img = file_get_contents($pathname, true);
		
		header("Cache-Control: private, max-age=10800, pre-check=10800");
		header("Pragma: private");
		header("Expires: Mon, 26 Jul 2997 05:00:00 GMT");
		header("Content-Type: image/jpeg;text/html; charset=utf-8");
		echo $img;
		exit;
	}

	public function pictureDownload($serial = "", $filename = "") {
		$filename = $_REQUEST['filename'];

		$hz = strstr($filename, ".");
		if ($hz == ".jpeg")
			$rawfilename = str_replace('.jpeg', '.nef', $filename);
		else
			$rawfilename = str_replace('.jpg', '.nef', $filename);

		$serial = $_REQUEST['serial'];
		$pathname = "/mnt/photos/$serial/raw/$rawfilename";

		$handle = fopen($pathname, "rb");
		header( "Pragma: public" );
		
		header( "Expires: 0" );
		header( "Cache-Component: must-revalidate, post-check=0, pre-check=0" );
		Header("Accept-Ranges: bytes");
		header("Content-Type: application/octet-stream");
		header("Content-Length: " . filesize($pathname));
		header( "Content-Disposition: attachment; filename=".urlencode($rawfilename) );
		header( 'Content-Transfer-Encoding: binary' );
		while (!feof($handle)) {
			echo fread($handle, 8192);
	   }
		fclose($handle);
		exit;
	}	
	
	public function pictureDelete($serial = "", $filename = "") {
		$filename = $_REQUEST['filename'];

		$hz = strstr($file, ".");
		if ($hz == ".jpeg")
			$rawfilename = str_replace('.jpeg', '.nef', $filename);
		else
			$rawfilename = str_replace('.jpg', '.nef', $filename);

		$serial = $_REQUEST['serial'];
		$pathname = "/mnt/photos/$serial/jpg/$filename";
		$rawpathname = "/mnt/photos/$serial/jpg/$rawfilename";


		exec("rm -f $pathname", $output, $ret);
		exec("rm -f $rawpathname", $output, $ret);

		$this->pictureEdit($serial);
	}	

	public function pictureOperate($serial = "", $type = "") {

		$photos = json_decode($_POST['photos'],TRUE);

		if (!count($photos)){
			$this->error('未选中图片');
		}

		if ($type == "part_delete") {
			foreach ($photos as $filename) {
				//dump($filename);
				$hz = strstr($file, ".");
				if ($hz == ".jpeg")
					$rawfilename = str_replace('.jpeg', '.nef', $filename);
				else
					$rawfilename = str_replace('.jpg', '.nef', $filename);

				$pathname = "/mnt/photos/$serial/jpg/$filename";
				$rawpathname = "/mnt/photos/$serial/raw/$rawfilename";
				exec("rm -f $pathname;rm -f $rawpathname", $output, $ret);
				
			}
			$this->pictureEdit($serial);
			//exit;
		} else { //partial download
			$tarfilename = $serial. ".tar.gz";
			$tarpathname = "/mnt/photos/$serial/$tarfilename";

			$tarcmd = "cd /mnt/photos/$serial/raw/ && tar cf $tarpathname ";

			foreach ($photos as $filename) {
				
				$hz = strstr($filename, ".");
				if ($hz == ".jpeg"){
					$rawfilename = str_replace('.jpeg', '.nef', $filename);
				}
				else {
					$rawfilename = str_replace('.jpg', '.nef', $filename);
				}
				$tarcmd .= "$rawfilename ";
			}


			exec($tarcmd, $output, $ret);

			//dump($tarcmd);
			//exit;
			$handle = fopen($tarpathname, "rb");
			header( "Pragma: public" );
			
			header( "Expires: 0" );
			header( "Cache-Component: must-revalidate, post-check=0, pre-check=0" );
			Header("Accept-Ranges: bytes");
			header("Content-Type: application/octet-stream");
			header("Content-Length: " . filesize($tarpathname));
			header( "Content-Disposition: attachment; filename=".urlencode($tarfilename) );
			header( 'Content-Transfer-Encoding: binary' );
			while (!feof($handle)) {
				echo fread($handle, 8192);
		   }
			fclose($handle);
			
			exit;
		}
	}
	
	public function pictureDownloadAll($serial = "") {
		$serial = $_REQUEST['serial'];
		$filename = $serial. ".tar.gz";
		$pathname = "/mnt/photos/$serial/$filename";

		$cmd = "tar cf $pathname -C /mnt/photos/$serial/raw .";
		exec($cmd, $output, $ret);

		$handle = fopen($pathname, "rb");
		header( "Pragma: public" );
		
		header( "Expires: 0" );
		header( "Cache-Component: must-revalidate, post-check=0, pre-check=0" );
		Header("Accept-Ranges: bytes");
		header("Content-Type: application/octet-stream");
		header("Content-Length: " . filesize($pathname));
		header( "Content-Disposition: attachment; filename=".urlencode($filename) );
		header( 'Content-Transfer-Encoding: binary' );
		while (!feof($handle)) {
			 echo fread($handle, 8192);
		}

		fclose($handle);

		exec("rm -f $pathname", $output, $ret);
		exec("rm -f /mnt/photos/$serial/jpg/*", $output, $ret);
		exec("rm -f /mnt/photos/$serial/raw/*", $output, $ret);

		$this->pictureEdit($serial);
		exit;
	}	

	public function pictureDelAll($serial = "") {
		$serial = $_REQUEST['serial'];

		exec("rm -f /mnt/photos/$serial/jpg/*", $output, $ret);
		exec("rm -f /mnt/photos/$serial/raw/*", $output, $ret);

		$this->pictureEdit($serial);
		exit;
	}	
}
