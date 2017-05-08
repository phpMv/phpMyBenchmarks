<?php
return
   array(
      // "base_url" the url that point to HybridAuth Endpoint (where index.php and config.php are found)
      "base_url" => $GLOBALS["config"]["siteUrl"]."Auth/hybridauth_endpoint/",

      "providers" => array (
         // google
            "Google" => array ( // 'id' is your google client id
               "enabled" => true,
               "keys" => array ( "id" => "613539316283-lofhjf03eveqe3isja890lfhfi697gqj.apps.googleusercontent.com", "secret" => "BQeGb-2qTYYxE8WN3S3sB_je" ),
            ),

         // facebook
            "Facebook" => array ( // 'id' is your facebook application id
               "enabled" => true,
               "keys" => array ( "id" => "", "secret" => "" ),
               "scope" => "email, user_about_me, user_birthday, user_hometown" // optional
            ),

         // twitter
            "Twitter" => array ( // 'key' is your twitter application consumer key
               "enabled" => true,
               "keys" => array ( "key" => "", "secret" => "" )
            ),
      		"GitHub" => array (
      				"enabled" => true,
      				"keys"=>array(
					'id' => '6450957648373b213ee9','secret' => '7be610f196918adf213edaad937fac736782aad7'),
      				"wrapper" => array( "path" => ROOT."./../vendor/hybridauth/hybridauth/additional-providers/hybridauth-github/Providers/GitHub.php", "class" => "Hybrid_Providers_GitHub" ),
      				"scope" => "user,gist,user:email"
      		)

         // and so on ...
      ),

      "debug_mode" => true ,

      // to enable logging, set 'debug_mode' to true, then provide here a path of a writable file
      "debug_file" => "./oauth.log",
    );