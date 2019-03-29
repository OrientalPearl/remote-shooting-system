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
 * Class ShootingTaskController
 * @package Admin\Controller
 */
class ShootingTaskController extends AdminBaseController
{
	public function index()
	{
		$this->redirect('Admin/ShootingTask/tasklist');
	}
	
	public function taskSearch($search_serial='', $search_type='', $search_user_id)
    {
        $_SESSION['search_field'] = array(
			'serial'=>$search_serial,
			'type'=>$search_type,
            'user_id'=>$search_user_id);

        self::task();
    }
	
    public function task()
    {    	
        $page = I('get.page', C('PAGER'));
        
		$search_serial = $_SESSION['search_field']['serial'];
		$search_type = $_SESSION['search_field']['type'];
		$search_user_id = $_SESSION['search_field']['user_id'];
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
        if ($search_serial != '')
        	$map['serial'] = $search_serial;
		
        if ($search_type != '')
			$map['type'] = $search_type;
		
        if ($search_user_id != '')
			$map['user_id'] = $search_user_id;
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		
        $mt = D('ysf_task');
        $count = $mt->where($map)->count(); // 查询满足要求的总记录数

        if ($count != 0) {
            $Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
            $pager_bar = $Page->show();
            $limit = $Page->firstRow . ',' . $Page->listRows;
            $list = $mt->where($map)->limit($limit)->select();           
        }
        
		
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
        $this->assign('page', $page);
        $this->assign('listname', '拍摄任务');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $list);
        $this->assign('total_count', $count);
		$this->assign('list_user', $list_user);
		$this->assign('user', $user);
        $this->display('tasklist');
		

		$_SESSION['search_field']['serial'] = '';
		$_SESSION['search_field']['type'] = '';
        $_SESSION['search_field']['user_id'] = '';
    }

	public function taskHandle()
    {    	
        if (I('post.delAll') == 1) {
            $post_ids = I('post.uid_chk_box');
			
            $res_info = '';
            foreach ($post_ids as $post_id) {
				self::taskDel($post_id);
            }
            $this->success('批量删除拍摄任务');
        }

        if (I('post.taskAdd') == 1) {
            $this->redirect('Admin/ShootingTask/taskAdd');
        }
        
        if (I('post.search') == 1) {
        	$this->redirect('Admin/ShootingTask/task',
        		array(				
        			'search_name' => I('post.search_name'),
        	));
        }
    }
	
	public function taskAdd()
	{
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		$list_device = M('ysf_device')->where($map)->select();
		
		foreach ($list_device as $device)
		{
			$device_aperture[$device['serial']] = $device['aperture_range'];
			$device_shutter[$device['serial']] = $device['shutter_range'];
			$device_iso[$device['serial']] = $device['iso_range'];
		}
	
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		
		$this->assign('action_name', 'addTask');
		$this->assign('action', '新增拍摄任务');
		$this->assign('list_device', $list_device);
		$this->assign('list_user', $list_user);
		$this->assign('list_json_aperture', json_encode($device_aperture));
		$this->assign('list_json_shutter', json_encode($device_shutter));
		$this->assign('list_json_iso', json_encode($device_iso));
		 $this->assign('user', $user);
		$this->display('taskadd');
	}
	
	public function taskAddHandle()
	{
		$serial = I('post.serial');
		//$user_id = I('post.user_id');

		
		$type = I('post.type');
		
		if ($type == 1) //mannual
		{
			$aperture = I('post.aperture');
			$shutter = I('post.shutter');		
			$iso = I('post.iso');	
		}
		else //auto
		{
			$aperture = "";
			$shutter = "";		
			$iso = "";	
		}
		
		$shooting_time = I('post.shooting_time');	
		$shooting_number = I('post.shooting_number');	
		$shooting_interval = I('post.shooting_interval');	
		
		
		//dump($shooting_time);
		
		$device_db = M('ysf_device');
		$user_name = $device_db->where('serial=\'%s\'', $serial)->getField('user_name');
		$user_id = $device_db->where('serial=\'%s\'', $serial)->getField('user_id');
		
		$data = array (
				'serial' => $serial,
				'user_id' => $user_id,
				'user_name' => $user_name,
				'type' => $type,
				'aperture' => $aperture,
				'shutter' => $shutter,
				'iso' => $iso,
				'shooting_time' => $shooting_time,
				'shooting_number' => $shooting_number,
				'shooting_interval' => $shooting_interval
		);
			
		$mt_db = M('ysf_task');
		$mt_db->add($data);
		
		
		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		
		LogLogic::addLog('新增'.'拍摄任务 '.$serial);
		$this->success('新增拍摄任务成功', U('Admin/ShootingTask/task'));
	}
	
	public function taskEdit($id = -1)
	{
		if ($id == -1)
			$this->error('拍摄任务未找到');
		
		$mt_info = M('ysf_task')->where('id=%s', $id)->select();
		
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		$list_device = M('ysf_device')->where($map)->select();
		
		foreach ($list_device as $device)
		{
			$device_aperture[$device['serial']] = $device['aperture_range'];
			$device_shutter[$device['serial']] = $device['shutter_range'];
			$device_iso[$device['serial']] = $device['iso_range'];
		}
	
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		
		$user = $_SESSION[C('USER_AUTH_INFO')];
		
		$this->assign('info', $mt_info[0]);
		$this->assign('action_name', 'editTask');
		$this->assign('list_device', $list_device);
		$this->assign('list_user', $list_user);
		$this->assign('action', '编辑拍摄任务');
		$this->assign('list_json_aperture', json_encode($device_aperture));
		$this->assign('list_json_shutter', json_encode($device_shutter));
		$this->assign('list_json_iso', json_encode($device_iso));
		$this->assign('user', $user);
		$this->display('taskadd');
	}
	
	public function taskEditHandle()
	{		
		$id = I('post.id');
		
	    $serial = I('post.serial');
		//$user_id = I('post.user_id');
		$type = I('post.type');
		
		if ($type == 1) //mannual
		{
			$aperture = I('post.aperture');
			$shutter = I('post.shutter');		
			$iso = I('post.iso');	
		}
		else //auto
		{
			$aperture = "";
			$shutter = "";		
			$iso = "";	
		}
		
		$shooting_time = I('post.shooting_time');	
		$shooting_number = I('post.shooting_number');	
		$shooting_interval = I('post.shooting_interval');	
		
		//$user_db = M('ysf_user');
		//$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
	
		$mt_db = M('ysf_task');

		$mt_db = $mt_db->where('id=\'%s\'', $id)->select();
		if (!$mt_db) {
			$this->error('拍摄任务未找到');
		}
		
		$data = array (
				'type' => $type,
				'aperture' => $aperture,
				'shutter' => $shutter,
				'iso' => $iso,
				'shooting_time' => $shooting_time ,
				'shooting_number' => $shooting_number,
				'shooting_interval' => $shooting_interval
		);
		
		$mt_db_save = M('ysf_task');
		$mt_db_save->where('id=\'%s\'', $id)->save($data);
		
		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		LogLogic::addLog('修改'.'拍摄任务 '.$serial);
		$this->success('修改成功', U('Admin/ShootingTask/task'));
	}
	
	public function taskDel($id = -1, $serial)
	{
		if ($id == -1)
			$this->error('拍摄任务未找到');
		
		$mt_db = M('ysf_task');
		$del_mt = $mt_db->where('id=%s', $id)->select();

		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		
		if ($del_mt)
		{			
			$mt_db->where('id=%s', $id)->delete();				
			LogLogic::addLog('删除拍摄任务'.$del_mt[0]['serial']);
			
		
			$this->success('删除拍摄任务成功');
		} else {
			$this->error('拍摄任务未找到');
		}
	}
	
	
	
	

	
    public function manual()
    {    	
        $page = I('get.page', C('PAGER'));
        
		$search_serial = $_SESSION['search_field']['serial'];
		$search_user_id = $_SESSION['search_field']['user_id'];
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
        if ($search_serial != '')
        	$map['serial'] = $search_serial;
		
        if ($search_user_id != '')
			$map['user_id'] = $search_user_id;
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		
		$map['type'] = 1;
		
        $mt = D('ysf_task');
        $count = $mt->where($map)->count(); // 查询满足要求的总记录数

        if ($count != 0) {
            $Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
            $pager_bar = $Page->show();
            $limit = $Page->firstRow . ',' . $Page->listRows;
            $list = $mt->where($map)->limit($limit)->select();           
        }
        
		
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
        $this->assign('page', $page);
        $this->assign('listname', '手动任务');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $list);
        $this->assign('total_count', $count);
		$this->assign('list_user', $list_user);
        $this->display('manuallist');
		
		
		$_SESSION['search_field']['serial'] = '';
        $_SESSION['search_field']['user_id'] = '';
    }
	
    public function manualHandle()
    {    	
        if (I('post.delAll') == 1) {
            $post_ids = I('post.uid_chk_box');
			
            $res_info = '';
            foreach ($post_ids as $post_id) {
				self::manualDel($post_id);
            }
            $this->success('批量删除手动任务');
        }

        if (I('post.manualAdd') == 1) {
            $this->redirect('Admin/ShootingTask/manualAdd');
        }
        
        if (I('post.search') == 1) {
        	$this->redirect('Admin/ShootingTask/manual',
        		array(				
        			'search_name' => I('post.search_name'),
        	));
        }
    }
	
	public function manualAdd()
	{
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		$list_device = M('ysf_device')->where($map)->select();
		
		foreach ($list_device as $device)
		{
			$device_aperture[$device['serial']] = $device['aperture_range'];
			$device_shutter[$device['serial']] = $device['shutter_range'];
			$device_iso[$device['serial']] = $device['iso_range'];
		}
	
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		$this->assign('action_name', 'addManual');
		$this->assign('action', '新增手动任务');
		$this->assign('list_device', $list_device);
		$this->assign('list_user', $list_user);
		$this->assign('list_json_aperture', json_encode($device_aperture));
		$this->assign('list_json_shutter', json_encode($device_shutter));
		$this->assign('list_json_iso', json_encode($device_iso));
		$this->display('manualadd');
	}
	
	public function manualAddHandle()
	{
		$serial = I('post.serial');
		$user_id = I('post.user_id');

		$aperture = I('post.aperture');
		$shutter = I('post.shutter');		
		$iso = I('post.iso');	
		
		$shooting_time = I('post.shooting_time');	
		$shooting_number = I('post.shooting_number');	
		$shooting_interval = I('post.shooting_interval');	
		
		
		//dump($shooting_time);
		
		$user_db = M('ysf_user');
		$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
		
		$data = array (
				'serial' => $serial,
				'user_id' => $user_id,
				'user_name' => $user_name,
				'type' => 1,
				'aperture' => $aperture,
				'shutter' => $shutter,
				'iso' => $iso,
				'shooting_time' => $shooting_time,
				'shooting_number' => $shooting_number,
				'shooting_interval' => $shooting_interval
		);
			
		$mt_db = M('ysf_task');
		$mt_db->add($data);
		
		
		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		
		LogLogic::addLog('新增'.'手动任务 '.$serial);
		$this->success('新增手动任务成功', U('Admin/ShootingTask/manual'));
	}
	
	public function manualEdit($id = -1)
	{
		if ($id == -1)
			$this->error('手动任务未找到');
		
		$mt_info = M('ysf_task')->where('id=%s', $id)->select();
		
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		$list_device = M('ysf_device')->where($map)->select();
		
		foreach ($list_device as $device)
		{
			$device_aperture[$device['serial']] = $device['aperture_range'];
			$device_shutter[$device['serial']] = $device['shutter_range'];
			$device_iso[$device['serial']] = $device['iso_range'];
		}
	
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		
		
		$this->assign('info', $mt_info[0]);
		$this->assign('action_name', 'editManual');
		$this->assign('list_device', $list_device);
		$this->assign('list_user', $list_user);
		$this->assign('action', '编辑手动任务');
		$this->assign('list_json_aperture', json_encode($device_aperture));
		$this->assign('list_json_shutter', json_encode($device_shutter));
		$this->assign('list_json_iso', json_encode($device_iso));
		$this->display('manualadd');
	}
	
	public function manualEditHandle()
	{		
		$id = I('post.id');
		
	    $serial = I('post.serial');
		$user_id = I('post.user_id');

		$aperture = I('post.aperture');
		$shutter = I('post.shutter');		
		$iso = I('post.iso');	
		
		$shooting_time = I('post.shooting_time');	
		$shooting_number = I('post.shooting_number');	
		$shooting_interval = I('post.shooting_interval');	
		
		$user_db = M('ysf_user');
		$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
		
		$mt_db = M('ysf_task');

		$mt_db = $mt_db->where('id=\'%s\'', $id)->select();
		if (!$mt_db) {
			$this->error('手动任务未找到');
		}
		
		$data = array (
				'aperture' => $aperture,
				'shutter' => $shutter,
				'iso' => $iso,
				'shooting_time' => $shooting_time ,
				'shooting_number' => $shooting_number,
				'shooting_interval' => $shooting_interval
		);
		
		$mt_db_save = M('ysf_task');
		$mt_db_save->where('id=\'%s\'', $id)->save($data);
		
		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		LogLogic::addLog('修改'.'手动任务 '.$serial);
		$this->success('修改成功', U('Admin/ShootingTask/manual'));
	}
	
	public function manualDel($id = -1, $serial)
	{
		if ($id == -1)
			$this->error('手动任务未找到');
		
		$mt_db = M('ysf_task');
		$del_mt = $mt_db->where('id=%s', $id)->select();

		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		
		if ($del_mt)
		{			
			$mt_db->where('id=%s', $id)->delete();				
			LogLogic::addLog('删除手动任务'.$del_mt[0]['serial']);
			
		
			$this->success('删除手动任务成功');
		} else {
			$this->error('手动任务未找到');
		}
	}
	
	
	
	
	
	
	public function autoSearch($search_serial='', $search_user_id)
    {
        $_SESSION['search_field'] = array(
			'serial'=>$search_serial,
            'user_id'=>$search_user_id);

        self::auto();
    }
	
    public function auto()
    {    	
        $page = I('get.page', C('PAGER'));
        
		$search_serial = $_SESSION['search_field']['serial'];
		$search_user_id = $_SESSION['search_field']['user_id'];
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
        if ($search_serial != '')
        	$map['serial'] = $search_serial;
		
        if ($search_user_id != '')
			$map['user_id'] = $search_user_id;
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		
		$map['type'] = 0;
			
        $mt = D('ysf_task');
        $count = $mt->where($map)->count(); // 查询满足要求的总记录数

        if ($count != 0) {
            $Page = new GreenPage($count, $page); // 实例化分页类 传入总记录数
            $pager_bar = $Page->show();
            $limit = $Page->firstRow . ',' . $Page->listRows;
            $list = $mt->where($map)->limit($limit)->select();           
        }
        
		
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
        $this->assign('page', $page);
        $this->assign('listname', '自动任务');
        $this->assign('pager_bar', $pager_bar);
        $this->assign('list', $list);
        $this->assign('total_count', $count);
		$this->assign('list_user', $list_user);
        $this->display('autolist');
		
		
		$_SESSION['search_field']['serial'] = '';
        $_SESSION['search_field']['user_id'] = '';
    }
	
    public function autoHandle()
    {    	
        if (I('post.delAll') == 1) {
            $post_ids = I('post.uid_chk_box');
			
            $res_info = '';
            foreach ($post_ids as $post_id) {
				self::autoDel($post_id);
            }
            $this->success('批量删除自动任务');
        }

        if (I('post.autoAdd') == 1) {
            $this->redirect('Admin/ShootingTask/autoAdd');
        }
        
        if (I('post.search') == 1) {
        	$this->redirect('Admin/ShootingTask/auto',
        		array(				
        			'search_name' => I('post.search_name'),
        	));
        }
    }
	
	public function autoAdd()
	{
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		$list_device = M('ysf_device')->where($map)->select();
		
		foreach ($list_device as $device)
		{
			$device_aperture[$device['serial']] = $device['aperture_range'];
			$device_shutter[$device['serial']] = $device['shutter_range'];
			$device_iso[$device['serial']] = $device['iso_range'];
		}
	
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		$this->assign('action_name', 'addAuto');
		$this->assign('action', '新增自动任务');
		$this->assign('list_device', $list_device);
		$this->assign('list_user', $list_user);
		$this->assign('list_json_aperture', json_encode($device_aperture));
		$this->assign('list_json_shutter', json_encode($device_shutter));
		$this->assign('list_json_iso', json_encode($device_iso));
		$this->display('autoadd');
	}
	
	public function autoAddHandle()
	{
		$serial = I('post.serial');
		$user_id = I('post.user_id');

		$type = 0;
		

		$aperture = I('post.aperture');
		$shutter = I('post.shutter');		
		$iso = I('post.iso');	

		
		$aperture = I('post.aperture');
		$shutter = I('post.shutter');		
		$iso = I('post.iso');	
		
		$shooting_time = I('post.shooting_time');	
		$shooting_number = I('post.shooting_number');	
		$shooting_interval = I('post.shooting_interval');	
		
		
		//dump($shooting_time);
		
		$user_db = M('ysf_user');
		$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
		
		$data = array (
				'serial' => $serial,
				'user_id' => $user_id,
				'user_name' => $user_name,
				'type' => 0,
				'aperture' => $aperture,
				'shutter' => $shutter,
				'iso' => $iso,
				'shooting_time' => $shooting_time,
				'shooting_number' => $shooting_number,
				'shooting_interval' => $shooting_interval
		);
			
		$mt_db = M('ysf_task');
		$mt_db->add($data);
		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		LogLogic::addLog('新增'.'自动任务 '.$serial);
		$this->success('新增自动任务成功', U('Admin/ShootingTask/auto'));
	}
	
	public function autoEdit($id = -1)
	{
		if ($id == -1)
			$this->error('自动任务未找到');
		
		$mt_info = M('ysf_task')->where('id=%s', $id)->select();
		
		$cur_user_info = $_SESSION[C('USER_AUTH_INFO')];
		
		if ($cur_user_info['type'] != 1)
			$map['user_id'] = $cur_user_info['id'];
		$list_device = M('ysf_device')->where($map)->select();
		
		foreach ($list_device as $device)
		{
			$device_aperture[$device['serial']] = $device['aperture_range'];
			$device_shutter[$device['serial']] = $device['shutter_range'];
			$device_iso[$device['serial']] = $device['iso_range'];
		}
	
		if ($cur_user_info['type'] != 1)
			$map_user['id'] = $cur_user_info['id'];
		$list_user = M('ysf_user')->where($map_user)->select();
		
		
		
		$this->assign('info', $mt_info[0]);
		$this->assign('action_name', 'editAuto');
		$this->assign('list_device', $list_device);
		$this->assign('list_user', $list_user);
		$this->assign('action', '编辑自动任务');
		$this->assign('list_json_aperture', json_encode($device_aperture));
		$this->assign('list_json_shutter', json_encode($device_shutter));
		$this->assign('list_json_iso', json_encode($device_iso));
		$this->display('autoadd');
	}
	
	public function autoEditHandle()
	{		
		$id = I('post.id');
		
	    $serial = I('post.serial');
		$user_id = I('post.user_id');
		
		$type = 0;	
		

		$aperture = I('post.aperture');
		$shutter = I('post.shutter');		
		$iso = I('post.iso');	

		
		$shooting_time = I('post.shooting_time');	
		$shooting_number = I('post.shooting_number');	
		$shooting_interval = I('post.shooting_interval');	
		
		$user_db = M('ysf_user');
		$user_name = $user_db->where('id=\'%s\'', $user_id)->getField('name');
		
		$mt_db = M('ysf_task');

		$mt_db = $mt_db->where('id=\'%s\'', $id)->select();
		if (!$mt_db) {
			$this->error('自动任务未找到');
		}
		
		$data = array (
				//'type' => $type,
				//'aperture' => $aperture,
				//'shutter' => $shutter,
				//'iso' => $iso,
				'shooting_time' => $shooting_time ,
				'shooting_number' => $shooting_number,
				'shooting_interval' => $shooting_interval
		);
		
		$mt_db_save = M('ysf_task');
		$mt_db_save->where('id=\'%s\'', $id)->save($data);
		
		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		
		LogLogic::addLog('修改'.'自动任务 '.$serial);
		$this->success('修改成功', U('Admin/ShootingTask/auto'));
	}
	
	public function autoDel($id = -1, $serial)
	{
		if ($id == -1)
			$this->error('自动任务未找到');
		
		$mt_db = M('ysf_task');
		$del_mt = $mt_db->where('id=%s', $id)->select();

		
		
		$dev_db = M('ysf_device');
		$old_dev = $dev_db->where('serial=\'%s\'', $serial)->select();
		$data = array (
					'serial' => $serial,
					'tasks_sync_seq' => $old_dev[0]['tasks_sync_seq'] + 1
					);
		$dev_db->where('serial=\'%s\'', $serial)->save($data);
		
		
		
		if ($del_mt)
		{			
			$mt_db->where('id=%s', $id)->delete();				
			LogLogic::addLog('删除自动任务'.$del_mt[0]['serial']);
			
		
			$this->success('删除自动任务成功');
		} else {
			$this->error('自动任务未找到');
		}
	}
	
	
	
	
}
