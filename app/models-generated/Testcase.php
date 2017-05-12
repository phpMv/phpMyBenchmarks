<?php
namespace models;
class Testcase{
	/**
	 * @id
	*/
	private $id;

	private $name;

	private $code;

	private $createdAt;

	private $phpVersion;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\Benchmark","name"=>"idBenchmark","nullable"=>false)
	*/
	private $benchmark;

	/**
	 * @oneToMany("mappedBy"=>"testcase","className"=>"models\Result")
	*/
	private $results;

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

}