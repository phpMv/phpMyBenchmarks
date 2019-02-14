<?php
namespace models;
class Result{
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
	 * @column("name"=>"status","nullable"=>"","dbType"=>"varchar(20)")
	 */
	private $status;

	/**
	 * @column("name"=>"timer","nullable"=>"","dbType"=>"double")
	 */
	private $timer;

	/**
	 * @column("name"=>"phpVersion","nullable"=>1,"dbType"=>"varchar(10)")
	 */
	private $phpVersion;

	/**
	 * @column("name"=>"note","nullable"=>"","dbType"=>"int(11)")
	 */
	private $note;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\\Execution","name"=>"idExecution","nullable"=>"")
	 */
	private $execution;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\\Testcase","name"=>"idTestcase","nullable"=>"")
	 */
	private $testcase;

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id=$id;
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

	public function getPhpVersion() {
		return $this->phpVersion;
	}

	public function setPhpVersion($phpVersion) {
		$this->phpVersion=$phpVersion;
		return $this;
	}

	public function getNote() {
		return $this->note;
	}

	public function setNote($note) {
		$this->note=$note;
		return $this;
	}

}
