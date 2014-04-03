<?php
namespace GearsIRCd\lib\cmd;

class cmd_oper {
	public static function run($server, $user, $line, $args) {
		if (isset($args[2])) {
			$username = $args[1];
			$password = $args[2];
			
			$isOper = false;
			foreach ($server->configOpers as $oper) {
				if (($oper["Username"] == $username) && (hash($oper["PasswordHashMethod"], $password) === $oper["Password"])) {
					$isOper = true;
					$user->Operator(true);
					$server->SocketHandler->sendData($user->Socket(), "381 " . $user->Nick() . " :You are now an IRC Operator");
					break;
				}
			}
			
			if ($isOper === false) {
				$server->SocketHandler->sendData($user->Socket(), "491 " . $user->Nick() . " :No O-lines for your host");
				$user->failedOperAttempts++;
				if ($user->failedOperAttempts >= 6) {
					// issue KILL for user here
				}
			}
		}
	}
}