<?php
namespace controllers;
use micro\utils\RequestUtils;
use micro\controllers\Controller;
use Ajax\Semantic;
use Ajax\JsUtils;
use libraries\UserAuth;
 /**
 * ControllerBase
 * @property JsUtils $jquery
 **/
abstract class ControllerBase extends Controller{

	/**
	 * @var Semantic
	 */
	protected $semantic;
	public function initialize(){
		$this->semantic=$this->jquery->semantic();
		if(!RequestUtils::isAjax()){
			$this->loadView("main/vHeader.html",["infoUser"=>UserAuth::getInfoUser($this->jquery,true)]);
		}
	}

	public function finalize(){
		if(!RequestUtils::isAjax()){
			$this->loadView("main/vFooter.html");
		}
	}
}
