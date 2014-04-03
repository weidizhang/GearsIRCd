<?php
namespace GearsIRCd\lib\cmd;

class cmd_ping {
	public static function run($server, $user, $line, $args) {
		$pingResp = substr($line, 5);
		if (substr($pingResp, 0, 1) != ":") {
			$pingResp = ":" . $pingResp;
		}
		$server->SocketHandler->sendRaw($user->Socket(), "PONG " . $server->addr . " " . $pingResp);
	}
}