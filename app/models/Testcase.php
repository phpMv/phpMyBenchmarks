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
#[Table(name: "testcase")]
class Testcase{
	
	#[Id()]
	#[Column(name: "id",dbType: "int(11)")]
	#[Validator(type: "id",constraints: ["autoinc"=>true])]
	private $id;

	
	#[Column(name: "name",dbType: "varchar(100)")]
	#[Validator(type: "length",constraints: ["max"=>"100","notNull"=>true])]
	private $name;

	
	#[Column(name: "code",dbType: "text")]
	#[Validator(type: "notNull",constraints: [])]
	private $code;

	
	#[Column(name: "createdAt",dbType: "timestamp")]
	#[Validator(type: "notNull",constraints: [])]
	private $createdAt;

	
	#[Column(name: "phpVersion",nullable: true,dbType: "varchar(10)")]
	#[Validator(type: "length",constraints: ["max"=>"10"])]
	private $phpVersion;

	
	#[ManyToOne()]
	#[JoinColumn(className: "models\\Benchmark",name: "idBenchmark")]
	private $benchmark;

	
	#[OneToMany(mappedBy: "testcase",className: "models\\Result")]
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


	public function getName(){
		return $this->name;
	}


	public function setName($name){
		$this->name=$name;
	}


	public function getCode(){
		return $this->code;
	}


	public function setCode($code){
		$this->code=$code;
	}


	public function getCreatedAt(){
		return $this->createdAt;
	}


	public function setCreatedAt($createdAt){
		$this->createdAt=$createdAt;
	}


	public function getPhpVersion(){
		return $this->phpVersion;
	}


	public function setPhpVersion($phpVersion){
		$this->phpVersion=$phpVersion;
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
		$result->setTestcase($this);
	}


	 public function __toString(){
		return ($this->phpVersion??'no value').'';
	}

}