<?php
namespace controllers;
 use micro\orm\DAO;
use Ajax\semantic\html\elements\HtmlLabel;
use libraries\Models;
use Ajax\semantic\html\elements\HtmlList;
use models\Result;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButton;
use libraries\GUI;
use models\Benchmark;
use models\Testcase;
use libraries\ServerExchange;
use models\Execution;
use Ajax\semantic\html\elements\html5\HtmlLink;
use Ajax\common\html\HtmlDoubleElement;
use Ajax\semantic\html\base\HtmlSemDoubleElement;

 /**
 * Controller Benchmarks
 **/
class Benchmarks extends ControllerBase{

	const COLORS=["success"=>"green","info"=>"blue","warning"=>"orange","error"=>"red"];

	public function index(){}

	public function initialize(){
		$this->semantic=$this->jquery->semantic();
	}

	public function finalize(){
	}

	public function all(){
		$benchmarks=DAO::getAll("models\Benchmark","1=1 ORDER BY createdAt DESC".$this->getLimitOffset(),true,true);
		$this->displayBenchmarks($benchmarks,"All benchmarks","Benchmarks/all",DAO::count("models\Benchmark"));
	}

	public function my(){
		$benchmarks=DAO::getAll("models\Benchmark","idUser=".UserAuth::getUser()->getId()." ORDER BY createdAt DESC".$this->getLimitOffset(),true,true);
		$this->displayBenchmarks($benchmarks,"My benchmarks","Benchmarks/my",DAO::count("models\Benchmark","idUser=".UserAuth::getUser()->getId()));
	}

	private function getLimitOffset($count=10){
		$p=1;
		if(isset($_POST["p"])){
			$p=$_POST['p'];
		}
		return " LIMIT ".(($p-1)*$count).",".$count;
	}

	private function createDataBenchmark($benchMarks,$jsonUrl){
		$deBenchs=$this->semantic->dataTable("deBenchs", "models\Benchmark", $benchMarks);
		$deBenchs->setIdentifierFunction("getId");

		$deBenchs->setCompact(true);
		$deBenchs->setFields(['stars','name','tests','results','createdAt']);
		$deBenchs->setCaptions(['Stars','Name','Tests','Latest results','Created at','Actions']);

		$deBenchs->setValueFunction("stars", function($str,$bench){
			return GUI::starButton($this->jquery, $bench);
		});

			$deBenchs->setValueFunction("tests", function($str,$bench){return new HtmlLabel("",\count($bench->getTestcases()));});

			$deBenchs->setValueFunction("results",function($str,$bench){
				$results=Models::getLastResults($bench,true);
				$list=new HtmlList("");
				$list->setHorizontal();
				$count=\count($results);
				$lblFast="";$lblSlow="";
				if($count>0){
					if($count>1){
						$lblFast=(new HtmlLabel(""))->addClass("green empty circular")." ";
						$lblSlow=(new HtmlLabel(""))->addClass("orange empty circular")." ";
					}
					$this->addListResult($list, $results[0],$lblFast);
					for ($i=1;$i<$count-1;$i++){
						$this->addListResult($list, $results[$i]);
					}
					if($count>1){
						$this->addListResult($list, $results[$count-1],$lblSlow);
					}
				}
				return $list;
			});

			$deBenchs->setValueFunction("name", function($name,$bench){
				$elm=new HtmlSemDoubleElement("name-".$bench->getId(),"div");
				$elm->setContent($name);
				$elm->addPopupHtml(GUI::getBenchmarkName($this->jquery, $bench),NULL,["setFluidWidth"=>true,"on"=>"click"]);
				return $elm;
			});

			$deBenchs->setValueFunction("createdAt", function($time){return Models::time_elapsed_string($time,false);});

			$deBenchs->addEditDeleteButtons(true,["ajaxTransition"=>"random"],function($edit,$bench){
				if(!isset($_SESSION["user"]) || $bench->getUser()->getId()!=$_SESSION["user"]->getId())
					$edit->wrap("<!--","-->");
			},
			function($delete,$bench){
				if(!isset($_SESSION["user"]) || $bench->getUser()->getId()!=$_SESSION["user"]->getId())
					$delete->wrap("<!--","-->");
			});
			$deBenchs->insertInFieldButton(5, "",true,function($fork,$bench){
				if(!isset($_SESSION["user"]) || $bench->getUser()->getId()!=$_SESSION["user"]->getId())
					$fork->addClass("fork")->asIcon("fork");
					else
						$fork->wrap("<!--","-->");
			});
			$deBenchs->insertInFieldButton(5, "",true,function($see){
				$see->addClass("see")->asIcon("unhide");
			});

			$deBenchs->setUrls(["refresh"=>$jsonUrl,"edit"=>"Main/benchmark","delete"=>"Benchmarks/delete"]);
			$deBenchs->setTargetSelector(["edit"=>"#main-container","delete"=>"#info"]);

			$this->jquery->getOnClick(".see", "Benchmarks/seeOne","#main-container",["attr"=>"data-ajax"]);
			$this->jquery->getOnClick(".fork", "Main/fork","#main-container",["attr"=>"data-ajax"]);
			return $deBenchs;
	}

	private function displayBenchmarks($benchMarks,$title,$jsonUrl,$total_rowcount,$count=10){
		$deBenchs=$this->createDataBenchmark($benchMarks, $jsonUrl);

		if(isset($_POST["p"])){
			$deBenchs->paginate($_POST["p"], $total_rowcount,$count);
			$deBenchs->refresh();
			$s= $this->jquery->compile($this->view);
			echo $deBenchs;
			echo $s;
		}else{
			$deBenchs->paginate(1, $total_rowcount,$count);
			$this->jquery->compile($this->view);
			$this->loadView("benchmarks/display.html",["title"=>$title]);
		}
	}

	private function addListResult(HtmlList $list,Result $result,$tag=""){
		$list->addItem(["image"=>"public/img/".$result->getStatus().".png","header"=>$result->getTestcase()->getName(),"description"=>$tag.Models::getTime($result->getTimer())]);
	}

	public function delete($ids){
		$instance=DAO::getOne("models\Benchmark",$ids);
		$instanceString=$instance."";
			if(sizeof($_POST)>0){
				if(DAO::remove($instance)){
					$message=$this->showSimpleMessage("Suppression de `".$instanceString."`", "info","info",4000);
					$this->jquery->exec("$('tr[data-ajax={$ids}]').remove();",true);
				}else{
					$message=$this->showSimpleMessage("Impossible de supprimer `".$instanceString."`", "warning","warning");
				}
			}else{
				$message=$this->showConfMessage("Confirmez la suppression de `".$instanceString."`?", "", "Benchmarks/delete/{$ids}", "#info", $ids);
			}
			echo $message;
			echo $this->jquery->compile($this->view);
	}

	public function seeOne($idBenchmark){
		$user=UserAuth::getUser();
		$benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		GUI::getBenchmarkTop($this->jquery,$benchmark,$user);



		$header1=$this->semantic->htmlHeader("header1",3,"Code");
		$header1->addIcon("code");
		$tests=DAO::getOneToMany($benchmark, "testcases");
		$this->jquery->exec("setAceEditor('preparation',true);",true);
		$testsView="";
		foreach ($tests as $test){
			$testsView.=$this->loadView("benchmarks/seeOneTest.html",["id"=>$test->getId(),"name"=>$test->getName(),"code"=>$test->getCode()],true);
			$this->jquery->exec("setAceEditor('code-".$test->getId()."',true);",true);
		}

		$header2=$this->semantic->htmlHeader("header2",3,"Executions");
		$header2->addIcon("lightning");

		$header3=$this->semantic->htmlHeader("header3",3,"Graph");
		$header3->addIcon("bar chart");
		$this->listExecs($benchmark);

		$this->jquery->exec("$('.ui.accordion').accordion({'exclusive': false});",true);
		$this->jquery->exec("google.charts.load('current', {'packages':['corechart']});",true);

		$this->jquery->compile($this->view);
		$this->loadView("benchmarks/seeOne.html",["benchmark"=>$benchmark,"tests"=>$testsView]);
	}

	public function listExecs($benchmark){
		$executions=DAO::getAll("models\Execution", "idBenchmark=".$benchmark->getId()." ORDER BY createdAt DESC");
		$listExecs=$this->semantic->dataTable("list-executions", "models\Execution", $executions);
		$listExecs->setIdentifierFunction("getId");
		$listExecs->setFields(["uid","createdAt","results"]);
		$listExecs->setValueFunction("createdAt", function($str){ return Models::time_elapsed_string($str);});
		$listExecs->setValueFunction("uid", function($str){ return \substr($str,0,7);});

		$listExecs->setValueFunction("results",function($str,$exec){
			$results=DAO::getAll("models\Result", "idExecution=".$exec->getId()." ORDER BY timer ASC");
			$list=new HtmlList("");
			$list->setHorizontal();
			$count=\count($results);
			$lblFast="";$lblSlow="";
			if($count>0){
				if($count>1){
					$lblFast=(new HtmlLabel(""))->addClass("green empty circular")." ";
					$lblSlow=(new HtmlLabel(""))->addClass("orange empty circular")." ";
				}
				$this->addListResult($list, $results[0],$lblFast);
				for ($i=1;$i<$count-1;$i++){
					$this->addListResult($list, $results[$i]);
				}
				if($count>1){
					$this->addListResult($list, $results[$count-1],$lblSlow);
				}
			}
			return $list;
		});
		$listExecs->setActiveRowSelector("error");
		$listExecs->getOnRow("click", "Benchmarks/seeExecutionResults","#ajax",["attr"=>"data-ajax"]);
		return $listExecs;
	}

	public function seeExecutionResults($idExecution){
		$execution=DAO::getOne("models\Execution", $idExecution);
		$results=DAO::getOneToMany($execution, "results");
		foreach ($results as $result){
			$this->jquery->get("Benchmarks/seeResult/".$result->getId(),"#result-".$result->getTestcase()->getId(),"{}",null,false);
		}
		$this->jquery->exec("drawChart('".$execution->getUid()."',".Models::getChartResults($results,true).",'graph');",true);

		echo $this->jquery->compile();
	}

	public function seeResult($idResult){
		$result=DAO::getOne("models\Result", $idResult);
		GUI::showMessage($this->jquery,null,$result->getStatus(),"lbl-".$result->getId());
		if($result->getStatus()!=="error"){
			$time=Models::getTime($result->getTimer());
		}else{
			$time="#N/A";
		}

		$bt=$this->semantic->htmlButton("bt-".$result->getId()," Time","fluid");
		$bt->addIcon("history");
		$bt->addLabel($time)->setPointing("left")->addClass("fluid");
		$btInterne=$bt->getContent()[0];
		$btInterne->addClass('fluid');
		echo $bt;
	}

	public function seeChart($idExecution){
		$execution=DAO::getOne("models\Execution", $idExecution);
		$results=DAO::getOneToMany($execution, "results");
		$this->jquery->exec("drawChart('".$execution->getUid()."',".Models::getChartResults($results,true).",'graph');",true);
		echo $this->jquery->compile();
	}

	public function run($idBenchmark){
		$benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		DAO::getOneToMany($benchmark, "testcases");
		$execution=$benchmark->addExecution(\md5(\microtime(true)));
		$_SESSION["execution"]=$execution;
		$testsIds=Models::getTestIds($benchmark);
		$_SESSION["testsIds"]=$testsIds;
		if(\sizeof($testsIds)>0){
			$this->jquery->get("Benchmarks/runTest/".$testsIds[0],"#result-".$testsIds[0]);
		}
		echo $this->jquery->compile();
	}

	public function runTest($id){
		$isWin=\strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
		$prefix=($isWin)?"":ROOT.DS."..".DS."server".DS;
		$test=DAO::getOne("models\Testcase", $id);
		$bench=$test->getBenchmark();
		$execution=$_SESSION["execution"];
		$id=$test->getId();
		$form="form".$id;
		$address="127.0.0.1";$port=9001;
		$action="run";

		$testFile="test-".\md5($test->getName()).".php";
		$filename=ROOT.DS."..".DS."server".DS."tests".DS.$testFile;
		$model=ROOT.DS."..".DS."server".DS."test.tpl";
		Models::openReplaceWrite($model, $filename,["%test%"=>$test->getCode(),"%preparation%"=>$bench->getBeforeAll(),"%iterations%"=>$bench->getIterations()]);
		$params=[$prefix."check.php",$prefix."tests/".$testFile,$form,$id];
		$content=($isWin)?"php-test.bat":$prefix."php-test.sh";
		$serverExchange=new ServerExchange($address,$port);
		$responses=$serverExchange->send($action, $content, $params);
		GUI::displayRunningMessages($this->jquery, $bench, $execution, $test,$responses, $id);
		\array_shift($_SESSION["testsIds"]);
		if(\sizeof($_SESSION["testsIds"])>0){
			$testsIds=$_SESSION["testsIds"];
			$this->jquery->get("Benchmarks/runTest/".$testsIds[0],"#result-".$testsIds[0]);
		}else{
			$this->jquery->get("Benchmarks/testTerminate","#list-executions","{}",NULL,true,"replaceWith","random");
		}
		echo $this->jquery->compile();
	}

	public function testTerminate(){
		$execution=$_SESSION["execution"];
		DAO::insert($execution);
		foreach ($execution->getResults() as $result){
			DAO::insert($result);
		}
		$benchmark=$execution->getBenchmark();
		echo $this->listExecs($benchmark);
		echo $this->jquery->compile($this->view);
	}

	public function stars($idBenchmark){
		$benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		echo GUI::getBenchmarkTop($this->jquery, $benchmark,UserAuth::getUser());
		echo $this->semantic->htmlHeader("",2,"Stargazers");
		$userstars=DAO::getManyToMany($benchmark, "userstars");
		$list=$this->semantic->htmlCardGroups("list-user-stars");
		$list->fromDatabaseObjects($userstars, function($user) use ($list){
			$image=$user->getAvatar();
			$image=($image!=null && $image!="")?$image:"public/img/male.png";
			$card=$list->newItem("card-".$user->getId());
			$card->addItemContent(Models::getUserName($user))->addImage($image,"","mini")->setFloated("left")->asAvatar();
			return $card;

		});
		echo $list;
		echo $this->jquery->compile($this->view);
	}

	public function forks($idBenchmark){
		$benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		echo GUI::getBenchmarkTop($this->jquery, $benchmark,UserAuth::getUser());
		echo $this->semantic->htmlHeader("",2,"Forks");
		$forks=DAO::getAll("models\Benchmark","idFork=".$idBenchmark);
		$list=$this->semantic->htmlCardGroups("list-user-stars");
		$list->fromDatabaseObjects($forks, function($bench) use ($list){
			$card=$list->newItem("card-".$bench->getId());
			$seg=new HtmlLink("forked-".$bench->getId(),"#",Models::getBenchmarkName($bench,false));
			$seg->getOnClick("Benchmarks/seeOne/".$bench->getId(),"#main-container");
			$seg->addIcon("fork");
			$card->addItemContent($seg);
			return $card;

		});
		echo $list;
		echo $this->jquery->compile($this->view);
	}

	private function showSimpleMessage($content,$type,$icon="info",$timeout=NULL){
		$semantic=$this->jquery->semantic();
		$message=$semantic->htmlMessage("msg-".rand(0,50),$content,$type);
		$message->setIcon($icon." circle");
		$message->setDismissable();
		if(isset($timeout))
			$message->setTimeout(3000);
		return $message;
	}

	private function showConfMessage($content,$type,$url,$responseElement,$data,$attributes=NULL){
		$messageDlg=$this->showSimpleMessage($content, $type,"help circle");
		$btOkay=new HtmlButton("bt-okay","Confirm","positive");
		$btOkay->addIcon("check circle");
		$btOkay->postOnClick($url,"{data:'".$data."'}",$responseElement,$attributes);
		$btCancel=new HtmlButton("bt-cancel","Cancel","negative");
		$btCancel->addIcon("remove circle outline");
		$btCancel->onClick($messageDlg->jsHide());

		$messageDlg->addContent([$btOkay,$btCancel]);
		return $messageDlg;
	}

}