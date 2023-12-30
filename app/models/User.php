<?php
namespace models;

use Ubiquity\attributes\items\Id;
use Ubiquity\attributes\items\Column;
use Ubiquity\attributes\items\Validator;
use Ubiquity\attributes\items\Transformer;
use Ubiquity\attributes\items\Table;
use Ubiquity\attributes\items\ManyToOne;
use Ubiquity\attributes\items\JoinColumn;
use Ubiquity\attributes\items\OneToMany;
use Ubiquity\attributes\items\ManyToMany;
use Ubiquity\attributes\items\JoinTable;

#[\AllowDynamicProperties()]
#[Table(name: "user")]
class User{
	
	#[Id()]
	#[Column(name: "id",dbType: "int(11)")]
	#[Validator(type: "id",constraints: ["autoinc"=>true])]
	private $id;

	
	#[Column(name: "login",dbType: "varchar(30)")]
	#[Validator(type: "length",constraints: ["max"=>"30","notNull"=>true])]
	private $login;

	
	#[Column(name: "email",dbType: "varchar(255)")]
	#[Validator(type: "email",constraints: ["notNull"=>true])]
	#[Validator(type: "length",constraints: ["max"=>"255"])]
	private $email;

	
	#[Column(name: "password",dbType: "varchar(30)")]
	#[Validator(type: "length",constraints: ["max"=>"30","notNull"=>true])]
	#[Transformer(name: "password")]
	private $password;

	
	#[Column(name: "avatar",nullable: true,dbType: "varchar(255)")]
	#[Validator(type: "length",constraints: ["max"=>"255"])]
	private $avatar;

	
	#[Column(name: "authkey", nullable: true, dbType: "varchar(100)")]
	#[Validator(type: "length",constraints: ["max"=>"100","notNull"=>false])]
	private $authkey;

	
	#[ManyToOne()]
	#[JoinColumn(name: "idAuthProvider", className: "models\\Authprovider", nullable: true)]
	private $authprovider;

	
	#[OneToMany(mappedBy: "user",className: "models\\Benchmark")]
	private $benchmarks;

	
	#[ManyToMany(targetEntity: "models\\Benchmark",inversedBy: "users")]
	#[JoinTable(name: "benchstar")]
	private $benchstars;

    #[Column(name: "settings", nullable: false, dbType: "text")]
    private $settings='[]';


	 public function __construct(){
		$this->benchmarks = [];
		$this->benchstars = [];
	}


	public function getId(){
		return $this->id;
	}


	public function setId($id){
		$this->id=$id;
	}


	public function getLogin(){
		return $this->login;
	}


	public function setLogin($login){
		$this->login=$login;
	}


	public function getEmail(){
		return $this->email;
	}


	public function setEmail($email){
		$this->email=$email;
	}


	public function getPassword(){
		return $this->password;
	}


	public function setPassword($password){
		$this->password=$password;
	}


	public function getAvatar(){
		return $this->avatar;
	}


	public function setAvatar($avatar){
		$this->avatar=$avatar;
	}


	public function getAuthkey(){
		return $this->authkey;
	}


	public function setAuthkey($authkey){
		$this->authkey=$authkey;
	}


	public function getAuthprovider(){
		return $this->authprovider;
	}


	public function setAuthprovider($authprovider){
		$this->authprovider=$authprovider;
	}


	public function getBenchmarks(){
		return $this->benchmarks;
	}


	public function setBenchmarks($benchmarks){
		$this->benchmarks=$benchmarks;
	}


	 public function addToBenchmarks($benchmark){
		$this->benchmarks[]=$benchmark;
		$benchmark->setUser($this);
	}


	public function getBenchstars(){
		return $this->benchstars;
	}


	public function setBenchstars($benchstars){
		$this->benchstars=$benchstars;
	}


	 public function addBenchstar($benchstar){
		$this->benchstars[]=$benchstar;
	}


	 public function __toString(){
		return ($this->avatar??'no value').'';
	}

    /**
     * @param string $settings
     */
    public function setSettings(string $settings): void {
        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function getSettings() {
        return $this->settings;
    }

    public function getSettings_() {
        return json_decode($this->settings,true);
    }

}