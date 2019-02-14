<?php
namespace models;
class Domain{
	/**
	 * @id
	 * @column("name"=>"id","nullable"=>"","dbType"=>"int(11)")
	 */
	private $id;

	/**
	 * @column("name"=>"name","nullable"=>"","dbType"=>"varchar(100)")
	 */
	private $name;

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

}