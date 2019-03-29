<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-10
 */

namespace Admin\Controller;

use Common\Controller\BaseController;
use Common\Logic\LogLogic;
use Think\Log;

/**
 * Class AdminBaseController
 * @package Admin\Controller
 */
class AdminBaseController extends BaseController
{
	/**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->_initialize();

        $this->_currentPostion();

        $this->_currentUser();
    }

    /**
     *
     */
    protected function _initialize()
    {
    	if (! isset($_SESSION[C('USER_AUTH_KEY')]))
    	{    		
    		redirect(U("Admin/Login/index"));
    	}
    	else 
    	{    	
    		$time = time();

    		if (($time - $_SESSION['logtime']) > C('USER_AUTH_TIMEOUT'))
    		{
    			session_unset();
    			session_destroy();
    			redirect(U("Admin/Login/index"));
    		}
    		else
    		{
    			$_SESSION['logtime'] = $time;
    		}
    	}
    }

    /**
     *
     */
    private function _currentPostion()
    {
        $cache = C('admin_big_menu');
        foreach ($cache as $big_url => $big_name) {
            if (strtolower($big_url) == strtolower(CONTROLLER_NAME)) {
                $module = $big_name['name'];
                $module_url = U("Admin/" . "$big_url" . '/index');
            } else {
            }
        }

        $cache = C('admin_sub_menu');
        foreach ($cache as $big_url => $big_name) {
            if (strtolower($big_url) == strtolower(CONTROLLER_NAME)) {
                foreach ($big_name as $sub_url => $sub_name) {
                    $sub_true_url = explode('/', $sub_url);
                    if (!strcasecmp($sub_true_url [1], strtolower(ACTION_NAME))) {
                        $action = $sub_name['name'];
                        $action_url = U("Admin/" . "$sub_url");
                    }
                }
            }
        }
        
        $this->assign('module', $module);
        $this->assign('action', $action);
        $this->assign('module_url', $module_url);
        $this->assign('action_url', $action_url);
    }


    /**
     *
     */
    public function isSuperAdmin()
    {
        $uapri = ( int )$_SESSION [C('USER_AUTH_PRI')];
        if ($uapri == 1)
        	return true;
        else 
        	return false;
    }
}