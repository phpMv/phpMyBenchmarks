<?php
namespace controllers;
use libraries\ServerExchange;
use Ubiquity\controllers\Controller;
use models\Testcase;
use Ajax\Semantic;
use Ajax\semantic\html\elements\HtmlButton;
use Ubiquity\orm\DAO;
use models\Benchmark;
use libraries\Models;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButtonGroups;
use libraries\GUI;
use Ajax\service\JArray;
use models\Domain;

 /**
 * Controller Main
 **/
class Main extends ControllerBase{
	const ICONS=["output"=>"history","success"=>"checkmark box","info"=>"info circle","warning"=>"warning circle","error"=>"announcement"];
	const COLORS=["output"=>"","success"=>"green","info"=>"blue","warning"=>"orange","error"=>"red"];


	public function index(){
	}

	public function jumbotron(){
		$_SESSION["jumbotron"]=true;
		echo GUI::getJumboButtons(false)->compile($this->jquery);
		echo $this->jquery->compile($this->view);
	}

	public function notJumbotron(){
		$_SESSION["jumbotron"]=false;
		echo GUI::getJumboButtons(true)->compile($this->jquery);
		echo $this->jquery->compile($this->view);
	}

	private function getButtons(HtmlButtonGroups $buttons){
		if(UserAuth::isAuth()){
			$element=$buttons->addElement("My benchmarks");
			$element->setProperty("data-ajax", "Benchmarks/myTab");
		}else{
			$element=$buttons->addElement("Sign in");
			$element->setProperty("data-ajax", "Auth/signin")->addIcon("sign in");

			$element=$buttons->addElement("Sign up");
			$element->setProperty("data-ajax", "Auth/signup")->addIcon("user add");
		}
	}

	public function createBenchmark(){
		return $this->benchmark();
	}

	public function benchmark($id=null){
		if(isset($id)){
			if($id!=="session")
				$benchmark=DAO::getOne(Benchmark::class, $id,true);
			else{
				$benchmark=$_SESSION["benchmark"];
			}
		}else{
			$benchmark=new Benchmark();
			$benchmark->setBeforeAll("//php preparation code executed before each test case");
			Models::addTest($benchmark,NULL,'//php test case code');
		}
		$_SESSION["benchmark"]=$benchmark;
		GUI::getBenchmarkName($this->jquery, $benchmark);
		$prepForm=$this->semantic->htmlForm("preparation-form");
		$prepForm->addInput("bench-name","Name","text",$benchmark->getName());
		$prepForm->addTextarea("bench-description", "Description",$benchmark->getDescription(),"Description",2);
		$fields=$prepForm->addFields();
		$input=$fields->addInput("iterations","Iterations count","number",$benchmark->getIterations(),"")->setWidth(6);
		$input->getDataField()->setProperty("max", "1000000");
		$fields->addDropdown("bench-phpVersion",Models::$PHP_VERSIONS,"php version",$benchmark->getPhpVersion());
		$fields->addDropdown("domains",JArray::modelArray(DAO::getAll(Domain::class,false),"getId","getName"),"Domains",$benchmark->getDomains(),true);

		$prepForm->addElement("preparation",$benchmark->getBeforeAll(),"Preparation","div","ui segment editor");
		$forms="";
		foreach ($benchmark->getTestcases() as $testcase){
			$forms.=$this->addFormTestCase($testcase,true);
		}
		$runCaption="Run test cases";

		if(UserAuth::isAuth()){
			$runCaption="Save and run test cases";
		}
		$bts=$this->semantic->htmlButtonGroups("btsTests",[$runCaption,"Close"]);
		$bts->addClass("fluid");
		$bts->getElement(0)->onClick("var form=getNextForm('form.toSubmit');if(form!=false) form.form('submit');")->addClass("teal")->addIcon("lightning");
		$btAdd=$this->semantic->htmlButton("addTest","Add test case");
		$btAdd->addIcon("plus");
		$btAdd->getOnClick("Main/addFormTestCase","#forms",["jqueryDone"=>"append","hasLoader"=>false]);
		$this->jquery->exec("setAceEditor('preparation');",true);
		$this->jquery->exec("google.charts.load('current', {'packages':['corechart']});",true);
		$this->jquery->exec("$('.ui.accordion').accordion({'exclusive': false});",true);
		$this->jquery->compile($this->view);
		$this->loadView("main.html",["forms"=>$forms]);
	}
	public function addFormTestCase($testcase=null,$asString=false){
		if(!($testcase instanceof Testcase)){
			$testcase=Models::addTest($_SESSION["benchmark"],NULL,'',Models::$DEFAULT_PHP_VERSION);
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
			$uid=$this->generateUid();
			$_SESSION["uid"]=$uid;
			$_SESSION["execution"]=$_SESSION["benchmark"]->addExecution($uid);
		}
			return $_SESSION["uid"];
	}

	private function getForm(Testcase $testcase){
		$id=$testcase->form;
		$formId="form".$id;
		$form=$this->semantic->dataForm($formId, $testcase);
		$form->addClass("test toSubmit");
		$form->setFields(["name","phpVersion\n","code"]);
		$this->jquery->exec("setAceEditor('".$formId."-code-0');",true);
		$form->setSubmitParams("Main/send/".$id,"#response-".$formId,["params"=>
				"{'bench-phpVersion':$(\"[name='bench-phpVersion']\").val(),'domains':$('#domains').val(),'bench-name':$('#bench-name').val(),'bench-description':$('#bench-description').val(),'preparation':ace.edit('preparation').getValue(),'code':ace.edit('".$formId."-code-0').getValue(),'iterations':$('#iterations').val()}"]);
		$form->fieldAsElement("code","div","ui segment editor");
		$form->fieldAsDropDown(1,Models::$PHP_VERSIONS,false);
		$btDelete=$this->semantic->htmlButton("delete-".$formId,"Delete test case","fluid");
		$btDelete->setProperty("data-ajax", $id);
		$btDelete->addIcon("remove circle",true,true);
		$btDelete->getOnClick('Main/removeTest',"#info",["attr"=>"data-ajax"]);
	}

	public function send($id){
		$isWin=\strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
		$prefix=($isWin)?"":ROOT.DS."..".DS."server".DS;

		$this->getMainUid();
		$command=$_POST["code"];
		$preparation=$_POST["preparation"];
		$iterations=min($_POST["iterations"],1000000);
		$name=$_POST["name"];
		$domains=$_POST["domains"];
		$phpVersion=$_POST["bench-phpVersion"];
		$benchmark=$_SESSION["benchmark"];
		$benchmark->setBeforeAll($preparation);
		$benchmark->setName($_POST["bench-name"]);
		$benchmark->setDescription($_POST["bench-description"]);
		$benchmark->setIterations($iterations);
		$benchmark->setDomains($domains);
		$benchmark->setPhpVersion($phpVersion);

		$test=$benchmark->getTestByCallback(function($test) use ($id){return $test->form==$id;});
		$test->setCode($command);
		$test->setName($name);
		$test->setPhpVersion($_POST["phpVersion"]);

		$form="form".$id;
		$address="127.0.0.1";$port=9001;
		$action="run";
		$phpVersion=Models::getTestPhpVersion($benchmark, $test);


		$testFile="test-".\md5($name).".php";
		$filename=ROOT.DS."..".DS."server".DS."tests".DS.$testFile;
		$model=ROOT.DS."..".DS."server".DS."test.tpl";
		Models::openReplaceWrite($model, $filename,["%test%"=>$command,"%preparation%"=>$preparation,"%iterations%"=>$iterations]);
		$params=[$prefix."check.php",$prefix."tests/".$testFile,$form,$id];
		if(isset($phpVersion) && !$isWin)
			$params[]=$phpVersion;
		$content=($isWin)?"php-test.bat":$prefix."php-test.sh";
		$serverExchange=new ServerExchange($address,$port);
		$responses=$serverExchange->send($action, $content, $params);
		GUI::displayRunningMessages($this->jquery, $_SESSION["benchmark"], $_SESSION["execution"],$test,$responses,$id);
		$this->jquery->exec("$('#".$form."').removeClass('toSubmit');var form=getNextForm('form.toSubmit');if(form!=false) form.form('submit'); else {\$('form.test').addClass('toSubmit');".
			$this->jquery->getDeferred("Main/testsTerminate","#results")."}",true);
		echo $this->jquery->compile();
	}

	public function testsTerminate(){
		$benchmark=$_SESSION["benchmark"];
		$execution=$_SESSION["execution"];
		$this->jquery->exec("drawChart('".$_SESSION["uid"]."',".Models::getResults($execution).",'graph');",true);

		$message=$this->semantic->htmlMessage("msg-terminate");
		$message->setDismissable();
		unset($_SESSION["uid"]);
		if(UserAuth::isAuth()){
			Models::save($benchmark);
			$message->setIcon("info circle");
			$message->addContent("Benchmark ".\implode("", Models::getBenchmarkName($benchmark))." saved.");
		}else{
			$message->setContent("You need to be logged in to save this benchmark");
			$message->setIcon("warning circle");
			$message->addClass("warning");
		}
		echo $message;
		echo $this->jquery->compile($this->view);
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

	public function star($idBenchmark){
		DAO::$db->execute("INSERT INTO benchstar(idBenchmark,idUser) VALUES(".$idBenchmark.",".UserAuth::getUser()->getId().");");
		echo GUI::starButton($this->jquery, $idBenchmark);
		echo $this->jquery->compile($this->view);
	}

	public function unstar($idBenchmark){
		DAO::$db->execute("DELETE FROM benchstar WHERE idBenchmark=".$idBenchmark." AND idUser=".UserAuth::getUser()->getId().";");
		echo GUI::starButton($this->jquery, $idBenchmark);
		echo $this->jquery->compile($this->view);
	}

	public function fork($idBenchmark){
		$benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		$tests=DAO::getOneToMany($benchmark, "testcases");
		$user=UserAuth::getUser();
		$benchmark->setUser($user);
		$benchmark->setId(NULL);
		$benchmark->setIdFork($idBenchmark);
		$benchmark->setCreatedAt(\date("Y-m-d H:i:s"));
		DAO::insert($benchmark);
		foreach ($tests as $test){
			$test->setBenchmark($benchmark);
			$test->setId(null);
			$test->setCreatedAt(\date("Y-m-d H:i:s"));
			DAO::insert($test);
		}
		$this->forward("controllers\Main","benchmark",["id"=>$benchmark->getId()],true,true);
	}

	public function initialize(){
		parent::initialize();
	}
}