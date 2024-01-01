<?php
namespace controllers;
use Ajax\common\Widget;
use Ajax\php\ubiquity\JsUtils;
use Ajax\Semantic;
use Ajax\semantic\html\base\HtmlSemCollection;
use Ajax\semantic\html\base\HtmlSemDoubleElement;
use libraries\GUI;
use libraries\MySettings;
use libraries\UserAuth;
use Ubiquity\controllers\Controller;
use Ubiquity\controllers\Startup;
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

    protected $settings;

    protected $style;

	/**
	 * @var boolean
	 */
	public $forwarded;

    protected function initializeAll(){
        $this->semantic=$this->jquery->semantic();
        $this->settings=MySettings::getSettings();
        $this->style=MySettings::getStyle();
        $this->view->setVar("style",$this->style);
        if($this->style==='inverted') {
            $this->jquery->setParam('beforeCompileHtml', function ($elm) {
                if (\method_exists($elm, 'setInverted')) {
                    $elm->setInverted(false);
                }
            });
        }
        GUI::$style=$this->style;
        if($this->style==='inverted'){
            $this->jquery->setAjaxLoader('<div class="ui active dimmer"><div class="ui text loader">Loading</div></div>');
        }
        $this->view->setVar("bg",MySettings::getBgColor());
        $this->jquery->getOnClick('.user-click','Benchmarks/userTab/','#main-container',['attr'=>'data-ajax','hasLoader'=>false,'listenerOn'=>'body']);
    }

    protected function setStyle(HtmlSemDoubleElement|HtmlSemCollection|Widget $element){
        if($this->style==='inverted'){
           $element->setInverted(true);
        }
    }

	public function initialize(){
        $this->initializeAll();
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
