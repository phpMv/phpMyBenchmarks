<?php
namespace models;
class User{
	/**
	 * @id
	*/
	private $id;

	private $login;

	private $email;

	private $password;

	private $authkey;

	/**
	 * @manyToOne
	 * @joinColumn("className"=>"models\Authprovider","name"=>"idAuthProvider","nullable"=>false)
	*/
	private $authprovider;

	/**
	 * @oneToMany("mappedBy"=>"user","className"=>"models\Benchmark")
	 * @manyToMany("targetEntity"=>"models\Benchmark","inversedBy"=>"users")
	 * @joinTable("name"=>"benchstar")
	*/
	private $benchmarks;

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

}