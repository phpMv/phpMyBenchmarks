<?php

namespace libraries;

use Ajax\JsUtils;
use Ajax\semantic\html\elements\HtmlButton;

class GUI {
	public static function showSimpleMessage(JsUtils $jquery,$content,$type,$icon="info circle",$timeout=NULL){
		$semantic=$jquery->semantic();
		$message=$semantic->htmlMessage("msg-".rand(0,50),$content,$type);
		$message->setIcon($icon);
		$message->setDismissable();
		if(isset($timeout))
			$message->setTimeout(3000);
		return $message;
	}

	public static function showConfMessage(JsUtils $jquery,$content,$type,$url,$responseElement,$data,$attributes=NULL){
		$messageDlg=self::showSimpleMessage($jquery,$content, $type,"help circle");
		$btOkay=new HtmlButton("bt-okay","Confirm","positive");
		$btOkay->addIcon("check circle");
		$btOkay->postOnClick($url,"{data:'".$data."'}",$responseElement,$attributes);
		$btCancel=new HtmlButton("bt-cancel","Cancel","negative");
		$btCancel->addIcon("remove circle outline");
		$btCancel->onClick($messageDlg->jsHide());

		$messageDlg->addContent([$btOkay,$btCancel]);
		return $messageDlg;
	}
}