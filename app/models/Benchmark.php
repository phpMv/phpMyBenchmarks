<?php
namespace models;

use libraries\Models;
use Ubiquity\attributes\items\Id;
use Ubiquity\attributes\items\Column;
use Ubiquity\attributes\items\Transient;
use Ubiquity\attributes\items\Validator;
use Ubiquity\attributes\items\Table;
use Ubiquity\attributes\items\OneToMany;
use Ubiquity\attributes\items\ManyToOne;
use Ubiquity\attributes\items\JoinColumn;
use Ubiquity\attributes\items\ManyToMany;
use Ubiquity\attributes\items\JoinTable;

#[\AllowDynamicProperties()]
#[Table(name: "benchmark")]
class Benchmark{
	
	#[Id()]
	#[Column(name: "id",dbType: "int(11)")]
	#[Validator(type: "id",constraints: ["autoinc"=>true])]
	private $id;

	
	#[Column(name: "name",dbType: "varchar(100)")]
	#[Validator(type: "length",constraints: ["max"=>"100","notNull"=>true])]
	private $name;

	
	#[Column(name: "description",dbType: "text")]
	#[Validator(type: "notNull",constraints: [])]
	private $description;

	
	#[Column(name: "createdAt",dbType: "timestamp")]
	#[Validator(type: "notNull",constraints: [])]
	private $createdAt;

	
	#[Column(name: "beforeAll",dbType: "text")]
	#[Validator(type: "notNull",constraints: [])]
	private $beforeAll;

	
	#[Column(name: "version",dbType: "varchar(10)")]
	#[Validator(type: "length",constraints: ["max"=>"10","notNull"=>true])]
	private $version;

	
	#[Column(name: "phpVersion",nullable: true,dbType: "varchar(10)")]
	#[Validator(type: "length",constraints: ["max"=>"10"])]
	private $phpVersion;

	
	#[Column(name: "iterations",dbType: "int(11)")]
	#[Validator(type: "notNull",constraints: [])]
	private $iterations;

	
	#[Column(name: "analysis",nullable: true,dbType: "text")]
	private $analysis;

	
	#[Column(name: "domains", nullable: true, dbType: "varchar(100)")]
	#[Validator(type: "length",constraints: ["max"=>"100","notNull"=>false])]
	private $domains='';

	
	#[OneToMany(mappedBy: "benchmark",className: "models\\Benchmark")]
	private $benchmarks;

	
	#[ManyToOne()]
	#[JoinColumn(className: "models\\Benchmark",name: "idFork")]
	private $benchmark;

	
	#[OneToMany(mappedBy: "benchmark",className: "models\\Execution")]
	private $executions;

	
	#[OneToMany(mappedBy: "benchmark",className: "models\\Testcase")]
	private $testcases;

	
	#[ManyToOne()]
	#[JoinColumn(className: "models\\User",name: "idUser")]
	private $user;

	
	#[ManyToMany(targetEntity: "models\\User",inversedBy: "benchmarks")]
	#[JoinTable(name: "benchstar")]
	private $users;

    private $idFork;


    #[Transient]
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
        $testcase->setPhpVersion($this->phpVersion);
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
        return $this->users;
    }

    public function setUserstars($userstars) {
        $this->users=$userstars;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * @param mixed $users
     */
    public function setUsers($users): void {
        $this->users = $users;
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