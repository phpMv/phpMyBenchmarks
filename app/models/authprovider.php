<?php
namespace models;

/**
 * @table("name"=>"authprovider")
 */
class Authprovider{
	/**
	 * @id
	*/
	private $id;

	private $name;

	private $icon;

	/**
	 * @oneToMany("mappedBy"=>"authprovider","className"=>"models\User")
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