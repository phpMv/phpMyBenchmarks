<?php
namespace controllers;
use libraries\ServerExchange;
use micro\controllers\Controller;
use models\Testcase;
use Ajax\Semantic;
use Ajax\semantic\html\elements\HtmlButton;
use micro\orm\DAO;
use models\Benchmark;
use libraries\Models;
use Ajax\semantic\html\elements\HtmlLabel;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButtonGroups;

 /**
 * Controller Main
 **/
class Main extends ControllerBase{
	const ICONS=["output"=>"history","success"=>"checkmark box","info"=>"info circle","warning"=>"warning circle","error"=>"announcement"];
	const COLORS=["output"=>"","success"=>"green","info"=>"blue","warning"=>"orange","error"=>"red"];


	public function index(){
		$header=$this->semantic->htmlHeader("header");
		$header->asImage("public/img/benchmarks.png", "phpMyBenchmarks.net","Benchmark and improve your php code to get better performances");
		$buttons=$this->semantic->htmlButtonGroups("buttons",["Create benchmark","All benchmarks"]);
		$buttons->getElement(0)->setColor("green")->setProperty("data-ajax", "Main/benchmark")->addIcon("plus");
		$buttons->getElement(1)->setProperty("data-ajax", "Benchmarks/all");

		$this->getButtons($buttons);
		$buttons->getOnClick("","#main-container",["attr"=>"data-ajax"]);
		$myBenchs="";
		if(UserAuth::isAuth()){
			$myBenchs=$this->forward("controllers\Benchmarks","my",[],true,true,true);
		}
		$this->jquery->compile($this->view);
		$this->loadView("main/index.html",["myBenchs"=>$myBenchs]);
	}

	private function getButtons(HtmlButtonGroups $buttons){
		if(UserAuth::isAuth()){
			$element=$buttons->addElement("My benchmarks");
			$element->setProperty("data-ajax", "Benchmarks/my");
		}else{
			$element=$buttons->addElement("Sign in");
			$element->setProperty("data-ajax", "Auth/signin")->addIcon("sign in");

			$element=$buttons->addElement("Sign up");
			$element->setProperty("data-ajax", "Auth/signup")->addIcon("user add");
		}
	}

	public function benchmark($id=null){
		if(isset($id)){
			$benchmark=DAO::getOne("models\Benchmark", $id,true,true);
		}else{
			$benchmark=new Benchmark();
			$benchmark->setBeforeAll("\$str='bonjour';");
			Models::addTest($benchmark,NULL,'for($j=0;$j<strlen($str);$j++){
	\substr($str, $j, 1);
}');
		}
		$_SESSION["benchmark"]=$benchmark;
		$prepForm=$this->semantic->htmlForm("preparation-form");
		$prepForm->addInput("bench-name","Name","text",$benchmark->getName());
		$prepForm->addTextarea("bench-description", "Description",$benchmark->getDescription(),"Description",2);
		$prepForm->addElement("preparation",$benchmark->getBeforeAll(),"Preparation","div","ui segment editor");
		$forms="";
		foreach ($benchmark->getTestcases() as $testcase){
			$forms.=$this->addFormTestCase($testcase,true);
		}
		$bts=$this->semantic->htmlButtonGroups("btsTests",["Run test cases","Save"]);
		$bts->addClass("fluid");
		$bts->getElement(0)->onClick("var form=getNextForm('form.toSubmit');if(form!=false) form.form('submit');");
		$btAdd=$this->semantic->htmlButton("addTest","Add test case");
		$btAdd->getOnClick("main/addFormTestCase","#forms",["jqueryDone"=>"append"]);
		$this->jquery->exec("setAceEditor('preparation');",true);
		$this->jquery->exec("google.charts.load('current', {'packages':['corechart']});",true);
		$this->jquery->exec("$('.ui.accordion').accordion({'exclusive': false});",true);
		$this->jquery->compile($this->view);
		$this->loadView("main.html",["forms"=>$forms]);
	}
	public function addFormTestCase($testcase=null,$asString=false){
		if(!($testcase instanceof Testcase)){
			$testcase=Models::addTest($_SESSION["benchmark"],NULL,'for($j=0;$j<strlen($str);$j++){
	\substr($str, $j, 1);
}');
		}
		$testcase->form=$testcase->getId();
		$id="form".$testcase->getId();
		$this->getForm($testcase);
		$this->jquery->compile($this->view);
		if($asString===true)
			return $this->loadView("testCase.html",["formName"=>$id],true);
		$this->loadView("testCase.html",["formName"=>$id]);
	}

	public function removeTest($id){
		$_SESSION["benchmark"]->removeTestByCallback(function($test) use ($id){return $test->form==$id;});
		$this->jquery->exec('$("#test-form'.$id.'").remove();',true);
		echo $this->jquery->compile();
	}

	private function generateUid(){
		return \md5(\microtime());
	}

	private function getMainUid(){
		if(!isset($_SESSION["uid"])){
			$_SESSION["uid"]=$this->generateUid();
		}
			return $_SESSION["uid"];
	}

	private function getForm(Testcase $testcase){
		$id=$testcase->form;
		$formId="form".$id;
		$form=$this->semantic->dataForm($formId, $testcase);
		$form->addClass("test toSubmit");
		$form->setFields(["name","code"]);
		$this->jquery->exec("setAceEditor('".$formId."-code-0');",true);
		$form->setSubmitParams("Main/send/".$id,"#response-".$formId,["params"=>"{'bench-name':$('#bench-name').val(),'bench-description':$('#bench-description').val(),'preparation':ace.edit('preparation').getValue(),'code':ace.edit('".$formId."-code-0').getValue()}"]);
		$form->fieldAsElement("code","div","ui segment editor");
		$btDelete=$this->semantic->htmlButton("delete-".$formId,"Delete test case","fluid");
		$btDelete->setProperty("data-ajax", $id);
		$btDelete->addIcon("remove circle outline",true,true);
		$btDelete->getOnClick('main/removeTest',"#info",["attr"=>"data-ajax"]);
	}

	public function send($id){
		$this->getMainUid();
		$command=$_POST["code"];
		$preparation=$_POST["preparation"];
		$name=$_POST["name"];
		$benchmark=$_SESSION["benchmark"];
		$benchmark->setBeforeAll($preparation);
		$benchmark->setName($_POST["bench-name"]);
		$benchmark->setDescription($_POST["bench-description"]);

		$test=$benchmark->getTestByCallback(function($test) use ($id){return $test->form==$id;});
		$test->setCode($command);
		$test->setName($name);

		$form="form".$id;
		$address="127.0.0.1";$port=9001;
		$action="run";

		$testFile="test-".\md5($name).".php";
		$filename=ROOT.DS."..".DS."server".DS."tests".DS.$testFile;
		$model=ROOT.DS."..".DS."server".DS."test.tpl";
		$this->openReplaceWrite($model, $filename,["%test%"=>$command,"%preparation%"=>$preparation]);
		$params=["check.php","tests/".$testFile,$form,$id];
		$content="php.bat";
		$serverExchange=new ServerExchange($address,$port);
		$responses=$serverExchange->send($action, $content, $params);
		$this->displayMessages($responses,$id);
		$this->jquery->exec("$('#".$form."').removeClass('toSubmit');var form=getNextForm('form.toSubmit');if(form!=false) form.form('submit'); else {\$('form.test').addClass('toSubmit');".
			$this->jquery->getDeferred("Main/testsTerminate","#results")."}",true);
		echo $this->jquery->compile();
	}

	public function testsTerminate(){
		$benchmark=$_SESSION["benchmark"];
		$this->jquery->exec("drawChart('".$_SESSION["uid"]."',".Models::getResults($benchmark, $_SESSION["uid"]).",'graph');",true);
		echo $this->jquery->compile();

		unset($_SESSION["uid"]);
		if(UserAuth::isAuth()){
			Models::save($benchmark);
		}
	}

	private function replaceAll($array,$subject){
		array_walk($array, function(&$item){if(is_array($item)) $item=implode("\n", $item);});
		return str_replace(array_keys($array), array_values($array), $subject);
	}

	private function openReplaceWrite($source,$destination,$keyAndValues){
		$str=\file_get_contents($source);
		$str=self::replaceAll($keyAndValues,$str);
		return \file_put_contents($destination,$str);
	}

	private function displayMessages($messages,$id){
		$form="form".$id;
		$i=0;
		foreach ($messages as $message){
			$obj=json_decode($message);
			if($obj!==null){
				if($obj->type==="output"){
					$output=\json_decode($obj->content);
					$this->showMessage(null,$output->status,$form."-".$i);
					if($output->status!=="error"){
						$time=$output->time;
					}else{
						$time="#N/A";
					}

					$testcase=$_SESSION["benchmark"]->getTestByCallback(function($test) use ($id){return $test->form==$id;});
					if(isset($testcase)){
						Models::addResult($testcase, $time, $output->status, $_SESSION["uid"]);
					}

					$bt=$this->semantic->htmlButton("response-".$output->form," Time","fluid");
					$bt->addIcon("history");
					$bt->addLabel($time)->setPointing("left")->addClass("fluid");
					$btInterne=$bt->getContent()[0];
					$btInterne->addClass('fluid');
					$btInterne->addPopup("Content",$output->content);
					echo $bt->compile($this->jquery);
				}elseif ($obj->type==="error"){
					$this->showMessage($obj->content,$obj->type,$form."-".$i);
				}
			}
			$i++;
		}
	}

	private function showMessage($content,$style,$id){
		if($style!=="info"){
			$msg=$this->semantic->htmlLabel($id,$style);
			$msg->addClass("fluid ".self::COLORS[$style]);
			$msg->addIcon(self::ICONS[$style]);
			if(isset($content) && $content!==""){
				$msg->addPopup($style,$content);
			}
			echo $msg->compile($this->jquery);
		}
	}

	public function initialize(){
		parent::initialize();
		/*if(!UserAuth::isAuth())
			$this->jquery->get("Auth/infoUser","#divInfoUser","{}",null,false);*/
	}
}