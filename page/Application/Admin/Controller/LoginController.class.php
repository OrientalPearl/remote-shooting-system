<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-10
 */

namespace Admin\Controller;

use Common\Controller\BaseController;
use Common\Event\UserEvent;

/**
 * Class LoginController
 * @package Admin\Controller
 */
class LoginController extends BaseController
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function vertify()
    {

        $config = array(
            'fontSize' => 20,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => true,
        );


        $Verify = new \Think\Verify($config);
        $Verify->entry();
    }

    /**
     *
     */
    public function index()
    {
        $this->display();
    }

    /**
     *
     */
    public function login()
    {        
      //$this->vertifyHandle();

        $map = array();
        $map['user_login'] = I('post.username');
        $map['user_pass'] = I('post.password');
        
        $UserEvent = new UserEvent();
        //$UserEvent->initDatabase($map['user_login'] == 'admin');

        $loginRes = $UserEvent->auth($map);
        $this->json2Response($loginRes);

    }

    public function vertifyHandle()
    {
        if (get_opinion('vertify_code', true, true)) {
            $verify = new \Think\Verify();

            if (!$verify->check(I('post.vertify'))) {
                $this->error("验证码错误");
            }
        }
    }

    /**
     *
     */
    public function logout()
    {
        $UserEvent = new UserEvent();
        $logoutRes = $UserEvent->logout();
        $this->json2Response($logoutRes);
    }
}