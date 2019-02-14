<?php
namespace models;
class User{
	/**
	 * @id
	 * @column("name"=>"id","nullable"=>"","dbType"=>"int(11)")
	 */
	private $id;

	/**
	 * @column("name"=>"login","nullable"=>"","dbType"=>"varchar(30)")
	 */
	private $login;

	/**
	 * @column("name"=>"email","nullable"=>"","dbType"=>"varchar(255)")
	 */
	private $email;

	/**
	 * @column("name"=>"password","nullable"=>"","dbType"=>"varchar(30)")
	 */
	private $password;

	/**
	 * @column("name"=>"avatar","nullable"=>1,"dbType"=>"varchar(255)")
	 */
	private $avatar;

	/**
	 * @column("name"=>"authkey","nullable"=>"","dbType"=>"varchar(100)")
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

	public function getAvatar() {
		if(!isset($this->avatar))
			return "public/img/male.png";
		return $this->avatar;
	}

	public function setAvatar($avatar) {
		$this->avatar=$avatar;
		return $this;
	}




}