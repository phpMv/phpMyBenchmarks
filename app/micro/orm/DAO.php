<?php

namespace micro\orm;

use micro\db\SqlUtils;
use micro\db\Database;
use micro\log\Logger;
use micro\orm\parser\ManyToManyParser;
use micro\orm\parser\Reflexion;

/**
 * Classe passerelle entre base de données et modèle objet
 * @author jc
 * @version 1.0.0.5
 * @package orm
 */
class DAO {
	public static $db;

	private static function getCondition($keyValues) {
		$retArray=array ();
		if (is_array($keyValues)) {
			foreach ( $keyValues as $key => $value ) {
				$retArray[]="`" . $key . "` = '" . $value . "'";
			}
			$condition=implode(" AND ", $retArray);
		} else
			$condition=$keyValues;
		return $condition;
	}

	/**
	 * Charge les membres associés à $instance par une relation de type ManyToOne
	 * @param object $instance
	 * @param mixed $value
	 * @param array $annotationArray
	 * @param boolean $useCache
	 */
	private static function getOneManyToOne($instance, $value, $annotationArray, $useCache=NULL) {
		$class=get_class($instance);
		$member=$annotationArray["member"];
		$key=OrmUtils::getFirstKey($annotationArray["className"]);
		$kv=array ($key => $value );
		$obj=self::getOne($annotationArray["className"], $kv, false, false, $useCache);
		if ($obj !== null) {
			Logger::log("getOneManyToOne", "Chargement de " . $member . " pour l'objet " . $class);
			$accesseur="set" . ucfirst($member);
			if (method_exists($instance, $accesseur)) {
				$instance->$accesseur($obj);
				return;
			}
		}
	}

	/**
	 * Affecte/charge les enregistrements fils dans le membre $member de $instance.
	 * Si $array est null, les fils sont chargés depuis la base de données
	 * @param object $instance
	 * @param string $member Membre sur lequel doit être présent une annotation OneToMany
	 * @param array $array paramètre facultatif contenant la liste des fils possibles
	 * @param boolean $useCache
	 * @param array $annot used internally
	 */
	public static function getOneToMany($instance, $member, $array=null, $useCache=NULL, $annot=null) {
		$ret=array ();
		$class=get_class($instance);
		if (!isset($annot))
			$annot=OrmUtils::getAnnotationInfoMember($class, "#oneToMany", $member);
		if ($annot !== false) {
			$fkAnnot=OrmUtils::getAnnotationInfoMember($annot["className"], "#joinColumn", $annot["mappedBy"]);
			if ($fkAnnot !== false) {
				$fkv=OrmUtils::getFirstKeyValue($instance);
				if (is_null($array)) {
					$ret=self::getAll($annot["className"], $fkAnnot["name"] . "='" . $fkv . "'", true, false, $useCache);
				} else {
					self::getOneToManyFromArray($ret, $array, $fkv, $annot);
				}
				self::setToMember($member, $instance, $ret, $class, "getOneToMany");
			}
		}
		return $ret;
	}

	private static function getOneToManyFromArray(&$ret, $array, $fkv, $annot) {
		$elementAccessor="get" . ucfirst($annot["mappedBy"]);
		foreach ( $array as $element ) {
			$elementRef=$element->$elementAccessor();
			if (!is_null($elementRef)) {
				$idElementRef=OrmUtils::getFirstKeyValue($elementRef);
				if ($idElementRef == $fkv)
					$ret[]=$element;
			}
		}
	}

	private static function setToMember($member, $instance, $value, $class, $part) {
		$accessor="set" . ucfirst($member);
		if (method_exists($instance, $accessor)) {
			Logger::log($part, "Affectation de " . $member . " pour l'objet " . $class);
			$instance->$accessor($value);
		} else {
			Logger::warn($part, "L'accesseur " . $accessor . " est manquant pour " . $class);
		}
	}

	/**
	 *
	 * @param object $instance
	 * @param ManyToManyParser $parser
	 * @return PDOStatement
	 */
	private static function getSQLForJoinTable($instance, ManyToManyParser $parser) {
		$accessor="get" . ucfirst($parser->getPk());
		$sql="SELECT * FROM `" . $parser->getJoinTable() . "` WHERE `" . $parser->getMyFkField() . "`='" . $instance->$accessor() . "'";
		Logger::log("ManyToMany", "Exécution de " . $sql);
		return self::$db->query($sql);
	}

	/**
	 * Affecte/charge les enregistrements fils dans le membre $member de $instance.
	 * Si $array est null, les fils sont chargés depuis la base de données
	 * @param object $instance
	 * @param string $member Membre sur lequel doit être présent une annotation ManyToMany
	 * @param array $array paramètre facultatif contenant la liste des fils possibles
	 * @param boolean $useCache
	 */
	public static function getManyToMany($instance, $member, $array=null, $useCache=NULL) {
		$ret=array ();
		$class=get_class($instance);
		$parser=new ManyToManyParser($instance, $member);
		if ($parser->init()) {
			if (is_null($array)) {
				$joinTableCursor=self::getSQLForJoinTable($instance, $parser);
				foreach ( $joinTableCursor as $row ) {
					$fkv=$row[$parser->getFkField()];
					$tmp=self::getOne($parser->getTargetEntity(), "`" . $parser->getPk() . "`='" . $fkv . "'", false, false, $useCache);
					array_push($ret, $tmp);
				}
			} else {
				self::getManyToManyFromArray($ret, $instance, $array, $class, $parser);
			}
			self::setToMember($member, $instance, $ret, $class, "getManyToMany");
		}
		return $ret;
	}

	private static function getManyToManyFromArray(&$ret, $instance, $array, $class, $parser) {
		$continue=true;
		$accessorToMember="get" . ucfirst($parser->getInversedBy());
		$myPkAccessor="get" . ucfirst($parser->getMyPk());

		if (!method_exists($instance, $myPkAccessor)) {
			Logger::warn("ManyToMany", "L'accesseur au membre clé primaire " . $myPkAccessor . " est manquant pour " . $class);
		}
		if (count($array) > 0)
			$continue=method_exists($array[0], $accessorToMember);
		if ($continue) {
			foreach ( $array as $targetEntityInstance ) {
				$instances=$targetEntityInstance->$accessorToMember();
				if (is_array($instances)) {
					foreach ( $instances as $inst ) {
						if ($inst->$myPkAccessor() == $instance->$myPkAccessor())
							array_push($ret, $targetEntityInstance);
					}
				}
			}
		} else {
			Logger::warn("ManyToMany", "L'accesseur au membre " . $parser->getInversedBy() . " est manquant pour " . $parser->getTargetEntity());
		}
	}

	/**
	 * Retourne un tableau d'objets de $className depuis la base de données
	 * @param string $className nom de la classe du model à charger
	 * @param string $condition Partie suivant le WHERE d'une instruction SQL
	 * @param boolean $loadManyToOne
	 * @param boolean $loadOneToMany
	 * @param boolean $useCache
	 * @return array
	 */
	public static function getAll($className, $condition='', $loadManyToOne=true, $loadOneToMany=false, $useCache=NULL) {
		$objects=array ();
		$invertedJoinColumns=null;
		$oneToManyFields=null;
		$tableName=OrmUtils::getTableName($className);
		$metaDatas=OrmUtils::getModelMetadata($className);
		if ($loadManyToOne && isset($metaDatas["#invertedJoinColumn"]))
			$invertedJoinColumns=$metaDatas["#invertedJoinColumn"];
		if ($loadOneToMany && isset($metaDatas["#oneToMany"])) {
			$oneToManyFields=$metaDatas["#oneToMany"];
		}
		if ($condition != '')
			$condition=" WHERE " . $condition;
		$query=self::$db->prepareAndExecute($tableName, $condition, $useCache);
		Logger::log("getAll", "SELECT * FROM " . $tableName . $condition);
		foreach ( $query as $row ) {
			$o=self::loadObjectFromRow($row, $className, $invertedJoinColumns, $oneToManyFields, $useCache);
			$objects[]=$o;
		}
		return $objects;
	}

	private static function loadObjectFromRow($row, $className, $invertedJoinColumns, $oneToManyFields, $useCache=NULL) {
		$o=new $className();
		foreach ( $row as $k => $v ) {
			$accesseur="set" . ucfirst($k);
			if (method_exists($o, $accesseur)) {
				$o->$accesseur($v);
			}
			if (isset($invertedJoinColumns) && isset($invertedJoinColumns[$k])) {
				self::getOneManyToOne($o, $v, $invertedJoinColumns[$k], $useCache);
			}
		}
		if (isset($oneToManyFields)) {
			foreach ( $oneToManyFields as $k => $annot ) {
				self::getOneToMany($o, $k, null, $useCache, $annot);
			}
		}
		return $o;
	}

	/**
	 * Retourne le nombre d'objets de $className depuis la base de données respectant la condition éventuellement passée en paramètre
	 * @param string $className nom de la classe du model à charger
	 * @param string $condition Partie suivant le WHERE d'une instruction SQL
	 */
	public static function count($className, $condition='') {
		$tableName=OrmUtils::getTableName($className);
		if ($condition != '')
			$condition=" WHERE " . $condition;
		return self::$db->query("SELECT COUNT(*) FROM " . $tableName . $condition)->fetchColumn();
	}

	/**
	 * Retourne une instance de $className depuis la base de données, à  partir des valeurs $keyValues de la clé primaire
	 * @param String $className nom de la classe du model à charger
	 * @param Array|string $keyValues valeurs des clés primaires ou condition
	 * @param boolean $useCache
	 */
	public static function getOne($className, $keyValues, $loadManyToOne=true, $loadOneToMany=false, $useCache=NULL) {
		if (!is_array($keyValues)) {
			if (strrpos($keyValues, "=") === false) {
				$keyValues="`" . OrmUtils::getFirstKey($className) . "`='" . $keyValues . "'";
			} elseif ($keyValues == "")
				$keyValues="";
		}
		$condition=self::getCondition($keyValues);
		$retour=self::getAll($className, $condition, $loadManyToOne, $loadOneToMany, $useCache);
		if (sizeof($retour) < 1)
			return null;
		else
			return $retour[0];
	}

	/**
	 * Supprime $instance dans la base de données
	 * @param Classe $instance instance à supprimer
	 */
	public static function remove($instance) {
		$tableName=OrmUtils::getTableName(get_class($instance));
		$keyAndValues=OrmUtils::getKeyFieldsAndValues($instance);
		$sql="DELETE FROM " . $tableName . " WHERE " . SqlUtils::getWhere($keyAndValues);
		Logger::log("delete", $sql);
		$statement=self::$db->prepareStatement($sql);
		foreach ( $keyAndValues as $key => $value ) {
			self::$db->bindValueFromStatement($statement, $key, $value);
		}
		return $statement->execute();
	}

	/**
	 * Insère $instance dans la base de données
	 * @param object $instance instance à insérer
	 * @param $insertMany si vrai, sauvegarde des instances reliées à $instance par un ManyToMany
	 */
	public static function insert($instance, $insertMany=false) {
		$tableName=OrmUtils::getTableName(get_class($instance));
		$keyAndValues=Reflexion::getPropertiesAndValues($instance);
		$keyAndValues=array_merge($keyAndValues, OrmUtils::getManyToOneMembersAndValues($instance));
		$sql="INSERT INTO " . $tableName . "(" . SqlUtils::getInsertFields($keyAndValues) . ") VALUES(" . SqlUtils::getInsertFieldsValues($keyAndValues) . ")";
		Logger::log("insert", $sql);
		Logger::log("Key and values", json_encode($keyAndValues));
		$statement=self::$db->prepareStatement($sql);
		foreach ( $keyAndValues as $key => $value ) {
			self::$db->bindValueFromStatement($statement, $key, $value);
		}
		$result=$statement->execute();
		if ($result) {
			$accesseurId="set" . ucfirst(OrmUtils::getFirstKey(get_class($instance)));
			$instance->$accesseurId(self::$db->lastInserId());
			if ($insertMany) {
				self::insertOrUpdateAllManyToMany($instance);
			}
		}
		return $result;
	}

	/**
	 * Met à jour les membres de $instance annotés par un ManyToMany
	 * @param object $instance
	 */
	public static function insertOrUpdateAllManyToMany($instance) {
		$members=OrmUtils::getAnnotationInfo(get_class($instance), "#manyToMany");
		if ($members !== false) {
			$members=\array_keys($members);
			foreach ( $members as $member ) {
				self::insertOrUpdateManyToMany($instance, $member);
			}
		}
	}

	/**
	 * Met à jour le membre $member de $instance annoté par un ManyToMany
	 * @param Object $instance
	 * @param String $member
	 */
	public static function insertOrUpdateManyToMany($instance, $member) {
		$parser=new ManyToManyParser($instance, $member);
		if ($parser->init()) {
			$myField=$parser->getMyFkField();
			$field=$parser->getFkField();
			$sql="INSERT INTO `" . $parser->getJoinTable() . "`(`" . $myField . "`,`" . $field . "`) VALUES (:" . $myField . ",:" . $field . ");";
			$memberAccessor="get" . ucfirst($member);
			$memberValues=$instance->$memberAccessor();
			$myKey=$parser->getMyPk();
			$myAccessorId="get" . ucfirst($myKey);
			$accessorId="get" . ucfirst($parser->getPk());
			$id=$instance->$myAccessorId();
			if (!is_null($memberValues)) {
				self::$db->execute("DELETE FROM `" . $parser->getJoinTable() . "` WHERE `" . $myField . "`='" . $id . "'");
				$statement=self::$db->prepareStatement($sql);
				foreach ( $memberValues as $targetInstance ) {
					$foreignId=$targetInstance->$accessorId();
					$foreignInstances=self::getAll($parser->getTargetEntity(), "`" . $parser->getPk() . "`" . "='" . $foreignId . "'");
					if (!OrmUtils::exists($targetInstance, $parser->getPk(), $foreignInstances)) {
						self::insert($targetInstance, false);
						$foreignId=$targetInstance->$accessorId();
						Logger::log("InsertMany", "Insertion d'une instance de " . get_class($instance));
					}
					self::$db->bindValueFromStatement($statement, $myField, $id);
					self::$db->bindValueFromStatement($statement, $field, $foreignId);
					$statement->execute();
					Logger::log("InsertMany", "Insertion des valeurs dans la table association '" . $parser->getJoinTable() . "'");
				}
			}
		}
	}

	/**
	 * Met à jour $instance dans la base de données.
	 * Attention de ne pas modifier la clé primaire
	 * @param Classe $instance instance à modifier
	 * @param $updateMany Ajoute ou met à jour les membres ManyToMany
	 */
	public static function update($instance, $updateMany=false) {
		$tableName=OrmUtils::getTableName(get_class($instance));
		$ColumnskeyAndValues=Reflexion::getPropertiesAndValues($instance);
		$ColumnskeyAndValues=array_merge($ColumnskeyAndValues, OrmUtils::getManyToOneMembersAndValues($instance));
		$keyFieldsAndValues=OrmUtils::getKeyFieldsAndValues($instance);
		$sql="UPDATE " . $tableName . " SET " . SqlUtils::getUpdateFieldsKeyAndValues($ColumnskeyAndValues) . " WHERE " . SqlUtils::getWhere($keyFieldsAndValues);
		Logger::log("update", $sql);
		Logger::log("Key and values", json_encode($ColumnskeyAndValues));
		$statement=self::$db->prepareStatement($sql);
		foreach ( $ColumnskeyAndValues as $key => $value ) {
			self::$db->bindValueFromStatement($statement, $key, $value);
		}
		$result=$statement->execute();
		if ($result && $updateMany)
			self::insertOrUpdateAllManyToMany($instance);
		return $result;
	}

	/**
	 * Réalise la connexion à la base de données en utilisant les paramètres passés
	 * @param string $dbName
	 * @param string $serverName
	 * @param string $port
	 * @param string $user
	 * @param string $password
	 */
	public static function connect($dbName, $serverName="127.0.0.1", $port="3306", $user="root", $password="", $cache=false) {
		self::$db=new Database($dbName, $serverName, $port, $user, $password, $cache);
		self::$db->connect();
	}
}
