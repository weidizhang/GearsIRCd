<?php
namespace GearsIRCd\lib\cmd;

class cmd_motd {
	public static function run($server, $user, $line, $args) {
		$server->SocketHandler->sendData($user->Socket(), "375 " . $user->Nick() . " :- " . $server->name . " Message of the Day -");
		$motdLines = explode("\n", $server->motd);
		foreach ($motdLines as $line) {
			$server->SocketHandler->sendData($user->Socket(), "372 " . $user->Nick() . " :- " . rtrim($line));
		}
		$server->SocketHandler->sendData($user->Socket(), "376 " . $user->Nick() . " :End of /MOTD command.");
	}
}