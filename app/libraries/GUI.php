<?php

namespace libraries;

use Ajax\common\Widget;
use Ajax\JsUtils;
use Ajax\semantic\html\base\HtmlSemCollection;
use Ajax\semantic\html\elements\HtmlButton;
use models\Benchmark;
use models\Execution;
use models\Testcase;
use Ajax\semantic\html\elements\HtmlButtonGroups;
use Ajax\semantic\html\collections\HtmlMessage;
use Ajax\semantic\html\elements\HtmlLabel;
use Ajax\semantic\html\elements\HtmlList;
use models\Result;
use Ajax\semantic\html\base\HtmlSemDoubleElement;
use Ubiquity\views\View;
use controllers\ControllerBase;

class GUI {

	const ICONS=["output"=>"history","success"=>"checkmark box","info"=>"info circle","warning"=>"warning circle","error"=>"announcement"];
	const COLORS=["output"=>"","success"=>"green","info"=>"blue","warning"=>"orange","error"=>"red"];

    public static $style='';

    public static function setStyle(HtmlSemDoubleElement|HtmlSemCollection|Widget $element){
        if(self::$style==='inverted'){
            $element->setInverted(true);
        }
    }

	public static function showSimpleMessage(JsUtils $jquery,$content,$type,$icon="info circle",$timeout=NULL){
		$semantic=$jquery->semantic();
		$message=$semantic->htmlMessage("msg-".rand(0,50),$content,$type);
		$message->setIcon($icon);
		$message->setDismissable();
		if(isset($timeout))
			$message->setTimeout(3000);
        self::setStyle($message);
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
					if(is_object($output)){
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
					}else{
						self::showRunningMessage($jquery,$obj->content,$obj->type,$form."-".$i);
					}
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
		$bt->addLabel(Models::countFork($benchmark))->setPointing('left')->getOnClick('Benchmarks/forks/'.$id,'#main-container',['hasLoader'=>'internal']);
		$bt->getOnClick('Main/fork/'.$id,'#main-container',['hasLoader'=>'internal']);
		return $bt;
	}

	public static function getBenchmarkName(JsUtils $jquery,Benchmark $benchmark,$recursive=true){
		$name=Models::getBenchmarkName($benchmark,$recursive);
		$header=$jquery->semantic()->htmlHeader("h-name",4);
		if(\count($name)>1){
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
		$bt=$jquery->semantic()->htmlButton('bt-star-'.$id)->setProperty('title', 'Star');
		$bt->addIcon('star')->setColor(($stared)?'green':'');
		$bt->addLabel(Models::countStar($benchmark))->setPointing('left')->getOnClick('Benchmarks/stars/'.$id,'#main-container',['hasLoader'=>'internal']);
		if(UserAuth::isAuth())
			$bt->getOnClick('Main/'.(($stared)?'unstar':'star').'/'.$id,'#bt-star-'.$id,['jqueryDone'=>'replaceWith']);
		return $bt;
	}

	public static function getToolbar(JsUtils $jquery,$user,Benchmark $benchmark){
		$toolbar=$jquery->semantic()->htmlButtonGroups('toolBar');
		$idBenchmark=$benchmark->getId();

		if(isset($user)){
			if($user->getId()==$benchmark->getUser()->getId()){
				$toolbar->addItem('Run test cases')->getOnClick('Benchmarks/run/'.$idBenchmark,'#testTerminate',['hasLoader'=>'internal'])->addClass('teal')->addIcon('lightning');
				$toolbar->addItem('Update')->getOnClick('Main/benchmark/'.$idBenchmark,'#main-container',['hasLoader'=>'internal'])->addIcon('edit');
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
        self::setStyle($buttons);
		$buttons->getOnClick('','#main-container',['attr'=>'data-ajax','hasLoader'=>'internal']);
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
		$bt->getOnClick("Main/createBenchmark","#main-container",['hasLoader'=>'internal'])->addIcon("plus");
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
			$list= self::getListResults($results);
			$list->addClass("see");
			$list->setProperty("data-ajax", $bench->getId());
			return $list;
		});

		$deBenchs->setValueFunction("name", function($name,$bench) use($jquery){
			$elm=new HtmlSemDoubleElement("name-".$bench->getId(),"div");
			$lbl=self::getPhpVersion($bench->getPhpVersion());
			$elm->setContent($name.$lbl);
			$elm->addPopupHtml(GUI::getBenchmarkName($jquery, $bench),NULL,["setFluidWidth"=>true,"on"=>"click"]);
			return $elm;
		});

		$deBenchs->setValueFunction("createdAt", function($time){return Models::time_elapsed_string($time,false);});

		$deBenchs->addEditDeleteButtons(true,['hasLoader'=>'internal'],function($edit,$bench){
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
		$jquery->getOnClick('.see', 'Benchmarks/seeOne','#main-container',['attr'=>'data-ajax','hasLoader'=>'internal']);
		$jquery->getOnClick('.fork', 'Main/fork','#main-container',['attr'=>'data-ajax','hasLoader'=>'internal']);
		return $deBenchs;
	}

	public static function getPhpVersion($phpVersion,$testcase=false){
		$phpVersion=Models::getPhpVersion($phpVersion);
		$lbl="";
		if(isset($phpVersion) &&(!$testcase || $phpVersion!==Models::$DEFAULT_PHP_VERSION)){
			$lbl=new HtmlLabel("",Models::$PHP_VERSIONS[$phpVersion]);
			$lbl->setSize("mini");
			$lbl=" ".$lbl;
		}
		return $lbl;
	}

	public static function getListResults($results,$hasImages=true,$max=3){
		$list=new HtmlList("");
		$list->setHorizontal();
		$count=\count($results);
		if($count>0){
			$results=\array_values($results);

			if($count<$max){
				for ($i=0;$i<$count-1;$i++){
					self::addListResult($list, $results[$i],self::getLblNote($results[$i]),$hasImages);
				}
			}else{
				for ($i=0;$i<$max-1;$i++){
					self::addListResult($list, $results[$i],self::getLblNote($results[$i]),$hasImages);
				}
				if($count>$max)
					$list->addItem("...");
			}
			if($count>1){
				self::addListResult($list, $results[$count-1],self::getLblNote($results[$count-1]),$hasImages);
			}
		}
		return $list;
	}

	public static function getResults($results){
		$count=\count($results);
		$result=[];
		if($count>0){
			$results=\array_values($results);
			$result[]=self::addResult($results[0],self::getLblNote($results[0]));
			if($count>1){
				$result[]=self::addResult($results[$count-1],self::getLblNote($results[$count-1]));
			}
		}
		return \implode("&nbsp;", $result);
	}

	public static function getLblNote(Result $result,$small=true){
		$note=$result->getNote();
		if($note<=0)
			$note="?";
		else
			$note=chr($note+64);
		if($small){
			$lbl=new HtmlLabel("lblNote-".$result->getId());
			$lbl->addClass("empty circular note note".$note);
		}else{
			$lbl=new HtmlLabel("lblNote-".$result->getId(),$note);
			$lbl->addClass("circular note note".$note);
		}
		return $lbl;
	}

	private static function addResult(Result $result,$tag=""){
		return $tag.$result->getTestcase()->getName()."->".Models::getTime($result->getTimer());
	}

	public static function addListResult(HtmlList $list,Result $result,$tag="",$hasImage=true){
		$lblVersion=self::getPhpVersion($result->getPhpVersion(),true);
		if($hasImage)
			$list->addItem(["image"=>"img/".$result->getStatus().".png","header"=>$result->getTestcase()->getName(),"description"=>$tag." ".Models::getTime($result->getTimer()).$lblVersion]);
		else
			$list->addItem(["header"=>$result->getTestcase()->getName(),"description"=>$tag." ".Models::getTime($result->getTimer())]);
	}

}