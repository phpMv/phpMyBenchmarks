<?php
namespace controllers;
use Ajax\semantic\html\base\constants\TextAlignment;
use libraries\MySettings;
use models\User;
use Ubiquity\orm\DAO;
use Ajax\semantic\html\elements\HtmlLabel;
use libraries\Models;
use models\Result;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButton;
use libraries\GUI;
use models\Benchmark;
use models\Testcase;
use libraries\ServerExchange;
use models\Execution;
use Ajax\semantic\html\elements\html5\HtmlLink;
use models\Domain;
use Ajax\semantic\html\content\HtmlListItem;

 /**
 * Controller Benchmarks
 **/
class Benchmarks extends ControllerBase{

	const COLORS=["success"=>"green","info"=>"blue","warning"=>"orange","error"=>"red"];

	public function index(){}

    public function initialize()
    {
        $this->initializeAll();
    }

	public function finalize(){
	}

	public function all(){
		$benchmarks=DAO::getAll("models\Benchmark","1=1 ORDER BY createdAt DESC".$this->getLimitOffset());
		GUI::displayBenchmarks($this->jquery,$this->view,$this,$benchmarks,null,"Benchmarks/all",DAO::count("models\Benchmark"));
	}

    private function allTabs($title,$action,$paramBenchmarks=[],$paramDomains=[]){
        $tab=$this->semantic->htmlTab("allTabs");
        $tab->addAndForwardTab($this->jquery, $title, $this, "controllers\\Benchmarks", $action,$paramBenchmarks);
        $tab->addAndForwardTab($this->jquery, "By category", $this, "controllers\\Benchmarks", "domains",$paramDomains);
        $this->jquery->renderView("benchmarks/allTabs.html");
    }

	public function allTab(){
        $this->allTabs('All benchmarks','all');
	}

	public function myTab(){
        $this->allTabs('My benchmarks','my',[],['my']);
	}

    public function userTab($id){
        $user=DAO::getById(User::class,$id,false);
        if($user!==null){
            $image = $user->getAvatar();
            $image = ($image != null && $image != "") ? $image : "img/male.png";
            $lbl = new HtmlLabel('', Models::getUserName($user)."'s benchmarks");
            $lbl->addClass('basic');
            $lbl->addAvatarImage($image, "", true)->addClass('spaced');
            $this->allTabs($lbl,'user',[$id],[$id]);
        }
    }

	public function domains($my=""){
		$useMy=$my=="my" && UserAuth::isAuth();
		$sqlMy="";
		if($useMy){
			$user=UserAuth::getUser();
			$sqlMy=" AND idUser=".$user->getId();
		}elseif (\ctype_digit($my)){
            $sqlMy=" AND idUser=".$my;
        }
		$domains=DAO::getAll("models\Domain");
		$list=$this->semantic->htmlList("listDomains");
		$list->fromDatabaseObjects($domains, function($domain) use($sqlMy,$my){
			$count=DAO::count("models\Benchmark","INSTR(`domains`, '".$domain->getId()."') > 0".$sqlMy);

			$item=new HtmlListItem("domain-".$domain->getId());
			$item->setIcon("line chart");
			$desc="no benchmark";
			if($count>0){
				$last=Models::getLastBenchmark($domain->getId(),$sqlMy);
				$results=Models::getLastResults($last,true);
				$desc="<b>".$last->getName()."</b>&nbsp;".GUI::getResults($results);
			}
			$item->setTitle($domain->getName()." (".$count.")",$desc,"header");
			$item->getOnClick("Benchmarks/benchmarksCat/".$domain->getId()."/".$my,"#tabBenchmarks",["jsCallback"=>"$('#listDomains').hide();$('#tabBenchmarks').show();"]);
		return $item;
		});
		$list->addClass("selection relaxed");
        $this->setStyle($list);
		echo $list;
		echo '<div id="tabBenchmarks" style="display: none;"></div>';
		echo $this->jquery->compile($this->view);
	}

	public function benchmarksCat($idDomain,$my=""){
		$domaine=DAO::getById(Domain::class, $idDomain);
		$bt=$this->semantic->htmlButton("return","Close");
		$bt->addIcon("close");
		$title=$domaine->getName();
		$useMy=$my=="my" && UserAuth::isAuth();
		$where="INSTR(`domains`, '".$idDomain."') > 0";
		$user=null;
		if($useMy){
			$user=UserAuth::getUser();
			$where.=" AND idUser=".$user->getId();
			$title="My benchmarks : ".$title;
		}elseif (\ctype_digit($my)){
            $user=DAO::getById(User::class,$my,false);
            $name=$user->getLogin();
            $where.=" AND idUser=".$user->getId();
            $title="$name's benchmarks : ".$title;
        }
		$bt->addLabel($title,false);
		$bt->onClick("$('#tabBenchmarks').hide();$('#listDomains').show();");
		$benchmarks=DAO::getAll("models\Benchmark",$where." ORDER BY createdAt DESC".$this->getLimitOffset(),true,true);
		GUI::displayBenchmarks($this->jquery,$this->view,$this,$benchmarks,$bt,"Benchmarks/benchmarksCat/".$idDomain."/".$my,DAO::count("models\Benchmark",$where));
	}

	public function my(){
		$benchmarks=DAO::getAll("models\Benchmark","idUser=".UserAuth::getUser()->getId()." ORDER BY createdAt DESC".$this->getLimitOffset());
		GUI::displayBenchmarks($this->jquery,$this->view,$this,$benchmarks,null,"Benchmarks/my",DAO::count("models\Benchmark","idUser=".UserAuth::getUser()->getId()));
	}

    public function user(int $id){
        $benchmarks=DAO::getAll("models\Benchmark","idUser=".$id." ORDER BY createdAt DESC".$this->getLimitOffset());
        GUI::displayBenchmarks($this->jquery,$this->view,$this,$benchmarks,null,"Benchmarks/user/".$id,DAO::count("models\Benchmark","idUser=".$id));
    }

	private function getLimitOffset($count=10){
		$p=$_POST['p']??1;
		return " LIMIT ".(($p-1)*$count).",".$count;
	}

	public function delete($ids){
		$instance=DAO::getById(Benchmark::class,$ids);
		$instanceString=$instance."";
			if(count($_POST)>0){
				if(DAO::remove($instance)){
					$message=$this->showSimpleMessage("Deletion","Deletion of `".$instanceString."`", "info","info",4000);
					$this->jquery->exec("$('tr[data-ajax={$ids}]').remove();",true);
				}else{
					$message=$this->showSimpleMessage('Deletion',"Unable to delete `".$instanceString."`", "warning","warning");
				}
			}else{
				$message=$this->showConfMessage('Deletion',"Confirm the deletion of `".$instanceString."`?", "", "Benchmarks/delete/{$ids}", "#info", $ids,['hasLoader'=>'internal']);
			}
			$this->jquery->renderComponent($message);
	}

	public function seeOne($idBenchmark){
        $_SESSION['activeBenchmark']=true;
		$user=UserAuth::getUser();
		$benchmark=DAO::getById(Benchmark::class, $idBenchmark);
		GUI::getBenchmarkTop($this->jquery,$benchmark,$user);

        $aceTheme=MySettings::getAceTheme();

		$header1=$this->semantic->htmlHeader("header1",3,"Code");
		$header1->addIcon("code");
		$tests=DAO::getOneToMany($benchmark, "testcases");
		$this->jquery->exec("setAceEditor('preparation',true,'$aceTheme');",true);
		$testsView="";
		foreach ($tests as $test){
			$testsView.=$this->loadView("benchmarks/seeOneTest.html",["id"=>$test->getId(),"name"=>$test->getName(),"code"=>$test->getCode()],true);
			$this->jquery->exec("setAceEditor('code-".$test->getId()."',true,'$aceTheme');",true);
		}

		$header2=$this->semantic->htmlHeader("header2",3,"Executions");
		$header2->addIcon("lightning");

		$header3=$this->semantic->htmlHeader("header3",3,"Graph");
		$header3->addIcon("bar chart");
		$this->listExecs($benchmark);

		$this->jquery->exec("$('.ui.accordion').accordion({'exclusive': false});",true);
		$this->jquery->exec("google.charts.load('current', {'packages':['corechart']});",true);

		$this->jquery->renderView("benchmarks/seeOne.html",["benchmark"=>$benchmark,"tests"=>$testsView]);
	}

	public function listExecs($benchmark){
		$user=UserAuth::getUser();
		$executions=DAO::getAll("models\Execution", "idBenchmark=".$benchmark->getId()." ORDER BY createdAt DESC");
		$listExecs=$this->semantic->dataTable("list-executions", "models\Execution", $executions);
		$listExecs->setIdentifierFunction("getId");
		$listExecs->setFields(["uid","createdAt","results"]);
		$listExecs->setCaptions(["uid","When created","Results","Actions"]);
		$listExecs->setIdentifierFunction("getId");
		$listExecs->setValueFunction("createdAt", function($str){ return Models::time_elapsed_string($str);});
		$listExecs->setValueFunction("uid", function($str){ return \substr($str,0,7);});

		$listExecs->setValueFunction("results",function($str,$exec){
			$results=DAO::getAll("models\Result", "idExecution=".$exec->getId()." ORDER BY timer ASC");
			$list=GUI::getListResults($results,true,7);
			return $list;
		});
		$listExecs->setActiveRowSelector("error");
		if($user!=null && $user->getId()==$benchmark->getUser()->getId()){
			$listExecs->addDeleteButton(true,['hasLoader'=>'internal']);
			$listExecs->setUrls(["delete"=>"Benchmarks/deleteResult"]);
			$listExecs->setTargetSelector(["delete"=>"#info"]);
		}
        $listExecs->onPreCompile(function ($dt) {
            $dt->setColAlignment(3, TextAlignment::RIGHT);
        });
		$listExecs->getOnRow("click", "Benchmarks/seeExecutionResults","#ajax",["attr"=>"data-ajax","hasLoader"=>false,"ajaxTransition"=>"random"]);
        return $listExecs;
	}

	public function deleteResult($id){
		$execution=DAO::getById(Execution::class, $id);
		if($execution!=null){
			DAO::remove($execution);
			$msg=GUI::showSimpleMessage($this->jquery, "Result deleted", "info","info circle",5000);
			$this->jquery->exec("$('tr[data-ajax={$id}]').remove();",true);
		}
		$this->jquery->renderComponent($msg);
	}

	public function seeExecutionResults($idExecution){
		$execution=DAO::getById(Execution::class, $idExecution);
		$results=DAO::getOneToMany($execution, "results");
		foreach ($results as $result){
			$testId=$result->getTestcase()->getId();
			$this->jquery->get("Benchmarks/seeResult/".$result->getId()."/".$testId,"#result-".$testId,["hasLoader"=>false,"ajaxTransition"=>"random"]);
		}
		$this->jquery->exec("drawChart('".substr($execution->getUid(),0,7)."',".Models::getChartResults($results,true).",'graph');",true);

		echo $this->jquery->compile();
	}

	public function seeResult($idResult,$idTest){
		$result=DAO::getById(Result::class, $idResult);
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
		$this->jquery->exec('$("#note-'.$idTest.'").html(\''.GUI::getLblNote($result,false).'\');',true);
		$this->jquery->exec('$("#php-'.$idTest.'").html(\''.GUI::getPhpVersion($result->getPhpVersion(),true).'\');',true);
		$this->jquery->renderComponent($bt);
	}

	public function seeChart($idExecution){
		$execution=DAO::getById(Execution::class, $idExecution);
		$results=DAO::getOneToMany($execution, "results");
		$this->jquery->exec("drawChart('".substr($execution->getUid(),0,7)."',".Models::getChartResults($results,true).",'graph');",true);
		echo $this->jquery->compile();
	}

	public function run($idBenchmark){
        if(!$_SESSION['activeBenchmark']??false){
            $this->forward(Benchmarks::class,'seeOne',[$idBenchmark],true,true);
        }
        $_SESSION['activeBenchmark']=true;
		$benchmark=DAO::getById(Benchmark::class, $idBenchmark);
		DAO::getOneToMany($benchmark, "testcases");
		$execution=$benchmark->addExecution(\md5(\microtime(true)));
		$_SESSION["execution"]=$execution;
		$testsIds=Models::getTestIds($benchmark);
		$_SESSION["testsIds"]=$testsIds;
		if(\count($testsIds)>0){
			$this->jquery->get("Benchmarks/runTest/".$testsIds[0],"#result-".$testsIds[0],['hasLoader'=>false]);
		}
		echo $this->jquery->compile();
	}

	public function runTest($id){
        $_SESSION['activeBenchmark']=true;
		$isWin=\strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
		$prefix=($isWin)?"":ROOT.DS."..".DS."server".DS;
		$test=DAO::getById(Testcase::class, $id);
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
		if(\count($_SESSION["testsIds"])>0){
			$testsIds=$_SESSION["testsIds"];
			$this->jquery->get("Benchmarks/runTest/".$testsIds[0],"#result-".$testsIds[0],['hasLoader'=>false]);
		}else{
			$this->jquery->get("Benchmarks/testTerminate","#list-executions",['hasLoader'=>false,"ajaxTransition"=>"random","jqueryDone"=>"replaceWith"]);
		}
		echo $this->jquery->compile();
	}

	public function testTerminate(){
        $_SESSION['activeBenchmark']=true;
		$execution=$_SESSION["execution"];
		DAO::insert($execution);
		$benchmark=$execution->getBenchmark();
		$results=$execution->getResults();
		Models::sortResults($results);
		$index=1;
		foreach ($results as $result){
			$result->setNote($index++);
            $result->setUid($execution->getUid());
            $result->setExecution($execution);
			$result->setPhpVersion(Models::getTestPhpVersion($benchmark, $result->getTestcase()));
			DAO::insert($result);
		}
        $this->jquery->exec("drawChart('".substr($execution->getUid(),0,7)."',".Models::getResults($execution).",'graph');",true);

		$this->jquery->renderComponent($this->listExecs($benchmark));
	}

	public function stars($idBenchmark){
        $_SESSION['activeBenchmark']=false;
        $benchmark=DAO::getById(Benchmark::class, $idBenchmark);
		GUI::getBenchmarkTop($this->jquery, $benchmark,UserAuth::getUser());
		$this->semantic->htmlHeader("bench-header",2,"Stargazers");
		$userstars=DAO::getManyToMany($benchmark, "users");
        if(\count($userstars)>0) {
            $list = $this->semantic->htmlCardGroups("list-user-stars");
            $list->fromDatabaseObjects($userstars, function ($user) use ($list) {
                $image = $user->getAvatar();
                $image = ($image != null && $image != "") ? $image : "img/male.png";
                $card = $list->newItem("card-" . $user->getId());
                $lbl = new HtmlLabel('', Models::getUserName($user));
                $lbl->setBasic()->setProperty('data-ajax', $user->getId())->addClass('user-click');
                $card->addItemContent($lbl)->addImage($image, "", "mini")->setFloated("left")->asAvatar();
                return $card;

            });
        } else {
            GUI::showSimpleMessage($this->jquery,'No stargazers for this benchmark!','','info circle',null,'Stargazers','list-user-stars');
        }
		$this->jquery->renderView('benchmarks/fork-stars.html');
	}

	public function forks($idBenchmark){
        $_SESSION['activeBenchmark']=false;
        $benchmark=DAO::getOne("models\Benchmark", $idBenchmark);
		GUI::getBenchmarkTop($this->jquery, $benchmark,UserAuth::getUser());
		$this->semantic->htmlHeader("bench-header",2,"Forks");
		$forks=DAO::getAll("models\Benchmark","idFork=".$idBenchmark);
        if(\count($forks)>0) {
            $list = $this->semantic->htmlCardGroups("list-user-stars");
            $list->fromDatabaseObjects($forks, function ($bench) use ($list) {
                $card = $list->newItem("card-" . $bench->getId());
                $seg = new HtmlLink("forked-" . $bench->getId(), "#", Models::getBenchmarkName($bench, false));
                $seg->getOnClick("Benchmarks/seeOne/" . $bench->getId(), "#main-container");
                $seg->addIcon("fork");
                $card->addItemContent($seg);
                return $card;

            });
        }else{
            GUI::showSimpleMessage($this->jquery,'This benchmark has never been forked!','','info circle',null,'Forks','list-user-stars');
        }
        $this->jquery->renderView('benchmarks/fork-stars.html');
	}

	private function showSimpleMessage($title,$content,$type,$icon="info",$timeout=NULL){
		$semantic=$this->jquery->semantic();
		$message=$semantic->htmlMessage("msg-".rand(0,50),$content,$type);
        $message->addHeader($title);
		$message->setIcon($icon." circle");
		$message->setDismissable();
		if(isset($timeout))
			$message->setTimeout(3000);
		return $message;
	}

	private function showConfMessage($title,$content,$type,$url,$responseElement,$data,$attributes=NULL){
		$messageDlg=$this->showSimpleMessage($title,$content, $type,"help circle");
		$btOkay=new HtmlButton("bt-okay","Confirm","positive");
		$btOkay->addIcon("check circle");
		$btOkay->postOnClick($url,"{data:'".$data."'}",$responseElement,$attributes);
		$btCancel=new HtmlButton("bt-cancel","Cancel","negative");
		$btCancel->addIcon("remove circle outline");
		$btCancel->onClick($messageDlg->jsHide());

		$messageDlg->addContent(['<div class="ui divider"></div>',$btOkay,$btCancel]);
		return $messageDlg;
	}

}