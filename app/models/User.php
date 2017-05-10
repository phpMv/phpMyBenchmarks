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

	/**
	 * @column("name"=>"authkey","nullable"=>true)
	 */
	private $authkey;

	/**
	 * @oneToMany("mappedBy"=>"user","className"=>"models\Benchmark")
	*/
	private $benchmarks;

	/**
	 * @manyToMany("targetEntity"=>"models\Benchmark","inversedBy"=>"userstars")
	 * @joinTable("name"=>"benchstar")
	 */
	private $benchstars;

	/**
	 * @manyToOne
	 * @joinColumn("name"=>"idAuthProvider","className"=>"models\Authprovider","nullable"=>true)
	 */
	private $authProvider;

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

	 public function setEmail($mail){
		$this->email=$mail;
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

	 public function getBenchmarks(){
		return $this->benchmarks;
	}

	 public function setBenchmarks($benchmarks){
		$this->benchmarks=$benchmarks;
	}

	public function getAuthProvider() {
		return $this->authProvider;
	}

	public function setAuthProvider($authProvider) {
		$this->authProvider=$authProvider;
		return $this;
	}

	public function getBenchstars() {
		return $this->benchstars;
	}

	public function setBenchstars($benchstars) {
		$this->benchstars=$benchstars;
		return $this;
	}



}