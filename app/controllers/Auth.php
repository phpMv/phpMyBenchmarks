<?php
namespace controllers;
 use micro\orm\DAO;
use models\User;
use micro\controllers\Startup;
use Ajax\semantic\html\base\constants\Size;
use micro\utils\RequestUtils;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButton;
use Ajax\semantic\html\base\constants\Social;

 /**
 * Controller Auth
 **/
class Auth extends ControllerBase{

	public function index(){
		$this->jquery->get("Auth/infoUser","#divInfoUser");
		echo $this->jquery->compile();
	}

	public function signup(){
		$bt=HtmlButton::social("bt-github", Social::GITHUB);
		$bt->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/GitHub"));
		$bt->compile($this->jquery,$this->view);

		$bt2=HtmlButton::social("bt-google", Social::GOOGLEPLUS);
		$bt2->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/Google"));
		$bt2->compile($this->jquery,$this->view);

		$this->semantic->defaultAccount("frm-account",new User());

		$this->jquery->compile($this->view);
		$this->loadView("Auth/sign.html",["title1"=>"Sign up with","title2"=>"Create an account"]);
	}

	public function signin(){
		$this->sign(function(){$this->semantic->defaultLogin("frm-account",new User());}, ["title1"=>"Sign in with","title2"=>"Log in with your account"]);
	}

	private function sign($formCallback,$titles){
		$bt=HtmlButton::social("bt-github", Social::GITHUB);
		$bt->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/GitHub"));
		$bt->compile($this->jquery,$this->view);

		$bt2=HtmlButton::social("bt-google", Social::GOOGLEPLUS);
		$bt2->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/Google"));
		$bt2->compile($this->jquery,$this->view);

		$formCallback();

		$this->jquery->compile($this->view);
		$this->loadView("Auth/sign.html",$titles);
	}

	public function hybridauth_endpoint() {
		include ROOT."./../vendor/hybridauth/hybridauth/hybridauth/index.php";
	}

	/**
	 * @param string $provider
	 * @return \Hybrid_Provider_Adapter
	 */
	private function getAdapter($provider){
		$authConfig=ROOT."./hybridauth/config.php";
		include ROOT."./../vendor/hybridauth/hybridauth/hybridauth/Hybrid/Auth.php";

		$hybridauth=new \Hybrid_Auth($authConfig);
		return $hybridauth->authenticate($provider);
	}

	public function signin_with_hybridauth($provider) {
		$adapter=$this->getAdapter($provider);
		$user_profile=$adapter->getUserProfile();

		$dbProvider=DAO::getOne("models\AuthProvider", array (
				"name" => $provider
		));
		if ($dbProvider!=NULL) {
			$user=DAO::getOne("models\User", array (
					"login" => $user_profile->displayName,"idAuthProvider" => $dbProvider->getId()
			));
			if ($user===null) {
				$user=new User();
				$user->setLogin($user_profile->displayName);
				$user->setMail($user_profile->email);
				$user->setAuthProvider($dbProvider);
				$user->setAuthkey($user_profile->identifier);
				DAO::insert($user);
			}
			$_SESSION["user"]=$user;
			$user->avatar=$user_profile->photoURL;
			setcookie("autoConnect", $provider, time()+3600, "/");
			if (array_key_exists("action", $_SESSION)) {
				Startup::runAction($_SESSION["action"], false, false);
				unset($_SESSION["action"]);
			} else {
				header('location:'.RequestUtils::getUrl(""));
			}
		}
	}
	public function infoUser() {
		echo UserAuth::getInfoUser($this->jquery);
		echo $this->jquery->compile();
	}

	public function disconnect(){
		$user=UserAuth::getUser();
		$authProvider=$user->getAuthProvider();
		if(isset($authProvider)){
			$adapter=$this->getAdapter($user->getAuthProvider()->getName());
			$adapter->logout();
		}
		unset($_SESSION["user"]);

		$header=$this->jquery->semantic()->htmlHeader("headerUser",3);
		$header->asImage($user->avatar, $user->getLogin(),"Déconnecté");
		$message=$this->semantic->htmlMessage("message",$header);
		$message->setDismissable()->setTimeout(5000);
		echo $message->compile($this->jquery);
		$this->jquery->get("Auth/infoUser","#divInfoUser","{}",null,false);
		$this->forward("controllers\Main","index",[],true,true);
		echo $this->jquery->compile();
	}
}