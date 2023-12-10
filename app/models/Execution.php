<?php
namespace models;

use Ubiquity\attributes\items\Id;
use Ubiquity\attributes\items\Column;
use Ubiquity\attributes\items\Validator;
use Ubiquity\attributes\items\Table;
use Ubiquity\attributes\items\ManyToOne;
use Ubiquity\attributes\items\JoinColumn;
use Ubiquity\attributes\items\OneToMany;

#[\AllowDynamicProperties()]
#[Table(name: "execution")]
class Execution{
	
	#[Id()]
	#[Column(name: "id",dbType: "int(11)")]
	#[Validator(type: "id",constraints: ["autoinc"=>true])]
	private $id;

	
	#[Column(name: "uid",dbType: "varchar(36)")]
	#[Validator(type: "length",constraints: ["max"=>"36","notNull"=>true])]
	private $uid;

	
	#[Column(name: "createdAt",dbType: "timestamp")]
	#[Validator(type: "notNull",constraints: [])]
	private $createdAt;

	
	#[ManyToOne()]
	#[JoinColumn(className: "models\\Benchmark",name: "idBenchmark")]
	private $benchmark;

	
	#[OneToMany(mappedBy: "execution",className: "models\\Result")]
	private $results;


	 public function __construct(){
		$this->results = [];
	}


	public function getId(){
		return $this->id;
	}


	public function setId($id){
		$this->id=$id;
	}


	public function getUid(){
		return $this->uid;
	}


	public function setUid($uid){
		$this->uid=$uid;
	}


	public function getCreatedAt(){
		return $this->createdAt;
	}


	public function setCreatedAt($createdAt){
		$this->createdAt=$createdAt;
	}


	public function getBenchmark(){
		return $this->benchmark;
	}


	public function setBenchmark($benchmark){
		$this->benchmark=$benchmark;
	}


	public function getResults(){
		return $this->results;
	}


	public function setResults($results){
		$this->results=$results;
	}


	 public function addToResults($result){
		$this->results[]=$result;
		$result->setExecution($this);
	}


	 public function __toString(){
		return ($this->createdAt??'no value').'';
	}

}