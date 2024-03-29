<?php

namespace libraries;

use models\Authprovider;
use models\Benchmark;
use models\Testcase;
use models\Result;
use Ubiquity\orm\DAO;
use models\Execution;
use models\User;
use Ubiquity\utils\base\UArray;

class Models {
	public static $PHP_VERSIONS=["8.2.13"=>"Default 8.2"];
	public static $DEFAULT_PHP_VERSION="8.2.13";
	public static $NOTES=['#152D39','#328C9B','#5CB8CD','#FB8A52','#DD7547','#BB0D48','#9E1248'];

	const TIME_UNITS=[" "=>1,"m"=>0.001,"µ"=>0.000001,"n"=>0.000000001];
	/**
	 * Adds a new testcase in $benchmark and returns the count of tests
	 * @param Benchmark $benchmark
	 * @param string $name
	 * @param string $code
	 * @return Testcase
	 */
	public static function addTest(Benchmark $benchmark,$name=NULL,$code="",$phpVersion=null){
		$test=new Testcase();
		$id=$benchmark->nextTestCaseId();
		$test->setId($id);
		$benchmark->addTestcase($test);
        if(isset($phpVersion)) {
            $test->setPhpVersion($phpVersion);
        }
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
        $result->setExecution($execution);
        $result->setUid($execution->getUid());
		return $result;
	}

	public static function getResults(Execution $execution,$percent=true){
		$results=$execution->getResults();
        self::sortResults($results);
        $index=1;
        foreach ($results as $result) {
            $result->setNote($index++);
        }
		return self::getChartResults($results,$percent);
	}

	public static function getChartResults($results,$percent=true){
		$array=[];$return=[];
		foreach ($results as $result){
			$time=$result->getTimer();
            $color=self::$NOTES[$result->getNote()-1]??'black';
			$array[$result->getTestcase()->getName()]=['time'=>$time,'color'=>$color];
			if($time!=0 && (!isset($min) || $time<$min))
				$min=$time;
		}
		foreach ($array as $k=>$values){
            $v=$values['time'];
            $c=$values['color'];
			if(isset($min) && $percent)
				$return[]="['$k',".($v/$min*100).",'$c']";
			else
				$return[]="['$k',".$v.",'$c']";
		}
		return "[".\implode(",", $return)."]";
	}

	public static function getBenchmarkName(Benchmark $benchmark,$recursive=true,$noLink=false){
		$return=[];
		$result=$benchmark->getName();
		$user=$benchmark->getUser();
		if($user instanceof User){
            if($noLink){
                $result = $user->getLogin() . "/" . $result;
            }else {
                $result = '<a href="#" class="user-click" data-ajax="' . $user->getId() . '">' . $user->getLogin() . "</a>/" . $result;
            }
		}
		$return[]=$result;
		if($benchmark->getIdFork()!=NULL && $recursive){
			$forked=DAO::getById(Benchmark::class, $benchmark->getIdFork());
			if(isset($forked))
				$return[]=self::getBenchmarkName($forked,false)[0];
		}
		return $return;
	}

	public static function getUserName($user){
		$result=$user->getLogin();
		if($user->getAuthProvider() instanceof Authprovider)
			$result.="@".$user->getAuthProvider()->getName();
		return $result;
	}

	public static function getLastResults(Benchmark $benchmark,$percent=true){
		$executions=$benchmark->getExecutions();
		$execution=self::getLastExecution($executions);
		if($execution!==NULL){
			return DAO::getAll(Result::class,"idExecution='".$execution->getId()."' ORDER BY status DESC,timer ASC");
		}
		return [];
	}

	public static function getLastBenchmark($idDomain,$sqlMy=""){
		return DAO::getOne(Benchmark::class, "INSTR(`domains`, '".$idDomain."')>0".$sqlMy." ORDER BY createdAt DESC LIMIT 1 OFFSET 0",true,true);
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

    public static function getExecutionByUid(Benchmark $bench,string $uid){
        $executions=$bench->getExecutions();
        foreach($executions as $exec){
            if($exec->getUid()==$uid){
                return $exec;
            }
        }
        return null;
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
        $db=DAO::getDb(Benchmark::class);
		return $db->count('benchstar',"idBenchmark=".$id);
	}

	public static function stared($benchmark){
        if(UserAuth::isAuth()) {
            if ($benchmark instanceof Benchmark)
                $id = $benchmark->getId();
            else {
                $id = $benchmark;
            }
            $where = "idBenchmark=" . $id . " AND idUser=" . UserAuth::getUser()->getId();
            $db = DAO::getDb(Benchmark::class);
            return $db->count('benchstar', $where) == 1;
        }
        return false;
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
			$results=$execution->getResults();
			self::sortResults($results);
			$index=1;
			foreach ($results as $result){
				$result->setPhpVersion(self::getTestPhpVersion($benchmark, $result->getTestcase()));
				$result->setNote($index++);
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

	public static function sortResults(&$results){
		return \usort($results,function ($result1, $result2) {
			$n1=$result1->getTimer();
			$n2=$result2->getTimer();
			if ($n1 == $n2) {
				return 0;
			}
			return ($n1 < $n2) ? -1 : 1;
		});
	}

	public static function getPhpVersion($phpVersion){
		if(isset($phpVersion) && isset(self::$PHP_VERSIONS[$phpVersion])){
			return $phpVersion;
		}
		return null;
	}

	public static function getTestPhpVersion(Benchmark $benchmark,Testcase $test){
		$phpVersion=$test->getPhpVersion();
		if(!isset(self::$PHP_VERSIONS[$phpVersion])){
			$phpVersion=$benchmark->getPhpVersion();
		}
		return self::getPhpVersion($phpVersion);
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
		print_r(UArray::toArray($benchmarks));
	}
}