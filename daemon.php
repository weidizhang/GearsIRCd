<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
set_time_limit(0);
foreach (glob("./Gears/*.php") as $gearsClass) {
	require $gearsClass;
}

if (file_exists("./gears.json")) {
	$getConfig = json_decode(file_get_contents("./gears.json"), true);
	if ($getConfig === false || !isset($getConfig["Server"]) || !isset($getConfig["Operators"])) {
		die("Error: Invalid configuration file syntax");
	}
	
	$ircServer = new \GearsIRCd\Server($getConfig["Server"]);
	foreach ($getConfig["Operators"] as $Operator) {
		$ircServer->addOperator($Operator);
	}
	
	$ircServer->startServer();
	while (true) {
		$ircServer->listenOnce();
		usleep(200000); // so the CPU load doesn't go (too) high
	}	
}
else {
	die("Error: gears.json configuration file not found.");
}
?>