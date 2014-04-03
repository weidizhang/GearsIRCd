<?php
namespace GearsIRCd;
use GearsIRCd\lib\Server;

set_time_limit(0);
spl_autoload_register(function ($className) {
	$namespaces = explode('\\', $className);
	if (count($namespaces) > 1) {
		array_shift($namespaces);
		$classPath = dirname(__FILE__) . '/' . implode('/', $namespaces) . '.class.php';
		if (file_exists($classPath)) {
			require_once($classPath);
		}
	}
});

require_once("config.inc.php");
$ircServer = new Server($settings, $opers);

$ircServer->startServer();

?>