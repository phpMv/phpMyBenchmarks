<?php
namespace models;

use Ubiquity\attributes\items\Id;
use Ubiquity\attributes\items\Column;
use Ubiquity\attributes\items\Validator;
use Ubiquity\attributes\items\Table;
use Ubiquity\attributes\items\OneToMany;

#[\AllowDynamicProperties()]
#[Table(name: "authprovider")]
class Authprovider{
	
	#[Id()]
	#[Column(name: "id",dbType: "int(11)")]
	#[Validator(type: "id",constraints: ["autoinc"=>true])]
	private $id;

	
	#[Column(name: "name",dbType: "varchar(30)")]
	#[Validator(type: "length",constraints: ["max"=>"30","notNull"=>true])]
	private $name;

	
	#[Column(name: "icon",dbType: "varchar(255)")]
	#[Validator(type: "length",constraints: ["max"=>"255","notNull"=>true])]
	private $icon;

	
	#[OneToMany(mappedBy: "authprovider",className: "models\\User")]
	private $users;


	 public function __construct(){
		$this->users = [];
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


	public function getIcon(){
		return $this->icon;
	}


	public function setIcon($icon){
		$this->icon=$icon;
	}


	public function getUsers(){
		return $this->users;
	}


	public function setUsers($users){
		$this->users=$users;
	}


	 public function addToUsers($user){
		$this->users[]=$user;
		$user->setAuthprovider($this);
	}


	 public function __toString(){
		return ($this->icon??'no value').'';
	}

}