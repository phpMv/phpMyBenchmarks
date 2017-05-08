<?php
namespace controllers;
 use micro\orm\DAO;
use Ajax\semantic\html\elements\HtmlLabel;
use libraries\Models;
use Ajax\semantic\html\elements\HtmlList;
use models\Result;
use libraries\UserAuth;

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
		$deBenchs->setFields(["name","tests","results","createdAt"]);
		$deBenchs->setCaptions(['Name','Tests','Latest results','Created at']);
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
		$this->jquery->compile($this->view);
		$this->loadView("benchmarks/display.html",["title"=>$title]);
	}

	private function addListResult(HtmlList $list,Result $result,$tag=""){
		$list->addItem(["image"=>"public/img/".$result->getStatus().".png","header"=>$result->getTestcase()->getName(),"description"=>$tag.Models::getTime($result->getTimer())]);
	}

}