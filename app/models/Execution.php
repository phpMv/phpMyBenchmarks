<?php
namespace models;
class Execution{
	/**
	 * @id
	 * @column("name"=>"id","nullable"=>"","dbType"=>"int(11)")
	 */
	private $id;

	/**
	 * @column("name"=>"uid","nullable"=>"","dbType"=>"varchar(36)")
	 */
	private $uid;

	/**
	 * @column("name"=>"createdAt","nullable"=>"","dbType"=>"timestamp")
	 */
	private $createdAt;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\\Benchmark","name"=>"idBenchmark","nullable"=>"")
	 */
	private $benchmark;

	/**
	 * @oneToMany("mappedBy"=>"execution","className"=>"models\\Result")
	 */
	private $results;

	public function __construct(){
		$this->results=[];
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

	public function addResult(Result $result){
		$this->results[]=$result;
		$result->setExecution($this);
	}

}