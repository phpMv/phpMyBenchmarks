<?php
namespace models;
use libraries\Models;

class Benchmark{
	/**
	 * @id
	 * @column("name"=>"id","nullable"=>"","dbType"=>"int(11)")
	 */
	private $id;

	/**
	 * @column("name"=>"name","nullable"=>"","dbType"=>"varchar(100)")
	 */
	private $name;

	/**
	 * @column("name"=>"description","nullable"=>"","dbType"=>"text")
	 */
	private $description;

	/**
	 * @column("name"=>"createdAt","nullable"=>"","dbType"=>"timestamp")
	 */
	private $createdAt;

	/**
	 * @column("name"=>"beforeAll","nullable"=>"","dbType"=>"text")
	 */
	private $beforeAll;

	/**
	 * @column("name"=>"version","nullable"=>"","dbType"=>"varchar(10)")
	 */
	private $version;

	/**
	 * @column("name"=>"phpVersion","nullable"=>1,"dbType"=>"varchar(10)")
	 */
	private $phpVersion;

	private $idFork;

	/**
	 * @column("name"=>"iterations","nullable"=>"","dbType"=>"int(11)")
	 */
	private $iterations;

	/**
	 * @column("name"=>"analysis","nullable"=>1,"dbType"=>"text")
	 */
	private $analysis;

	/**
	 * @column("name"=>"domains","nullable"=>"","dbType"=>"varchar(100)")
	 */
	private $domains;

	/**
	 * @oneToMany("mappedBy"=>"benchmark","className"=>"models\\Benchmark")
	 */
	private $benchmarks;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\\Benchmark","name"=>"idFork","nullable"=>"")
	 */
	private $benchmark;

	/**
	 * @oneToMany("mappedBy"=>"benchmark","className"=>"models\\Execution")
	 */
	private $executions;

	/**
	 * @oneToMany("mappedBy"=>"benchmark","className"=>"models\\Testcase")
	 */
	private $testcases;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\\User","name"=>"idUser","nullable"=>"")
	 */
	private $user;

	/**
	 * @manyToMany("targetEntity"=>"models\\User","inversedBy"=>"benchstars")
	 * @joinTable("name"=>"benchstar")
	 */
	private $userstars;

	/**
	 * @transient
	 */
	private $toDelete;

	public function __construct(){
		$this->iterations=1000;
		$this->phpVersion=Models::$DEFAULT_PHP_VERSION;
		$this->domains="";
		$this->testcases=[];
		$this->executions=[];
		$this->toDelete=[];
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

	public function addExecution($uid){
		$exec=new Execution();
		$exec->setUid($uid);
		$exec->setBenchmark($this);
		$this->executions[]=$exec;
		return $exec;
	}

	public function addTestcase(Testcase $testcase){
		$this->testcases[]=$testcase;
		$testcase->setBenchmark($this);
		return \count($this->testcases);
	}

	public function getTestIndexByCallback($callback){
		$find=null;
		$count=\count($this->testcases);
		for($i=0;$i<$count;$i++){
			if(isset($this->testcases[$i])){
				if($callback($this->testcases[$i])){
					$find=$i;
					break;
				}
			}
		}
		return $find;
	}

	public function getTestByCallback($callback){
		$find=$this->getTestIndexByCallback($callback);
		if(isset($find))
			return $this->testcases[$find];
		return null;
	}

	public function removeTestByCallback($callback){
		$toDelete=$this->getTestIndexByCallback($callback);
		if(isset($toDelete)){
			$this->toDelete[]=$this->testcases[$toDelete];
			array_splice($this->testcases, $toDelete, 1);
		}
	}

	public function nextTestCaseId(){
		$count=\count($this->testcases);
		$max=0;
		for($i=0;$i<$count;$i++){
			if(isset($this->testcases[$i]))
				if($this->testcases[$i]->getId()>=$max)
					$max=$this->testcases[$i]->getId();
		}
		return $max+1;
	}

	public function __toString(){
		$result=$this->getName();
		if(\count($this->testcases)>0){
			$result.=" (".\count($this->testcases)." test(s))";
		}
		return $result;
	}

	public function getPhpVersion() {
		return $this->phpVersion;
	}

	public function setPhpVersion($phpVersion) {
		$this->phpVersion=$phpVersion;
		return $this;
	}

	public function getUserstars() {
		return $this->userstars;
	}

	public function setUserstars($userstars) {
		$this->userstars=$userstars;
		return $this;
	}

	public function getExecutions() {
			return $this->executions;
	}

	public function getExecution($uid=NULL) {
			foreach ($this->executions as $execution){
				if($execution->getUid()==$uid)
					return $execution;
			}
			return null;
	}

	public function setExecutions($executions) {
		$this->executions=$executions;
		return $this;
	}

	public function getIdFork() {
		return $this->idFork;
	}

	public function setIdFork($idFork) {
		$this->idFork=$idFork;
		return $this;
	}

	public function getIterations() {
		return $this->iterations;
	}

	public function setIterations($iterations) {
		$this->iterations=$iterations;
		return $this;
	}

	public function getToDelete() {
		return $this->toDelete;
	}

	public function getAnalysis() {
		return $this->analysis;
	}

	public function setAnalysis($analysis) {
		$this->analysis=$analysis;
		return $this;
	}

	public function getDomains() {
		return $this->domains;
	}

	public function setDomains($domains) {
		$this->domains=$domains;
		return $this;
	}


}
