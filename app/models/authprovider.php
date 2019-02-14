<?php
namespace models;

/**
 * @table("name"=>"Authprovider")
 */
class Authprovider{
	/**
	 * @id
	 * @column("name"=>"id","nullable"=>"","dbType"=>"int(11)")
	 */
	private $id;

	/**
	 * @column("name"=>"name","nullable"=>"","dbType"=>"varchar(30)")
	 */
	private $name;

	/**
	 * @column("name"=>"icon","nullable"=>"","dbType"=>"varchar(255)")
	 */
	private $icon;

	/**
	 * @oneToMany("mappedBy"=>"authprovider","className"=>"models\\User")
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

}