<?php
return array(
		"siteUrl"=>"http://127.0.0.1/phpmybenchmarks/",
		"database"=>[
				"dbName"=>"benchmarks",
				"type"=>"mysql",
				"serverName"=>"127.0.0.1",
				"port"=>"3306",
				"user"=>"root",
				"password"=>"",
				"cache"=>false
		],
		"namespaces"=>[],
		"templateEngine"=>'Ubiquity\\views\\engine\\Twig',
		"templateEngineOptions"=>array("cache"=>false),
		"test"=>false,
		"debug"=>false,
		"sessionName"=>"phpmybenchmarks",
		"di"=>["jquery"=>function(){
							$jquery=new Ajax\php\ubiquity\JsUtils(["defer"=>true,"debug"=>false]);
							$jquery->setAjaxLoader("<div class=\"ui active centered inline text loader\">Loading</div>");
							$jquery->semantic(new Ajax\Semantic());
							return $jquery;
						}],
		"cacheDirectory"=>"cache/",
		"mvcNS"=>["models"=>"models","controllers"=>"controllers"]
);
