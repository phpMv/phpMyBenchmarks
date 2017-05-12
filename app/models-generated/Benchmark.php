<?php
namespace models;
class Benchmark{
	/**
	 * @id
	*/
	private $id;

	private $name;

	private $description;

	private $createdAt;

	private $beforeAll;

	private $version;

	private $phpVersion;

	/**
	 * @oneToMany("mappedBy"=>"benchmark","className"=>"models\Execution")
	*/
	private $executions;

	/**
	 * @oneToMany("mappedBy"=>"benchmark","className"=>"models\Testcase")
	*/
	private $testcases;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\User","name"=>"idUser","nullable"=>false)
	*/
	private $user;

	/**
	 * @manyToMany("targetEntity"=>"models\User","inversedBy"=>"benchmarks")
	 * @joinTable("name"=>"benchstar")
	*/
	private $users;

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

	 public function getDescription(){
		return $this->description;
	}

	 public function setDescription($description){
		$this->description=$description;
	}

	 public function getCreatedAt(){
		return $this->createdAt;
	}

	 public function setCreatedAt($createdAt){
		$this->createdAt=$createdAt;
	}

	 public function getBeforeAll(){
		return $this->beforeAll;
	}

	 public function setBeforeAll($beforeAll){
		$this->beforeAll=$beforeAll;
	}

	 public function getVersion(){
		return $this->version;
	}

	 public function setVersion($version){
		$this->version=$version;
	}

	 public function getPhpVersion(){
		return $this->phpVersion;
	}

	 public function setPhpVersion($phpVersion){
		$this->phpVersion=$phpVersion;
	}

	 public function getExecutions(){
		return $this->executions;
	}

	 public function setExecutions($executions){
		$this->executions=$executions;
	}

	 public function getTestcases(){
		return $this->testcases;
	}

	 public function setTestcases($testcases){
		$this->testcases=$testcases;
	}

	 public function getUser(){
		return $this->user;
	}

	 public function setUser($user){
		$this->user=$user;
	}

	 public function getUsers(){
		return $this->users;
	}

	 public function setUsers($users){
		$this->users=$users;
	}

}