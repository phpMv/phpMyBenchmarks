<?php
namespace controllers;
 use micro\orm\DAO;
use Ajax\semantic\html\elements\HtmlLabel;
use libraries\Models;
use Ajax\semantic\html\elements\HtmlList;
use models\Result;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButton;
use micro\db\Database;
use Ajax\semantic\html\content\view\HtmlItem;
use Ajax\semantic\html\elements\HtmlSegment;

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
		$benchmarks=DAO::getAll("models\Benchmark","",true,true);
		$this->displayBenchmarks($benchmarks,"All benchmarks");
	}

	public function my(){
		$benchmarks=DAO::getAll("models\Benchmark","idUser=".UserAuth::getUser()->getId()." ORDER BY createdAt DESC",true,true);
		$this->displayBenchmarks($benchmarks,"My benchmarks");
	}

	private function displayBenchmarks($benchMarks,$title){
		$deBenchs=$this->semantic->dataTable("deBenchs", "models\Benchmark", $benchMarks);
		$deBenchs->setCompact(true);
		$deBenchs->setIdentifierFunction("getId");
		$deBenchs->setFields(['stars','name','tests','results','createdAt']);
		$deBenchs->setCaptions(['Stars','Name','Tests','Latest results','Created at','Actions']);
		$deBenchs->setValueFunction("stars", function($str,$bench){
			$count=DAO::$db->count('benchstar',"idBenchmark=".$bench->getId());
			$bt=new HtmlButton("bt-star-".$bench->getId());
			$bt->addIcon("star");
			$bt->addLabel($count);
			$bt->getContent()[0]->addClass("icon");
			return $bt;
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
		$deBenchs->setValueFunction("createdAt", function($time){return Models::time_elapsed_string($time,false);});

		$deBenchs->addEditDeleteButtons(true,["ajaxTransition"=>"random"],function($edit,$bench){
			if(!isset($_SESSION["user"]) || $bench->getUser()->getId()!=$_SESSION["user"]->getId())
				$edit->asIcon("fork");
		},
		function($delete,$bench){
			if(!isset($_SESSION["user"]) || $bench->getUser()->getId()!=$_SESSION["user"]->getId())
				$delete->wrap("<!--","-->");
		});
		$deBenchs->insertInFieldButton(5, "",true,function($see){
			$see->addClass("see")->asIcon("unhide");
		});
		$deBenchs->setUrls(["edit"=>"Main/benchmark","delete"=>"Benchmarks/delete"]);
		$deBenchs->setTargetSelector(["edit"=>"#main-container","delete"=>"#info"]);

		$this->jquery->getOnClick(".see", "Benchmarks/seeOne","#main-container",["attr"=>"data-ajax"]);
		$this->jquery->compile($this->view);
		$this->loadView("benchmarks/display.html",["title"=>$title]);
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
		$header1=$this->semantic->htmlHeader("header1",3,"Code");
		$header1->addIcon("code");
		$benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		$tests=DAO::getOneToMany($benchmark, "testcases");
		$this->jquery->exec("setAceEditor('preparation',true);",true);
		$list=$this->semantic->htmlList("tests");
		$list->fromDatabaseObjects($tests, function($test){
			$item=new HtmlItem("");
			$code=new HtmlSegment("code-".$test->getId(),\htmlentities($test->getCode()));
			$code->addClass("editor");
			$item->addItemContent([$test->getName(),$code]);
			$this->jquery->exec("setAceEditor('code-".$test->getId()."',true);",true);
			return $item;
		});
		$this->jquery->exec("$('.ui.accordion').accordion({'exclusive': false});",true);
		$this->jquery->compile($this->view);
		$this->loadView("benchmarks/seeOne.html",["benchmark"=>$benchmark]);
	}

	public function star($idBenchmark){

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