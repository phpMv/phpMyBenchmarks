<?php
namespace controllers;
 use micro\orm\DAO;
use libraries\GUI;
use micro\utils\RequestUtils;
use Ajax\semantic\html\elements\HtmlHeader;
use libraries\UserAuth;
use models\Benchmark;
use libraries\Models;
use Ajax\semantic\html\elements\HtmlLabel;
use Ajax\semantic\html\elements\HtmlButtonGroups;
use Ajax\semantic\html\elements\HtmlButton;
use Ajax\semantic\html\collections\HtmlMessage;

 /**
 * Controller Nol
 **/
class Nol extends ControllerBase{

	public function index(){
		if(!RequestUtils::isAjax()){
			$_SESSION["jumbotron"]=false;
			$header=new HtmlHeader("header");
			$header->asImage("public/img/benchmarks.png", "phpMyBenchmarks.net","Benchmark and improve your php code to get better performances");
			$buttons=GUI::getJumboButtons();

			$headerMessage=$this->semantic->htmlMessage("jumbotron",[$header,$buttons]);
			$headerMessage->setDismissable(true);
			$headerMessage->setCloseTransition("{animation : 'fade', duration : '1s', onComplete : function() { ".$this->jquery->getDeferred("Main/jumbotron","#menu-jumbotron")."}}");
		}else{
			if(isset($_SESSION["jumbotron"]) && $_SESSION["jumbotron"]){
				$this->jquery->get("Main/jumbotron","#menu-jumbotron");
			}else{
				$this->jquery->get("Main/notJumbotron","#user-buttons","{}",null,false,"replaceWith");
			}
		}
		$myBenchs="";
		if(UserAuth::isAuth()){
			$myBenchs=$this->forward("controllers\Benchmarks","my",[],true,true,true);
		}
		if(isset($_SESSION["benchmark"])){
			if(UserAuth::isAuth() || $_SESSION["benchmark"]->getId()==null)
				$this->getTmpBenchmark($_SESSION["benchmark"]);
		}
		$this->jquery->compile($this->view);
		$this->loadView("main/index.html",["myBenchs"=>$myBenchs]);
	}
	public function all(){
		$benchmarks=DAO::getAll("models\Benchmark","1=1 ORDER BY createdAt DESC".$this->getLimitOffset(),true,true);
		GUI::displayBenchmarks($this->jquery,$this->view,$this,$benchmarks,"All benchmarks","Benchmarks/all",DAO::count("models\Benchmark"));
	}

	private function getTmpBenchmark(Benchmark $benchmark){
		$saved="";
		if($benchmark->getId()==null){
			$saved="[not saved]&nbsp;";
		}else {
			if(UserAuth::isAuth()){
				if($benchmark->getUser()->getId()!==UserAuth::getUser()->getId())
					return ;
			}
		}
		$segment=$this->semantic->htmlSegment("tmp-bench");
		$bt=new HtmlButton("btUpdate","Update");
		$bt->addLabel(Models::getBenchmarkName($benchmark),true,"edit");
		$bt->getOnClick("Main/benchmark/session","#main-container",["ajaxTransition"=>"random"]);
		$segment->setContent(["Last benchmark&nbsp;".$saved,$bt]);
		if(!UserAuth::isAuth()){
			$message=new HtmlMessage("msg-tmp-bench");
			$message->setDismissable();
			$message->setIcon("info circle");
			$message->setContent(["You need to be logged in to save this benchmark"]);
			$segment->addContent($message);
		}


	}

	public function initialize(){
		parent::initialize();
	}
}