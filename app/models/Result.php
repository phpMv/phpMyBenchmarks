<?php
namespace models;

use Ubiquity\attributes\items\Id;
use Ubiquity\attributes\items\Column;
use Ubiquity\attributes\items\Validator;
use Ubiquity\attributes\items\Table;
use Ubiquity\attributes\items\ManyToOne;
use Ubiquity\attributes\items\JoinColumn;

#[\AllowDynamicProperties()]
#[Table(name: "result")]
class Result{
	
	#[Id()]
	#[Column(name: "id",dbType: "int(11)")]
	#[Validator(type: "id",constraints: ["autoinc"=>true])]
	private $id;

	
	#[Column(name: "uid",dbType: "varchar(36)")]
	#[Validator(type: "length",constraints: ["max"=>"36","notNull"=>true])]
	private $uid;

	
	#[Column(name: "createdAt", nullable: true, dbType: "timestamp")]
	#[Validator(type: "notNull",constraints: [])]
	private $createdAt;

	
	#[Column(name: "status",dbType: "varchar(20)")]
	#[Validator(type: "length",constraints: ["max"=>"20","notNull"=>true])]
	private $status;

	
	#[Column(name: "timer",dbType: "double")]
	#[Validator(type: "notNull",constraints: [])]
	private $timer;

	
	#[Column(name: "phpVersion",nullable: true,dbType: "varchar(10)")]
	#[Validator(type: "length",constraints: ["max"=>"10"])]
	private $phpVersion;

	
	#[Column(name: "note",dbType: "int(11)")]
	#[Validator(type: "notNull",constraints: [])]
	private $note;

	
	#[ManyToOne()]
	#[JoinColumn(className: "models\\Execution",name: "idExecution")]
	private $execution;

	
	#[ManyToOne()]
	#[JoinColumn(className: "models\\Testcase",name: "idTestcase")]
	private $testcase;


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


	public function getStatus(){
		return $this->status;
	}


	public function setStatus($status){
		$this->status=$status;
	}


	public function getTimer(){
		return $this->timer;
	}


	public function setTimer($timer){
		$this->timer=$timer;
	}


	public function getPhpVersion(){
		return $this->phpVersion;
	}


	public function setPhpVersion($phpVersion){
		$this->phpVersion=$phpVersion;
	}


	public function getNote(){
		return $this->note;
	}


	public function setNote($note){
		$this->note=$note;
	}


	public function getExecution(){
		return $this->execution;
	}


	public function setExecution($execution){
		$this->execution=$execution;
	}


	public function getTestcase(){
		return $this->testcase;
	}


	public function setTestcase($testcase){
		$this->testcase=$testcase;
	}


	 public function __toString(){
		return ($this->phpVersion??'no value').'';
	}

}