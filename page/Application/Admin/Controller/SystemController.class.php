<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-11
 */

namespace Admin\Controller;

use Common\Util\GreenPage;
use Common\Util\Encrypt;
use Think\Storage;
use Think\Model;
use Admin\Event\HaffmanEvent;
use Common\Logic\LogLogic;

/**
 * Class SystemController
 * @package Admin\Controller
 */
class SystemController extends AdminBaseController
{
	public function index()
	{
		$this->redirect('Admin/System/admin');
	}
	
	public function adminSearch($search_name = '')
    {
        $_SESSION['search_field'] = array('name'=>$search_name);

        self::admin();
    }
	// 用户列表
    /**
     *
     */	
    public function admin()
    {    	
        $page = I('get.page', C('PAGER'));
        
		$search_name = $_SESSION['search_field']['name'];
		
        if ($search_name != '')
        	$map['name'] = $search_name;
               
        $map['_string'] = HaffmanEvent::getQueryString('id');

        $User = D('ysf_user');
        $count = $User->where($map)->count(); // 查询满足要求的总记录数

        if ($count != 0) {
            $Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
            $pager_bar = $Page->show();
            $limit = $Page->firstRow . ',' . $Page->listRows;
            $list = $User->where($map)->limit($limit)->select();           
        }
        
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
        $this->assign('page', $page);
        $this->assign('listname', '管理员');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $list);
        $this->assign('total_count', $count);
		 $this->assign('user', $user);
        $this->display('userlist');
		
		$_SESSION['search_field']['name'] = "";
    }
	
	// 用户列表页面操作
    /**
     *
     */
    public function adminHandle()
    {    	
        if (I('post.delAll') == 1) {
            $post_ids = I('post.uid_chk_box');
			
            $res_info = '';
            foreach ($post_ids as $post_id) {
				self::adminDel($post_id);
            }
            $this->success('批量删除管理员');
        }

        if (I('post.adminAdd') == 1) {
            $this->redirect('Admin/System/adminAdd');
        }
        
        if (I('post.search') == 1) {
        	$this->redirect('Admin/System/Admin',
        		array(				
        			'search_name' => I('post.search_name'),
        	));
        }
    }
	
	public function adminAdd()
	{
		$this->assign('action_name', 'addUser');
		$this->assign('action', '新增管理员');
		$this->display('adminadd');
	}
	
	public function adminAddHandle()
	{
		$type = 2; /* 系统（超级）管理员 1，设备管理员 2*/
		$name = I('post.user_login');
		$new_pwd_pass1 = I('post.password');
		$user_name = I('post.user_login');
		$remark = I('post.user_remark');		
		$email = I('post.email');		
		
		$user_db = M('ysf_user');
		if ($user_db->where('name=\'%s\'', $name)->select()) {
			$this->error('用户名已存在');
		}
		
		//$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		$crypt = new Encrypt();
		
		$str = "abcdefghijklmnopqrstuvwxyz!@$%^&*()_+~?ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		$shuffled = str_shuffle ($str);
		$salt_get = substr($shuffled, 5, 12 );
		
		$privilege_lever = 5;//权限级别，不能超过5
		
		{
			$encode_pwd_new = $crypt->encode ( $new_pwd_pass1, $salt_get );
			$haffman_key_new = HaffmanEvent::getAssignHaffmanKey();
				
			$data = array (
					'name' => $name,
					'pwd' => $encode_pwd_new,
					'type' => $type,
					'haffman_key'=>$haffman_key_new,
					'privilege_level'=>$privilege_lever,
					'salt' => $salt_get ,
					'user_name' => $user_name,
					'remark' => $remark,
					'email' => $email,
			);
				
			$user_db->add($data);
			
		}
		
		$this->success('新增管理员成功', U('Admin/System/admin'));
	}
	
	public function adminEdit($uid = -1)
	{
		if ($uid == -1)
			$this->error('用户未找到');
		
		$user_info = M('ysf_user')->where('id=%s', $uid)->select();
		
		$this->assign('info', $user_info[0]);
		$this->assign('action_name', 'editUser');
		$this->assign('action', '编辑管理员');
		$this->display('adminadd');
	}
	
	public function adminEditHandle()
	{
		$modify_pwd = I('post.modify_pwd');		
		$uid = I('post.user_id');
		$name = I('post.user_login');//disabled, can not get from input
		$new_pwd_pass1 = I('post.password');
		$user_name = I('post.user_login');
		$email = I('post.email');

		$remark = I('post.user_remark');

		
		$user_db = M('ysf_user');

		$crypt = new Encrypt();
		
		$user_info = $user_db->where('id=%s', $uid)->select();

		if (!$user_info) {
			$this->error('老用户未找到');
		}		
		
		$privilege_lever = $user_info[0]['privilege_level'];
		$salt_get = $user_info[0]['salt'];
		$haffman_key_new = $user_info[0]['haffman_key'];
		$name = $user_info[0]['name'];
		
		if ($modify_pwd) {
			$encode_pwd_new = $crypt->encode( $new_pwd_pass1, $salt_get );
		} else {
			$encode_pwd_new = $user_info[0]['pwd'];
		}
		
		$type = $user_info[0]['type'];

		$data = array (
				//'name' => $name,
				'pwd' => $encode_pwd_new,
				'type' => $type,
				'haffman_key'=>$haffman_key_new,
				'privilege_level'=>$privilege_lever,
				'salt' => $salt_get ,
				'user_name' => $user_name,
				'remark' => $remark,
				'email' => $email
		);
		
		$user_db->where('id=%s', $uid)->save($data);
		LogLogic::addLog('修改'.'管理员 '.$name);

		
		$this->success('修改成功', U('Admin/System/admin'));
	}
	
	public function adminDel($uid = -1)
	{
		if ($uid == -1)
			$this->error('管理员未找到');
		
		$user_db = M('ysf_user');
		$del_user = $user_db->where('id=%s', $uid)->select();

		if ($del_user)
		{
			$dev_db = M('ysf_device');
			$dev_count = $dev_db->where('user_id=%s', $uid)->count();
			if ($dev_count > 0)
				$this->error('请先删除此管理员下的设备');

			$mt_db = M('ysf_task_mt');
			$dev_count = $dev_db->where('user_id=%s', $uid)->count();
			if ($dev_count > 0)
				$this->error('请先删除此管理员下的手动任务列表');
			
			$at_db = M('ysf_task_at');
			$dev_count = $dev_db->where('user_id=%s', $uid)->count();
			if ($dev_count > 0)
				$this->error('请先删除此管理员下的自动任务列表');
			
			$user_db->where('id=%s', $uid)->delete();				
			LogLogic::addLog('删除管理员'.$del_user[0]['name']);
			
		
			$this->success('删除管理员成功');
		} else {
			$this->error('管理员未找到');
		}
	}

    // 系统操作日志
    /**
     *
     */
	public function sysoplog($op_id = '')
	{
		$page = I('get.page', C('PAGER'));
		
		$search_uname = I('post.search_uname');
	    
		if ($op_id == '') {
	    	$map['_string'] = HaffmanEvent::getQueryString('op_id');
		} else {
			$map['op_id'] = $op_id;
		}
		
		if ($search_uname)
			$map['op_uname'] = $search_uname;
	    
	   	$User = D('Sysop_log');
	    $count = $User->where($map)->count(); // 查询满足要求的总记录数
	    
		if ($count != 0) {
	  		$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
	    	$pager_bar = $Page->show();
	    	$limit = $Page->firstRow . ',' . $Page->listRows;
	    	$list = $User->where($map)->order('log_time desc')->limit($limit)->select();
	    }
	    
	    $this->assign('listname', '操作日志');
	    $this->assign('pager_bar', $pager_bar);
	    $this->assign('list', $list);
	    $this->assign('total_count', $count);
	    $this->display('sysoplog');
	}
	
	// 行业管理
	/**
	 *
	 */
	 public function category()
	 {
	 	$page = I('get.page', C('PAGER'));
	 
	 	$query_string = 'select count(*) from `'
	 			.C(DB_NAME).'`.`wrt_category`'; 	
	  	$Model = new Model();	   	
	   	
		$data = $Model->query($query_string); // 查询满足要求的总记录数
		$count = intval($data[0]['count(*)']);
		
	 	if ($count != 0) {
		 	$Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
		 	$pager_bar = $Page->show();
		 	$limit = $Page->firstRow . ',' . $Page->listRows;
		 	$query_string = 'select * from `'
		 			.C(DB_NAME).'`.`wrt_category`'
		 			.' limit '.$limit;
		 	$list = $Model->query($query_string);
	 	}
	 	 
	 	$this->assign('action', '行业管理');
	 	$this->assign('pager_bar', $pager_bar);
	 	$this->assign('list', $list);
	 	$this->display('category');
	}
	
	public function categoryAdd()
	{
		$this->assign('action_name', 'add');
		$this->assign('action', '新增行业');
		$this->display('categoryedit');  
	}
	
	public function categoryAddHandle()
	{
		$category = I('post.category');

		if ($category == '')
		    $this->error('行业名称未输入');
		
	    $category_db = M('Category');
		$category_chk = $category_db->where("category='%s'", $category)->select();
		if ($category_chk) 
			$this->error('行业名称已存在');
		
		$category_id = $category_db->max('category_id');
		
		$data['category'] = $category;
		$data['category_id'] = $category_id + 1;
		$category_db->add($data);
		
		$this->success('新增行业成功', U('Admin/System/category'));
		LogLogic::addLog('新增行业:'.$category);
	}

	public function categoryEdit($id = -1, $category = '')
	{
		$this->assign('id', $id);
		$this->assign('category', $category);
		$this->assign('action', '编辑行业');
		$this->display('categoryedit');  
	}
	
	public function categoryEditHandle()
	{
		$category = I('post.category');
		$id = I('post.id');

		if ($id == -1)
			$this->error('行业索引错误');

		if ($category == '')
		    $this->error('行业名称未输入');
		
	    $category_db = M('Category');
		$category_chk = $category_db->where("category='%s' and id !=%d", $category, $id)->select();
		if ($category_chk) 
			$this->error('行业名称已存在');
		
		$category_old = $category_db->where("id = %d", $id)->getField('category');
		
		$data['id'] = $id;
		$data['category'] = $category;
		$category_db->save($data);
		
		$this->success('编辑行业名称成功', U('Admin/System/category'));
		LogLogic::addLog('编辑行业:'.$category_old.' 改为 '.$category);
	}
	
	//设备自动升级
	public function devupdate()
	{
	 	$devupdate_db = M('version');

		$data ['version'] = $devupdate_db->getField('version');
		$data ['release_time'] = $devupdate_db->getField('release_time');
		$data ['auto_update_enable'] = $devupdate_db->getField('auto_update_enable');
		$data ['start_hour'] = $devupdate_db->getField('start_hour');
		$data ['end_hour'] = $devupdate_db->getField('end_hour');
		$data ['upgrade_limit'] = $devupdate_db->getField('upgrade_limit');
		$data ['x86_version'] = $devupdate_db->getField('x86_version');
		
	 	$this->assign('action', '设备自动升级');
	 	$this->assign('data', $data);		
	 	$this->display('devupdate');
	}
	
	public function devupdateSave()
	{
		$data ['version'] = I('post.version');
		$data ['release_time'] = I('post.release_time');
		$data ['auto_update_enable'] = I('post.auto_update_enable');
		$data ['start_hour'] = I('post.start_hour');
		$data ['end_hour'] = I('post.end_hour');
		$data ['upgrade_limit'] = I('post.upgrade_limit');
		$data ['x86_version'] = I('post.x86_version');
		
	    $db = M('version');		
		$db->save($data);
		
		$this->success('保存升级配置成功', U('Admin/System/devupdate'));
		LogLogic::addLog('自动升级配置保存成功');
	}
	
	//设备发布版本页面
	public function devUpdateFile($dev_type = '')
	{	
		if ($dev_type == 'x86') {
			$this->assign('action', 'x86发布版本');
		} else {
			$this->assign('action', '路由器发布版本');
		}
		
		$this->assign('dev_type', $dev_type);

		$this->display('devupdatefile');
	}
	
	//设备发布版本提交
	public function devUpdateCommit()
	{
		$dev_type = I('post.dev_type');
		$version = I('post.version');
		
		$uploaddir = "/var/www/html/firmware/";
		
		$version_arr = explode('.', $version);
		if ($dev_type == 'x86') {
			if($version_arr[0]<10 || $version_arr[0] > 19){
				LogLogic::addLog('上传新版本'.$version.'失败，版本号需在10.0.0.0-10.255.255.255之间,您的版本号输入：'.$version);
				$this->error('上传失败,版本号需在10.0.0.0-10.255.255.255之间');
			}
			
			$uploadfile = $uploaddir . $version . '.tar.gz';
		} else {
			if($version_arr[0]>=10 || $version_arr[0] < 1){
				LogLogic::addLog('上传新版本'.$version.'失败，版本号需在1.0.0.0-9.255.255.255之间,您的版本号输入：'.$version);
				$this->error('上传失败,版本号需在1.0.0.0-9.255.255.255之间');
			}
			
			$uploadfile = $uploaddir . $version . '.tar.gz';
		}
		
		if (move_uploaded_file($_FILES['file1']['tmp_name'], $uploadfile)) {
			LogLogic::addLog('上传新版本,版本号：'.$version);
			
			$db = M('version');
			
			$old = $db->select();
			
			$data = $old[0];
			
			if ($dev_type == 'x86') {
				$data ['x86_version'] = $version;
			} else {
				$data ['version'] = $version;
			}
			
			$data ['release_time'] = date('Y-m-d H:i:s');
			
			$db->where(1)->delete();	

			$db->add($data);
			
			$this->success('上传成功', U('Admin/System/devupdate'));
		} else {
			LogLogic::addLog('上传失败,错误码：'.$_FILES['file1']['error']);
			$this->error('上传失败,错误码：'.$_FILES['file1']['error']);
		}
	}
	
	//系统监控
	public function monitor()
	{
        $res = exec("pgrep cnm_server", $output, $ret);

		if ($res)
			$this->assign('cnm_server', 1);
		else
			$this->assign('cnm_server', 0);

        $res = exec("pgrep appmonitor");
		
		if ($res)
			$this->assign('appmonitor', 1);
		else
			$this->assign('appmonitor', 0);
	
	 	$this->display('monitor');
	}

	public function monitor_cnm_server_restart()
	{
        $res = exec("service cnm_server restart");
	   
		if ($res)
			$this->success('重启cnm_server失败', U('Admin/System/monitor'));
		else
			$this->success('重启cnm_server成功', U('Admin/System/monitor'));

		LogLogic::addLog('执行cnm_server重启操作');
	}
	
	public function monitor_appmonitor_restart()
	{
        $res = exec("service appmonitor restart");

		if ($res)
			$this->success('重启appmonitor失败', U('Admin/System/monitor'));
		else
			$this->success('重启appmonitor成功', U('Admin/System/monitor'));
		
		LogLogic::addLog('执行appmonitor重启操作');
	}
	
	
	public function adminExportxls()
	{
		vendor("PHPExcel.PHPExcel");
		vendor('PHPExcel.PHPExcel.IOFactory');
		
		$map['_string'] = HaffmanEvent::getQueryString('id');
		
		$User = D('User');
		$data = $User->where($map)->select(); 

		$category = D('Category')->select();
		
		$objPHPExcel = new \PHPExcel();
		$objPHPExcel->getProperties()->setTitle("export")->setDescription("none");
		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '管理员') ;
		$objPHPExcel->getActiveSheet()->mergeCells('A1:G1');
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
		//$objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	
	
	
		// Field names in the first row
		$fields = array('name'=>'用户名','privilege_level'=>'权限级别','area'=>'区域','user_name'=>'姓名','create_time'=>'创建时间','last_login_time'=>'最后一次登陆时间');
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
				if($key1=='privilege_level'){
					if($v['type']==1){
						if($v['privilege_level']){if($v['privilege_level']==1)
						{
							$tmp = '超级管理员';
						}
						else{
							$tmp = $v['privilege_level'].'级系统管理员';
						}
						}
					}else
					{
						$tmp = '设备管理员';
					}
				}else if($key1 == 'last_login_time')
				{
					if(!$v['last_login_time']){
						$tmp = "未登陆过系统";
					}else{
						$tmp = $v['last_login_time'];
					}
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
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(50);
		$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
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
		//发送标题强制用户下载文件
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="user_list-'.uniqid().'-'.date('dMy').'.xls"');
		header('Cache-Control: max-age=0');
		$objWriter->save('php://output');
	}
	
	
    /**
     *
     */
    public function updateindex()
    {
    	$filename = "/usr/private/Firmware_version";
    	$handle = fopen($filename, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
    	 
    	//通过filesize获得文件大小，将整个文件一下子读到一个字符串中
    	$curVersion = fread($handle, filesize ($filename));
    	fclose($handle);
    	
    	$uploaddir = "/var/www/html/download/";
    	$curName = 'CNMFirmware.bin';
    	
    	//dump($_FILES['file1']['tmp_name']);
    	//dump($uploadfile);
        
    	if ( $_FILES['file1']['tmp_name'] )
    	{
        	$uploadfile = $uploaddir . $curName . '.new';
        	//dump($uploadfile);
        	if (move_uploaded_file($_FILES['file1']['tmp_name'], $uploadfile)) {
    	    	//LogLogic::addLog('上传新版本,版本号：'.$version);
    	    } else {
    	    	//LogLogic::addLog('上传失败,错误码：'.$_FILES['file1']['error']);
    	    	$this->error('上传失败,错误码：'.$_FILES['file1']['error']);
    	    }

    	    $res = exec("echo 1 > /var/www/html/download/cnm_server_flag");
  
    	    LogLogic::addLog('cnm_server升级');
    	}

    	
    	$this->assign ( "curVersion", $curVersion );
    	$this->display ();
    }
	
	public function clearCache(){  //删除服务器端缓存
		import('@.ORG.Io.Dir');
		$obj = new Dir;
		$url = "./Runtime";
		$obj->delDir($url);
	}

	//email setting page
	public function email()
	{
	 	$db = M('ysf_email');

		$data['email_server_address'] = $db->getField('email_server_address');
		$data['email_server_port'] = $db->getField('email_server_port');
		$data['email_sender'] = $db->getField('email_sender');
		$data['email_sender_show'] = $db->getField('email_sender_show');
		//$data['email_auth'] = $db->getField('email_auth');
		//$data['email_auth_user'] = $db->getField('email_auth_user');
		$data['email_auth_passwd'] = $db->getField('email_auth_passwd');
		$data['email_subject'] = $db->getField('email_subject');
		
		$email_auth_receiver_arr = explode(";", $db->getField('email_auth_receiver'));
		foreach($email_auth_receiver_arr as $one)
		{
			$one = trim($one);
			$email_auth_receiver .= $one . PHP_EOL;
		}
		$data['email_auth_receiver'] = $email_auth_receiver;
		
		
	 	$this->assign('action', '邮箱配置');
	 	$this->assign('data', $data);		
	 	$this->display('email');
	}
	
	public function emailUpdate()
	{
		$data['email_server_address'] = I('post.email_server_address');
		$data['email_server_port'] = I('post.email_server_port');
		$data['email_sender'] = I('post.email_sender');
		$data['email_sender_show'] = I('post.email_sender_show');
		//$data['email_auth'] = I('post.email_auth');
		//$data['email_auth_user'] = I('post.email_auth_user');
		$data['email_auth_passwd'] = I('post.email_auth_passwd');
		$data['email_subject'] = I('post.email_subject');

		$email_auth_receiver_arr = explode("\n", I('post.email_auth_receiver'));
		foreach($email_auth_receiver_arr as $one)
		{
			$one = trim($one);
			if ($one == "")
				break;
			if ($email_auth_receiver)
				$email_auth_receiver .=  ';' . $one;
			else
				$email_auth_receiver .= $one;
		}
		
		$data['email_auth_receiver'] = $email_auth_receiver;
		
		$db = M('ysf_email');
		$count = $db->count();
		
		if (!$count)
			$db->add($data);
		else		
			$db->where('1')->save($data);
		
		$this->success('保存邮箱配置成功', U('Admin/System/email'));
		LogLogic::addLog('邮箱配置保存成功');
	}
	
}
