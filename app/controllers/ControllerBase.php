<?php
namespace controllers;
use Ajax\Semantic;
use Ajax\JsUtils;
use libraries\UserAuth;
use Ubiquity\controllers\Controller;
use Ubiquity\utils\http\URequest;
 /**
 * ControllerBase
 * @property JsUtils $jquery
 **/
abstract class ControllerBase extends Controller{

	/**
	 * @var Semantic
	 */
	protected $semantic;

	/**
	 * @var boolean
	 */
	public $forwarded;

	public function initialize(){
		$this->semantic=$this->jquery->semantic();
		if(!URequest::isAjax() && !$this->forwarded){
			$this->loadView("main/vHeader.html",["infoUser"=>UserAuth::getInfoUser($this->jquery,true)]);
		}
	}

	public function finalize(){
		if(!URequest::isAjax() && !$this->forwarded){
			$this->loadView("main/vFooter.html");
		}
	}
}
