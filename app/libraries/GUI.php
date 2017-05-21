<?php

namespace libraries;

use Ajax\JsUtils;
use Ajax\semantic\html\elements\HtmlButton;
use models\Benchmark;
use models\Execution;
use models\Testcase;
use models\User;
use Ajax\Semantic;

class GUI {

	const ICONS=["output"=>"history","success"=>"checkmark box","info"=>"info circle","warning"=>"warning circle","error"=>"announcement"];
	const COLORS=["output"=>"","success"=>"green","info"=>"blue","warning"=>"orange","error"=>"red"];

	public static function showSimpleMessage(JsUtils $jquery,$content,$type,$icon="info circle",$timeout=NULL){
		$semantic=$jquery->semantic();
		$message=$semantic->htmlMessage("msg-".rand(0,50),$content,$type);
		$message->setIcon($icon);
		$message->setDismissable();
		if(isset($timeout))
			$message->setTimeout(3000);
		return $message;
	}

	public static function showConfMessage(JsUtils $jquery,$content,$type,$url,$responseElement,$data,$attributes=NULL){
		$messageDlg=self::showSimpleMessage($jquery,$content, $type,"help circle");
		$btOkay=new HtmlButton("bt-okay","Confirm","positive");
		$btOkay->addIcon("check circle");
		$btOkay->postOnClick($url,"{data:'".$data."'}",$responseElement,$attributes);
		$btCancel=new HtmlButton("bt-cancel","Cancel","negative");
		$btCancel->addIcon("remove circle outline");
		$btCancel->onClick($messageDlg->jsHide());

		$messageDlg->addContent([$btOkay,$btCancel]);
		return $messageDlg;
	}

	public static function showMessage(JsUtils $jquery,$content,$style,$id){
		if($style!=="info"){
			$msg=$jquery->semantic()->htmlLabel($id,$style);
			$msg->addClass("fluid ".self::COLORS[$style]);
			$msg->addIcon(self::ICONS[$style]);
			if(isset($content) && $content!==""){
				$msg->addPopup($style,$content);
			}
			echo $msg->compile($jquery);
		}
	}

	public static function displayRunningMessages(JsUtils $jquery,Benchmark &$benchmark,Execution &$execution,Testcase $testcase,$messages,$id){
		$form="form".$id;
		$i=0;
		foreach ($messages as $message){
			$obj=json_decode($message);
			if($obj!==null){
				if($obj->type==="output"){
					$output=\json_decode($obj->content);
					self::showRunningMessage($jquery,null,$output->status,$form."-".$i);
					if($output->status!=="error"){
						$time=$output->time;
					}else{
						$time="#N/A";
					}

					if(isset($testcase)){
						Models::addResult($execution,$testcase, $time, $output->status);
					}

					$bt=$jquery->semantic()->htmlButton("response-".$output->form," Time","fluid");
					$bt->addIcon("history");
					$bt->addLabel($time)->setPointing("left")->addClass("fluid");
					$btInterne=$bt->getContent()[0];
					$btInterne->addClass('fluid');
					$btInterne->addPopup("Content",$output->content);
					echo $bt->compile($jquery);
				}elseif ($obj->type==="error"){
					self::showRunningMessage($jquery,$obj->content,$obj->type,$form."-".$i);
				}
			}
			$i++;
		}
	}

	public static function showRunningMessage(JsUtils $jquery,$content,$style,$id){
		if($style!=="info"){
			$msg=$jquery->semantic()->htmlLabel("lbl-status-".$id,$style);
			$msg->addClass("fluid ".self::COLORS[$style]);
			$msg->addIcon(self::ICONS[$style]);
			if(isset($content) && $content!==""){
				$msg->addPopup($style,$content);
			}
			echo $msg->compile($jquery);
		}
	}

	public static function forkButton(JsUtils $jquery,Benchmark $benchmark){
		$id=$benchmark->getId();
		$bt=$jquery->semantic()->htmlButton("bt-fork-".$id,"Fork");
		$bt->addIcon("fork");
		$bt->addLabel(Models::countFork($benchmark))->setPointing("left")->getOnClick("Benchmarks/forks/".$id,"#main-container",["ajaxTransition"=>"random"]);
		$bt->getOnClick("Main/fork/".$id,"#main-container",["ajaxTransition"=>"random"]);
		return $bt;
	}

	public static function getBenchmarkName(JsUtils $jquery,Benchmark $benchmark,$recursive=true){
		$name=Models::getBenchmarkName($benchmark,$recursive);
		$header=$jquery->semantic()->htmlHeader("h-name",4);
		if(\sizeof($name)>1){
			$header->asTitle($name[0],"forked from <a href='#'>".$name[1]."</a>");
			$jquery->getOnClick("#subheader-h-name a", "benchmarks/seeOne/".$benchmark->getIdFork(),"#main-container",["ajaxTransition"=>"random"]);
		}
		else
			$header->setContent($name[0]);
		return $header;
	}

	public static function getBenchmarkTop(JsUtils $jquery,Benchmark $benchmark,$user){
		$result=$jquery->semantic()->htmlSegment("top-benchmark");
		$result->addContent(GUI::getBenchmarkName($jquery, $benchmark));
		$result->addContent(GUI::getToolbar($jquery, $user, $benchmark));
		return $result;
	}

	public static function starButton(JsUtils $jquery,$benchmark){
		if($benchmark instanceof Benchmark)
			$id=$benchmark->getId();
		else{
			$id=$benchmark;
		}
		$stared=Models::stared($benchmark);
		$bt=$jquery->semantic()->htmlButton("bt-star-".$id)->setProperty("title", "Star");
		$bt->addIcon("star")->setColor(($stared)?"green":"");
		$bt->addLabel(Models::countStar($benchmark))->setPointing("left")->getOnClick("Benchmarks/stars/".$id,"#main-container",["ajaxTransition"=>"random"]);
		if(UserAuth::isAuth())
			$bt->getOnClick("Main/".(($stared)?"unstar":"star")."/".$id,"#bt-star-".$id,["jqueryDone"=>"replaceWith"]);
		return $bt;
	}

	public static function getToolbar(JsUtils $jquery,User $user,Benchmark $benchmark){
		$toolbar=$jquery->semantic()->htmlButtonGroups("toolBar");
		$idBenchmark=$benchmark->getId();

		if(isset($user)){
			if($user->getId()==$benchmark->getUser()->getId()){
				$toolbar->addItem("Run test cases")->getOnClick("Benchmarks/run/".$idBenchmark,"#testTerminate")->addClass("teal")->addIcon("lightning");
				$toolbar->addItem("Update")->getOnClick("Main/benchmark/".$idBenchmark,"#main-container",["ajaxTransition"=>"random"])->addIcon("edit");
			}
		}
		$toolbar->addItem(GUI::forkButton($jquery, $benchmark));
		$toolbar->addItem(GUI::starButton($jquery, $benchmark));
		return $toolbar;
	}
}