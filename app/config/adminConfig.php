<?php
return array(
	"devtools-path"=>"Ubiquity",
	"info"=>[],
	"display-cache-types"=>[
			"controllers",
			"models"
			],
	"ace-theme"=>"automatic",
	"maintenance"=>[
			"on"=>false,
			"modes"=>[
					"maintenance"=>[
							"excluded"=>[
									"urls"=>[
											"admin",
											"Admin"
											],
									"ports"=>[
											8080,
											8090
											],
									"hosts"=>[
											"127.0.0.1"
											]
									],
							"controller"=>"\\controllers\\MaintenanceController",
							"action"=>"index",
							"title"=>"Maintenance mode",
							"icon"=>"recycle",
							"message"=>"Our application is currently undergoing sheduled maintenance.<br>Thank you for your understanding."
							]
					]
			],
	"git-macros"=>[
			"Status"=>"git status",
			"commit & push"=>"git+add+.%0Agit+commit+-m+%22%3Cyour+message%3E%22%0Agit+push%0A",
			"checkout"=>"git+checkout+%3Cbranch-name%3E",
			"remove file from remote repository"=>"git+rm+--cached+%3Cfilename%3E%0Agit+commit+-m+%22Removed+file+from+repository%22%0Agit+push",
			"remove folder from remote repository"=>"git+rm+--cached+-r+%3Cdir_name%3E%0Agit+commit+-m+%22Removed+folder+from+repository%22%0Agit+push",
			"undo last commit (soft)"=>"git+reset+--soft+HEAD%5E",
			"undo last commit (hard)"=>"git+reset+--hard+HEAD%5E",
			"unstage file(s) from index"=>"git+rm+--cached+%3Cfile-name%3E",
			"stash & pull (overwrite local changes with pull)"=>"git+stash%0Agit+pull%0A"
			],
	"part1"=>[
			"models",
			"routes",
			"controllers",
			"cache",
			"rest",
			"config"
			],
	"part2"=>[
			"translate",
			"themes",
			"maintenance",
			"composer",
			"oauth"
			],
	"style"=>"",
	"oauth-providers"=>[
			"GitHub"=>false
			],
	"activeDb"=>"default"
	);