<?php

namespace micro\views\engine\micro;

use micro\views\engine\TemplateEngine;
use micro\controllers\Startup;

class MicroTemplateEngine extends TemplateEngine {
	private $viewsFolder;

	public function __construct() {
		$this->viewsFolder=ROOT . DS . "views/";
	}

	/*
	 * (non-PHPdoc)
	 * @see TemplateEngine::render()
	 */
	public function render($viewName, $pData, $asString) {
		$config=Startup::getConfig();
		$fileName=$this->viewsFolder . $viewName;
		if (is_array($pData)) {
			extract($pData);
		}
		$tpl=new TemplateParser($fileName);
		$content=eval('?>' . $tpl->__toString());
		if ($asString)
			return $content;
		else
			echo $content;
	}
}
