<?php

namespace libraries;

use models\Benchmark;
use models\Testcase;
use models\Result;
use micro\orm\DAO;
use models\Execution;
use models\User;
use micro\utils\JArray;

class Models {
	const TIME_UNITS=[" "=>1,"m"=>0.001,"µ"=>0.000001,"n"=>0.000000001];
	/**
	 * Adds a new testcase in $benchmark and returns the count of tests
	 * @param Benchmark $benchmark
	 * @param string $name
	 * @param string $code
	 * @return Testcase
	 */
	public static function addTest(Benchmark $benchmark,$name=NULL,$code=""){
		$test=new Testcase();
		$id=$benchmark->nextTestCaseId();
		$test->setId($id);
		$benchmark->addTestcase($test);
		if(!isset($name)){
			$name="test #".$id;
		}
		$test->setCode($code);
		$test->setName($name);
		return $test;
	}

	/**
	 * @param Testcase $testcase
	 * @param double $calc
	 * @param string $status
	 * @return Result
	 */
	public static function addResult(Execution &$execution,Testcase $testcase,$calc,$status){
		$result=new Result();
		$result->setTimer($calc);
		$result->setStatus($status);
		$result->setTestcase($testcase);
		$execution->addResult($result);
		return $result;
	}

	public static function getResults(Execution $execution,$percent=true){
		$results=$execution->getResults();
		return self::getChartResults($results,$percent);
	}

	public static function getChartResults($results,$percent=true){
		$array=[];$return=[];
		foreach ($results as $result){
			$time=$result->getTimer();
			$array[$result->getTestcase()->getName()]=$time;
			if($time!=0 && (!isset($min) || $time<$min))
				$min=$time;
		}
		foreach ($array as $k=>$v){
			if(isset($min) && $percent)
				$return[]="['".$k."',".($v/$min*100)."]";
			else
				$return[]="['".$k."',".$v."]";
		}
		return "[".\implode(",", $return)."]";
	}

	public static function getBenchmarkName(Benchmark $benchmark,$recursive=true){
		$return=[];
		$result=$benchmark->getName();
		$user=$benchmark->getUser();
		if($user instanceof User){
			$result=$user->getLogin()."/".$result;
		}
		$return[]=$result;
		if($benchmark->getIdFork()!=NULL && $recursive){
			$forked=DAO::getOne("models\Benchmark", $benchmark->getIdFork());
			if(isset($forked))
				$return[]=self::getBenchmarkName($forked,false)[0];
		}
		return $return;
	}

	public static function getUserName($user){
		$result=$user->getLogin();
		if($user->getAuthProvider()!=null)
			$result.="@".$user->getAuthProvider()->getName();
		return $result;
	}

	public static function getLastResults(Benchmark $benchmark,$percent=true){
		$executions=$benchmark->getExecutions();
		$execution=self::getLastExecution($executions);
		if($execution!==NULL){
			return DAO::getAll("models\Result","idExecution='".$execution->getId()."' ORDER BY status DESC,timer ASC");
		}
		return [];
	}

	public static function getTestIds(Benchmark $benchmark){
		$result=[];
		foreach ($benchmark->getTestcases() as $test){
			$result[]=$test->getId();
		}
		return $result;
	}

	/**
	 * @param array $executions
	 * @return Execution
	 */
	private static function getLastExecution(array $executions){
		$last=null;
		$max=0;
		foreach ($executions as $execution){
			if($execution->getCreatedAt()>$max){
				$max=$execution->getCreatedAt();
				$last=$execution;
			}
		}
		return $last;
	}

	public static function countFork(Benchmark $benchmark){
		return DAO::count("models\Benchmark","idFork=".$benchmark->getId());
	}

	public static function countStar($benchmark){
		if($benchmark instanceof Benchmark)
			$id=$benchmark->getId();
		else{
			$id=$benchmark;
			}
		return DAO::$db->count('benchstar',"idBenchmark=".$id);
	}

	public static function stared($benchmark){
		if($benchmark instanceof Benchmark)
			$id=$benchmark->getId();
			else{
				$id=$benchmark;
			}
		$where="idBenchmark=".$id;
		if(UserAuth::isAuth()){
			$where.=" AND idUser=".UserAuth::getUser()->getId();
		}
		return DAO::$db->count('benchstar',$where)==1;
	}

	public static function save(Benchmark $benchmark){
		$user=UserAuth::getUser();
		$benchmark->setUser($user);
		if($benchmark->getCreatedAt()!=NULL){
			DAO::update($benchmark);
		}else{
			$benchmark->setId(NULL);
			DAO::insert($benchmark,true);
			$benchmark->setCreatedAt(\date("Y-m-d H:i:s"));
		}
		foreach ($benchmark->getTestcases() as $test){
			if($test->getCreatedAt()!=NULL){
				DAO::update($test);
			}else{
				$test->setId(null);
				DAO::insert($test);
				$test->setCreatedAt(\date("Y-m-d H:i:s"));
			}
		}
		foreach ($benchmark->getToDelete() as $testToDelete){
			DAO::remove($testToDelete);
		}
		foreach ($benchmark->getExecutions() as $execution){
			if($execution->getCreatedAt()!=NULL)
				DAO::update($execution);
			else{
				$execution->setId(NULL);
				DAO::insert($execution);
				$execution->setCreatedAt(\date("Y-m-d H:i:s"));
			}
			foreach ($execution->getResults() as $result){
				if($result->getCreatedAt()!=NULL)
					DAO::update($result);
				else{
					$result->setId(null);
					DAO::insert($result);
					$result->setCreatedAt(\date("Y-m-d H:i:s"));
				}
			}
		}
	}

	public static function getTime($time){
		foreach (self::TIME_UNITS as $unit=>$value){
			$v=\number_format($time/$value,4);
			if($v>.01)
				return $v." ".$unit."s";
		}
		return $time;
	}

	/**
	 * @param string $datetime
	 * @param boolean $full
	 * @return string
	 * @see http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago
	 */
	public static function time_elapsed_string($datetime, $full = false) {
		$now = new \DateTime();
		$ago = new \DateTime($datetime);
		$diff = $now->diff($ago);

		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;

		$string = array(
				'y' => 'year',
				'm' => 'month',
				'w' => 'week',
				'd' => 'day',
				'h' => 'hour',
				'i' => 'minute',
				's' => 'second',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}

		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}

	public static function replaceAll($array,$subject){
		array_walk($array, function(&$item){if(is_array($item)) $item=implode("\n", $item);});
		return str_replace(array_keys($array), array_values($array), $subject);
	}

	public static function openReplaceWrite($source,$destination,$keyAndValues){
		$str=\file_get_contents($source);
		$str=self::replaceAll($keyAndValues,$str);
		return \file_put_contents($destination,$str);
	}

	public static function getJsonBenchmarks($page,$condition="1=1",$count=15){
		$benchmarks=DAO::getAll("models\Benchmark",$condition." limit ".(($page-1)*$count).",".$count);
		print_r(JArray::toArray($benchmarks));
	}
}