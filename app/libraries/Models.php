<?php

namespace libraries;

use models\Benchmark;
use models\Testcase;
use models\Result;
use micro\orm\DAO;

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
	 * @param string $uid
	 * @return Result
	 */
	public static function addResult(Testcase $testcase,$calc,$status,$uid){
		$result=new Result();
		$result->setTimer($calc);
		$result->setStatus($status);
		$result->setUid($uid);
		$testcase->addResult($result);
		return $result;
	}

	public static function getResults(Benchmark $benchmark,$uid,$percent=true){
		$array=[];$return=[];
		$tests=$benchmark->getTestcases();
		foreach ($tests as $test){
			$results=$test->getResults();
			foreach ($results as $result){
				$time=$result->getTimer();
				if($result->getUid()==$uid){
					$array[$test->getName()]=$time;
					if($time!=0 && (!isset($min) || $time<$min))
						$min=$time;
				}
			}
		}
			foreach ($array as $k=>$v){
				if($percent)
					$return[]="['".$k."',".($v/$min*100)."]";
				else
					$return[]="['".$k."',".$v."]";
			}
		return "[".\implode(",", $return)."]";
	}

	public static function getLastResults(Benchmark $benchmark,$percent=true){
		$tests=$benchmark->getTestcases();
		if(\count($tests)>0){
			$uid=self::getLastResultUid($tests[0]);
			if($uid!==NULL){
				return DAO::getAll("models\Result","uid='".$uid."' ORDER BY status DESC,timer ASC");
			}
		}
		return [];
	}

	private static function getLastResultUid(Testcase $test){
		$result=DAO::getOne("models\Result", "idTestcase=".$test->getId()." order by CreatedAt DESC");
		if(isset($result))
			return $result->getUid();
		return null;
	}

	public static function save($benchmark){
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
			if($test->getCreatedAt()!=NULL)
				DAO::update($test);
				else{
					$test->setId(null);
					DAO::insert($test);
					$test->setCreatedAt(\date("Y-m-d H:i:s"));
				}
				foreach ($test->getResults() as $result){
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
}