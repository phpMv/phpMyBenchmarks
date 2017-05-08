<?php
return array(
		"siteUrl"=>"http://127.0.0.1/phpMyBenchmarks/",
		"database"=>[
				"dbName"=>"benchmarks",
				"serverName"=>"127.0.0.1",
				"port"=>"3306",
				"user"=>"root",
				"password"=>"",
				"cache"=>false
		],
		"sessionToken"=>"%temporaryToken%",
		"namespaces"=>[],
		"templateEngine"=>'micro\views\engine\Twig',
		"templateEngineOptions"=>array("cache"=>false),
		"test"=>false,
		"debug"=>false,
		"di"=>["jquery"=>function(){
							$jquery=new Ajax\php\micro\JsUtils(["defer"=>true]);
							$jquery->setAjaxLoader("<img src=\"public/img/hourglass.svg\">");
							$jquery->semantic(new Ajax\Semantic());
							return $jquery;
						}],
		"cacheDirectory"=>"cache/",
		"mvcNS"=>["models"=>"models","controllers"=>"controllers"]
);
