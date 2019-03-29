<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-13
 */

namespace Admin\Controller;

use Think\Storage;

/**
 * Class IndexController
 * @package Admin\Controller
 */
class IndexController extends AdminBaseController
{
    /**
     * 首页基本信息
     */
    public function index()
    {
    	$this->display();
    }    
    
    //who get this method???
    public function checktodo()
    {
    	exit;
    }
}