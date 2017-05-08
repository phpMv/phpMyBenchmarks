<?php
namespace models;
class Result{
	/**
	 * @id
	*/
	private $id;

	private $uid;

	private $createdAt;

	private $status;

	private $timer;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\Testcase","name"=>"idTestcase","nullable"=>false)
	*/
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

	 public function getTestcase(){
		return $this->testcase;
	}

	 public function setTestcase($testcase){
		$this->testcase=$testcase;
	}

}