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

if (version_compare(PHP_VERSION, "5.4.0", "<")) {
	die("PHP 5.4+ is required. You have PHP " . PHP_VERSION . ".");
}

$jsonfilename = "./gears.json";
if (file_exists($jsonfilename)) {
	$getConfig = json_decode(file_get_contents($jsonfilename), true);
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
		usleep(10000); // so the CPU load doesn't go (too) high
	}	
}
else {
	die("Error: " . $jsonfilename . " configuration file not found.");
}
?>