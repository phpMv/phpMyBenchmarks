<?php
global $argv;
$_steps=1000000;
$_status="success";
%preparation%
$_start=microtime(true);
ob_start();
for($_increment=0;$_increment<$_steps;$_increment++){
	%test%
}
$_end=microtime(true);
ob_clean();

ob_start();
%test%
$_content=ob_get_clean();
$_calc=($_end-$_start)/$_steps;
if(strpos($_content, "error")!==false)
	$_status="error";
echo "{\"time\":".$_calc.",\"content\":\"".htmlentities($_content)."\",\"status\":\"".$_status."\",\"form\":\"".$argv[2]."\"}";
exit(0);