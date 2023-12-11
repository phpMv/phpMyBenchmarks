<?php
namespace controllers;

use models\User;
use Ubiquity\attributes\items\router\Get;
use Hybridauth\Adapter\AdapterInterface;
use Ubiquity\controllers\Startup;
use Ubiquity\orm\DAO;
use Ubiquity\utils\http\URequest;

/**
  * Controller MyOauth
  */
class MyOauth extends \Ubiquity\controllers\auth\AbstractOAuthController {

	public function index(){
	}
	
	#[Get(path: "oauth/{name}")]
	public function _oauth(string $name, ?string $callbackUrl = null):void {
		parent::_oauth($name,'https://phpmybenchmarks.kobject.net/oauth/'.$name);
	}
	
	protected function onConnect(string $name,AdapterInterface $provider){
        $user_profile=$provider->getUserProfile();
        $dbProvider=DAO::getOne("models\Authprovider", array (
            "name" => $name
        ));
        if ($dbProvider!=NULL) {
            $user=DAO::getOne("models\User", array (
                "login" => $user_profile->displayName,"idAuthProvider" => $dbProvider->getId()
            ));
            if ($user===null) {
                $user=new User();
                $user->setLogin($user_profile->displayName);
                $user->setEmail($user_profile->email);
                $user->setAuthProvider($dbProvider);
                $user->setAuthkey($user_profile->identifier);
                $user->setAvatar($user_profile->photoURL);
                DAO::insert($user);
            }
            $_SESSION["user"]=$user;
            $user->setAvatar($user_profile->photoURL);
            setcookie("autoConnect", $provider, time()+3600, "/");
            if (array_key_exists("action", $_SESSION)) {
                Startup::runAction($_SESSION["action"], false, false);
                unset($_SESSION["action"]);
            } else {
                header('location:'.URequest::getUrl(""));
            }
        }
	}
}
