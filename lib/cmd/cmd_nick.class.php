<?php
namespace GearsIRCd\lib\cmd;

class cmd_nick {
	public static function run($server, $user, $line, $args) {
		if (isset($args[1])) {
			$newNick = trim(ltrim($args[1], ":"));
			$changeNick = $user->Nick($newNick, $server->reservedNicks, $server->allUsers);
			if (!$changeNick) {
				$errorMsg = "";
				if ($changeNick === 10) {
					$errorMsg = "432 " . $newNick . " :Erroneous Nickname";
				}
				elseif ($changeNick === 11) {
					$errorMsg = "433 " . $newNick . " :Nickname is already in use";
				}
				else {
					$errorMsg = "432 " . $newNick . " :Erroneous Nickname: Reserved for services";
				}
				$server->SocketHandler->sendData($user->Socket(), $errorMsg);
			}
		}
	}
}