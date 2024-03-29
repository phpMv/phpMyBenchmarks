<?php

namespace libraries;

use Ajax\php\ubiquity\JsUtils;
use Ajax\semantic\html\elements\HtmlImage;
use Ubiquity\utils\http\USession;
use models\User;

class UserAuth {
	/**
	 * Retourne l'utilisateur actuellement connecté<br>
	 * ou NULL si personne ne l'est
	 * @return User
	 */
	public static function getUser(){
		return USession::get("user");
	}

	/**
	 * Retourne vrai si un utilisateur est connecté
	 * @return boolean
	 */
	public static function isAuth(){
		return null!==self::getUser();
	}

    public static function setUser($user){
        USession::set("user",$user);
    }

	public static function getInfoUser(JsUtils $jquery,$asItem=true){
		$user=self::getUser();
		\ob_start();
		if(isset($user)){
			$img=new HtmlImage('img-user',$user->getAvatar());
			$img->addClass('mini rounded');
			$dd=$jquery->semantic()->htmlDropdown('ddUser',$img);

			$dd->addHeaderItem('Signed in as <b>'.$user->getLogin().'</b>');
			$dd->addDividerItem();
			$dd->addItem('Create benchmark')->setProperty('data-ajax', 'Main/benchmark');
			$dd->addItem('My benchmarks')->setProperty('data-ajax', 'Benchmarks/myTab');
			$dd->addItem('All benchmarks')->setProperty('data-ajax', 'Benchmarks/allTab');
			$dd->addDividerItem();
            $theme=MySettings::getTheme();
            $iconTheme='sun';
            $themeCaption='Light theme';
            if($theme==='light'){
                $themeCaption='Dark theme';
                $iconTheme='moon';
            }
            $dd->addItem("<span>$themeCaption</span>")->setIdentifier('idTheme')->addIcon($iconTheme);
			$dd->addItem("Settings")->setProperty('data-ajax', 'Auth/settings');
            $dd->addDividerItem();
            $dd->addItem("Sign out")->setProperty("data-ajax", "Auth/disconnect");
			$jquery->getOnClick('#ddUser [data-ajax]',"","#main-container",["attr"=>"data-ajax","hasLoader"=>false]);
            $jquery->getOnClick("#idTheme",'Auth/toggleTheme',"#ajax",['hasLoader'=>'internal']);
			if($asItem)
				$dd->wrap('<div class="item">','</div>');
            $jquery->renderComponent($dd);
		}else{
			$buttons=$jquery->semantic()->htmlButtonGroups('buttons',['Sign in','Sign up','']);
			$buttons->getElement(0)->asLink()->addIcon('sign in');
			$buttons->getElement(1)->setColor('teal')->addIcon('user add');
            $iconTheme=MySettings::getTheme()==='light'?'moon':'sun';
            $buttons->getElement(2)->setIdentifier('idTheme')->asIcon($iconTheme);
			$buttons->setPropertyValues('data-ajax', ['Auth/signin','Auth/signup']);
			$jquery->getOnClick('#buttons [data-ajax]','','#main-container',['attr'=>'data-ajax','hasLoader'=>'internal']);
			if($asItem)
				$buttons->wrap('<div class="item">','</div>');
            $jquery->getOnClick('#idTheme','Auth/toggleTheme','#ajax',['hasLoader'=>'internal']);
            $jquery->renderComponent($buttons);
		}
		return \ob_get_clean();
	}

}