<?php
return array(
		"siteUrl"=>"https://phpmybenchmarks.kobject.net/",
		"database"=>[
				"dbName"=>"benchmarks",
                "wrapper"=>"Ubiquity\\db\\providers\\pdo\\PDOWrapper",
				"type"=>"mysql",
				"serverName"=>"127.0.0.1",
				"port"=>"3306",
				"user"=>"admin",
				"password"=>"ilovedev",
                "options"=>[],
				"cache"=>false
		],
		"namespaces"=>[],
		"templateEngine"=>'Ubiquity\\views\\engine\\twig\\Twig',
		"templateEngineOptions"=>array("cache"=>false),
		"test"=>false,
		"debug"=>false,
		"sessionName"=>"phpmybenchmarks",
		"di"=>['@exec'=>["jquery"=>function($ctrl){
							$jquery=new Ajax\php\ubiquity\JsUtils(["defer"=>true,"debug"=>false],$ctrl);
							$jquery->setAjaxLoader("<div class=\"ui active centered inline text loader\">Loading</div>");
							$jquery->semantic(new Ajax\Semantic());
							return $jquery;
						}]],
		"cacheDirectory"=>"cache/",
		"mvcNS"=>["models"=>"models","controllers"=>"controllers"]
);
