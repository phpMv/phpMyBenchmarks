<?php

namespace libraries;

use Ajax\JsUtils;
use micro\js\Jquery;
use Ajax\semantic\html\elements\HtmlImage;

class UserAuth {
	/**
	 * Retourne l'utilisateur actuellement connecté<br>
	 * ou NULL si personne ne l'est
	 * @return User
	 */
	public static function getUser(){
		$user=null;
		if(array_key_exists("user", $_SESSION))
			$user=$_SESSION["user"];
		return $user;
	}

	/**
	 * Retourne vrai si un utilisateur est connecté
	 * @return boolean
	 */
	public static function isAuth(){
		return null!==self::getUser();
	}

	public static function getInfoUser(JsUtils $jquery,$asItem=true){
		$user=self::getUser();
		\ob_start();
		if(isset($user)){
			$img=new HtmlImage("img-user",$user->getAvatar());
			$img->addClass("mini rounded");
			$dd=$jquery->semantic()->htmlDropdown("ddUser",$img);

			$dd->addHeaderItem("Signed in as <b>".$user->getLogin()."</b>");
			$dd->addDividerItem();
			$dd->addItem("Create benchmark")->setProperty("data-ajax", "Main/benchmark");
			$dd->addItem("My benchmarks")->setProperty("data-ajax", "Benchmarks/myTab");
			$dd->addItem("All benchmarks")->setProperty("data-ajax", "Benchmarks/allTab");
			$dd->addDividerItem();
			$dd->addItem("Sign out")->setProperty("data-ajax", "Auth/disconnect");
			$dd->getOnClick("","#main-container",["attr"=>"data-ajax","ajaxTransition"=>"random"]);
			if($asItem)
				$dd->wrap('<div class="item">','</div>');
			echo $dd->compile($jquery);
		}else{
			$buttons=$jquery->semantic()->htmlButtonGroups("buttons",["Sign in","Sign up"]);
			$buttons->getElement(0)->asLink()->addIcon("sign in");
			$buttons->getElement(1)->setColor("teal")->addIcon("user add");
			$buttons->setPropertyValues("data-ajax", ["Auth/signin","Auth/signup"]);
			$buttons->getOnClick("","#main-container",["attr"=>"data-ajax"]);
			if($asItem)
				$buttons->wrap('<div class="item">','</div>');
			echo $buttons->compile($jquery);
		}
		return \ob_get_clean();
	}

}