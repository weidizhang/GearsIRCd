<?php
namespace GearsIRCd\lib;

class CommandHandler {	
	public static function Handle($server, $user, $index, $line) {
		$recvArgs = explode(" ", $line);
		$cmdRecv = strtolower($recvArgs[0]);
		$className = 'GearsIRCd\lib\cmd\cmd_'.$cmdRecv;
		
		if (class_exists($className)) {
			$className::run($server, $user, $line, $recvArgs);
		}
	}
}
?>