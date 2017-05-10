<?php
namespace controllers;
 use micro\orm\DAO;
use models\User;
use micro\controllers\Startup;
use micro\utils\RequestUtils;
use libraries\UserAuth;
use Ajax\semantic\html\elements\HtmlButton;
use Ajax\semantic\html\base\constants\Social;
use libraries\GUI;

 /**
 * Controller Auth
 **/
class Auth extends ControllerBase{

	public function index(){
		$this->jquery->get("Auth/infoUser","#divInfoUser");
		echo $this->jquery->compile();
	}

	public function signup(){
		$this->sign(function(){
			$frm=$this->semantic->defaultAccount("frm-account",new User());
			$frm->setSubmitParams("Auth/signCheck/true","#ajax");
			$this->jquery->postOn("change", "#frm-account-login-0", "Auth/signCheck","{'login':$(this).val()}","#ajax");
		}, ["title1"=>"Sign up with","title2"=>"Create an account"]);
	}

	public function signin(){
		$this->sign(function(){
			$frm=$this->semantic->defaultLogin("frm-account",new User());
			$frm->setSubmitParams("Auth/connect","#main-container");
		}, ["title1"=>"Sign in with","title2"=>"Log in with your account"]);
	}

	public function connect(){
		if(isset($_POST["login"])){
			$users=DAO::getAll("models\User","login='".$_POST["login"]."' OR email='".$_POST["login"]."'");
			foreach ($users as $user){
				if($user->getPassword()==@$_POST["password"]){
					$user->avatar="public/img/male.png";
					$_SESSION["user"]=$user;
					$header=$this->jquery->semantic()->htmlHeader("headerUser",3);
					$header->asImage($user->avatar, $user->getLogin(),"connected");
					echo GUI::showSimpleMessage($this->jquery, $header, "info","");
					$this->forward("controllers\Main","index",[],true,true);
					$this->jquery->get("Auth/infoUser","#divInfoUser","{}",null,false);
					break;
				}
			}
		}
		if(!isset($_SESSION["user"])){
			echo GUI::showSimpleMessage($this->jquery, "Failed to connect : bad login or password.", "error","error");
			$this->forward("controllers\Auth","signin",[],true,true);
		}
		echo $this->jquery->compile($this->view);
	}

	public function signCheck($beforeSubmit=false){
		if(isset($_POST["login"])){
			$nb=DAO::count("models\User","login='".$_POST["login"]."' AND idAuthProvider is null");
			if($nb>0){
				$this->jquery->exec("$('#frm-account').form('add errors', {login: 'This login is already in use.'});$('#frm-account').form('add prompt', 'login')",true);
			}else{
				$this->jquery->exec("$('#frm-account .ui.error.message ul:contains(\"This login is already in use.\")').remove();",true);
				if($beforeSubmit=="true"){
					$this->jquery->post("Auth/createAccount","#main-container",\json_encode($_POST),["ajaxTransition"=>"random"]);
				}
			}
			echo $this->jquery->compile();
		}
	}

	public function createAccount(){
		$user=new User();
		RequestUtils::setValuesToObject($user,$_POST);
		$key=md5(\microtime(true));
		$user->setAuthkey($key);
		try{
		if(DAO::insert($user)){
			echo GUI::showSimpleMessage($this->jquery, "An email was sent to <b>".$user->getEmail()."</b>, containing a link to activate your account.", "info");
		}else{
			echo GUI::showSimpleMessage($this->jquery, "The account was not created due to an error.<br>Try again later.", "error");
		}
		}catch(\Exception $e){
			echo GUI::showSimpleMessage($this->jquery, "The account was not created due to an error.<br>Try again later.", "error");
		}
		echo $this->jquery->compile();
	}

	public function activateAccount($key){
		$user=DAO::getOne("models\User", "authkey='".$key."'");
		if($user!==null){
			$user->setAuthkey(null);
			DAO::update($user);
			$bt=new HtmlButton("bt-signin","Sign in");
			$bt->addIcon("sign in");
			$bt->getOnClick("Auth/signin","#main-container",["ajaxTransition"=>"random"]);
			echo GUI::showSimpleMessage($this->jquery, ["Your account has been activated. You can now log in as <b>`".$user->getLogin()."`</b>:<br>",$bt], "","announcement");
		}else{
			echo GUI::showSimpleMessage($this->jquery, "Unable to activate account.<br>Check the given url and try again.", "warning","warning");
		}
		$this->forward("controllers\Main","index",[],true,true);

		echo $this->jquery->compile($this->view);
	}

	private function sign($formCallback,$titles){
		$bt=HtmlButton::social("bt-github", Social::GITHUB);
		$bt->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/GitHub"));
		$bt->compile($this->jquery,$this->view);

		$bt2=HtmlButton::social("bt-google", Social::GOOGLEPLUS);
		$bt2->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/Google"));
		$bt2->compile($this->jquery,$this->view);

		$bt3=HtmlButton::social("bt-linkedin", Social::LINKEDIN);
		$bt3->asLink(RequestUtils::getUrl("Auth/signin_with_hybridauth/Linkedin"));
		$bt3->compile($this->jquery,$this->view);

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
				$user->setEmail($user_profile->email);
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
		$header->asImage($user->avatar, $user->getLogin(),"By!");
		$message=$this->semantic->htmlMessage("message",$header);
		$message->setDismissable()->setTimeout(5000);
		echo $message->compile($this->jquery);
		$this->jquery->get("Auth/infoUser","#divInfoUser","{}",null,false);
		$this->forward("controllers\Main","index",[],true,true);
		echo $this->jquery->compile();
	}
}