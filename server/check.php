<?php
global $argv;
$file=$argv[1];
$errors=exec('php -l '.$file);
if(strpos($errors, 'No syntax errors detected') === false){
	echo "{\"status\":\"error\",\"content\":\"".$errors."\",\"form\":\"".$argv[2]."\"}";
	exit(0);
}else {
	include $file;
}