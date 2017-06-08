<?php

namespace libraries;

use Ajax\JsUtils;
use Ajax\semantic\html\elements\HtmlButton;
use models\Benchmark;
use models\Execution;
use models\Testcase;
use models\User;
use Ajax\Semantic;
use Ajax\semantic\html\elements\HtmlButtonGroups;
use Ajax\semantic\html\collections\HtmlMessage;
use Ajax\semantic\html\elements\HtmlLabel;
use Ajax\semantic\html\elements\HtmlList;
use models\Result;
use Ajax\semantic\html\base\HtmlSemDoubleElement;
use micro\views\View;
use controllers\ControllerBase;

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

	public static function getBenchmarkTop(JsUtils $jquery,Benchmark $benchmark,$user,$id="top-benchmark"){
		$result=$jquery->semantic()->htmlSegment($id);
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

	public static function getToolbar(JsUtils $jquery,$user,Benchmark $benchmark){
		$toolbar=$jquery->semantic()->htmlButtonGroups("toolBar");
		$idBenchmark=$benchmark->getId();

		if(isset($user)){
			if($user->getId()==$benchmark->getUser()->getId()){
				$toolbar->addItem("Run test cases")->getOnClick("Benchmarks/run/".$idBenchmark,"#testTerminate")->addClass("teal")->addIcon("lightning");
				$toolbar->addItem("Update")->getOnClick("Main/benchmark/".$idBenchmark,"#main-container",["ajaxTransition"=>"random"])->addIcon("edit");
			}
			$toolbar->addItem(GUI::forkButton($jquery, $benchmark));
		}
		$toolbar->addItem(GUI::starButton($jquery, $benchmark));
		return $toolbar;
	}

	public static function getJumboButtons($inJumbotron=true){
		$buttons=new HtmlButtonGroups("user-buttons",["Create benchmark","All benchmarks"]);
		$buttons->getElement(0)->setColor("green")->setProperty("data-ajax", "Main/benchmark")->addIcon("plus");
		$buttons->getElement(1)->setProperty("data-ajax", "Benchmarks/allTab");

		self::getButtons($buttons,$inJumbotron);
		$buttons->getOnClick("","#main-container",["attr"=>"data-ajax"]);
		return $buttons;
	}

	public static function getButtons(HtmlButtonGroups $buttons,$inJumbotron){
		if(UserAuth::isAuth()){
			$element=$buttons->addElement("My benchmarks");
			$element->setProperty("data-ajax", "Benchmarks/myTab");
		}elseif($inJumbotron){
			$element=$buttons->addElement("Sign in");
			$element->setProperty("data-ajax", "Auth/signin")->addIcon("sign in");

			$element=$buttons->addElement("Sign up");
			$element->setProperty("data-ajax", "Auth/signup")->addIcon("user add");
		}
	}

	public static function displayBenchmarks(JsUtils $jquery,View $view,ControllerBase $controller,$benchMarks,$title,$jsonUrl,$total_rowcount,$count=10){
		$deBenchs=self::createDataBenchmark($jquery,$benchMarks, $jsonUrl);

		if(isset($_POST["p"])){
			$deBenchs->paginate($_POST["p"], $total_rowcount,$count);
			$deBenchs->refresh();
			$s= $jquery->compile($view);
			echo $deBenchs;
			echo $s;
		}else{
			$deBenchs->paginate(1, $total_rowcount,$count);
			$jquery->compile($view);
			$controller->loadView("benchmarks/display.html",["title"=>$title]);
		}
	}

	private static function createDataBenchmark(JsUtils $jquery,$benchMarks,$jsonUrl){
		$deBenchs=$jquery->semantic()->dataTable("deBenchs", "models\Benchmark", $benchMarks);
		$deBenchs->setIdentifierFunction("getId");

		$bt=new HtmlButton("add-benchmark","Create benchmark","green");
		$bt->getOnClick("Main/createBenchmark","#main-container")->addIcon("plus");
		$msg=new HtmlMessage("empty-message",["no benchmark to display&nbsp;",$bt]);
		$msg->setIcon("info circle");

		$deBenchs->setEmptyMessage($msg);
		$deBenchs->setCompact(true);
		$deBenchs->setFields(['stars','name','tests','results','createdAt']);
		$deBenchs->setCaptions(['Stars','Name','Tests','Latest results','Created at','Actions']);

		$deBenchs->setValueFunction("stars", function($str,$bench) use($jquery){
			return GUI::starButton($jquery, $bench);
		});

		$deBenchs->setValueFunction("tests", function($str,$bench){return new HtmlLabel("",\count($bench->getTestcases()));});

		$deBenchs->setValueFunction("results",function($str,$bench){
			$results=Models::getLastResults($bench,true);
			return self::getListResults($results);
		});

		$deBenchs->setValueFunction("name", function($name,$bench) use($jquery){
			$elm=new HtmlSemDoubleElement("name-".$bench->getId(),"div");
			$elm->setContent($name);
			$elm->addPopupHtml(GUI::getBenchmarkName($jquery, $bench),NULL,["setFluidWidth"=>true,"on"=>"click"]);
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
			if(isset($_SESSION["user"]) && $bench->getUser()->getId()!=$_SESSION["user"]->getId())
				$fork->addClass("fork")->asIcon("fork");
				else
					$fork->wrap("<!--","-->");
		});
		$deBenchs->insertInFieldButton(5, "",true,function($see){
			$see->addClass("see")->asIcon("unhide");
		});

		$deBenchs->setUrls(["refresh"=>$jsonUrl,"edit"=>"Main/benchmark","delete"=>"Benchmarks/delete"]);
		$deBenchs->setTargetSelector(["edit"=>"#main-container","delete"=>"#info"]);

		$jquery->getOnClick(".see", "Benchmarks/seeOne","#main-container",["attr"=>"data-ajax"]);
		$jquery->getOnClick(".fork", "Main/fork","#main-container",["attr"=>"data-ajax"]);
		return $deBenchs;
	}

	public static function getListResults($results,$hasImages=true){
		$list=new HtmlList("");
		$list->setHorizontal();
		$count=\count($results);
		$lblFast="";$lblSlow="";
		if($count>0){
			if($count>1){
				$lblFast=(new HtmlLabel(""))->addClass("green empty circular")." ";
				$lblSlow=(new HtmlLabel(""))->addClass("orange empty circular")." ";
			}
			self::addListResult($list, $results[0],$lblFast,$hasImages);
			if($count<3){
				for ($i=1;$i<$count-1;$i++){
					self::addListResult($list, $results[$i],"",$hasImages);
				}
			}else{
				for ($i=1;$i<2;$i++){
					self::addListResult($list, $results[$i],"",$hasImages);
				}
				$list->addItem("...");
			}
			if($count>1){
				self::addListResult($list, $results[$count-1],$lblSlow,$hasImages);
			}
		}
		return $list;
	}

	public static function getResults($results){
		$count=\count($results);
		$result=[];
		$lblFast="";$lblSlow="";
		if($count>0){
			if($count>1){
				$lblFast=(new HtmlLabel(""))->addClass("green empty circular")." ";
				$lblSlow=(new HtmlLabel(""))->addClass("orange empty circular")." ";
			}
			$result[]=self::addResult($results[0],$lblFast);
			if($count>1){
				$result[]=self::addResult($results[$count-1],$lblSlow);
			}
		}
		return \implode("&nbsp;", $result);
	}

	private static function addResult(Result $result,$tag=""){
		return $tag.$result->getTestcase()->getName()."->".Models::getTime($result->getTimer());
	}

	public static function addListResult(HtmlList $list,Result $result,$tag="",$hasImage=true){
		if($hasImage)
			$list->addItem(["image"=>"public/img/".$result->getStatus().".png","header"=>$result->getTestcase()->getName(),"description"=>$tag.Models::getTime($result->getTimer())]);
		else
			$list->addItem(["header"=>$result->getTestcase()->getName(),"description"=>$tag.Models::getTime($result->getTimer())]);
	}
}