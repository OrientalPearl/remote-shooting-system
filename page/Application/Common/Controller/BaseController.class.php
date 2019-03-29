<?php
/**
 * Created by Sy2rt Team.
 * User: zengwang.yuan
 * Date: 15-1-10
 */

namespace Common\Controller;
use Think\Controller;
use Think\Hook;

/**
 * Sy2rt基类控制器
 * Class BaseController
 * @package Common\Controller
 */

abstract class BaseController extends Controller {
	/**
	 *
	 */
	function __construct() {
		parent::__construct();

	}
	
	/**
	 * 简化tp json返回
	 * @param int $status
	 * @param string $info
	 * @param string $url
	 */
	function jsonReturn($status = 1, $info = '', $url = '') {
		die(json_encode(array("status" => $status, "info" => $info, "url" => $url)));
	}

	function jsonResult($status = 1, $info = '', $url = '') {
		return json_encode(array("status" => $status, "info" => $info, "url" => $url));
	}

	function json2Response($json) {
		$resArray = json_decode($json, true);

		if ($resArray['status'] == 1) {
			if ($resArray['url'] != '') {
				$this->success($resArray['info'], $resArray['url'], false);
			} else {
				$this->success($resArray['info']);

			}
		} else {
			$this->error($resArray['info']);
		}
	}
    
    /**
     *
     */
    protected function _currentUser()
    {
        $this->assign('user', $_SESSION[C('USER_AUTH_INFO')]);
    }

}