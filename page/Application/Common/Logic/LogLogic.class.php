<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-10
 */

namespace Common\Logic;

use Think\Model\RelationModel;

class LogLogic extends RelationModel
{

    public function countAll($where){
        $Log = D('Sysop_log');

        return $Log->where($where)->count();
    }
    
    public function addLog($message = '')
    {
        $Log = D('Sysop_log');
        
        $user = $_SESSION[C('USER_AUTH_INFO')];

        $log_data['content']=$message;
        $log_data['op_id']=$user['id'];
        $log_data['op_uname']=$user['name'];
        $log_data['user_ip']=get_user_ip();
        $log_data['haffman_key']=$user['haffman_key'];
        $log_data['log_time']=date('Y-m-d H:i:s');
        
        $insert_res = $Log->data($log_data)->add();
        return $insert_res;
    }

    public function getList($limit=0,$where=array(),$relation = true)
    {
        $Log = D('Sysop_log');
        $log_list = $Log->where($where)->limit($limit)->select();

        return $log_list;
    }
}