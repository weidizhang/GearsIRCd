<?php
namespace GearsIRCd\lib\cmd;

class cmd_privmsg {
	public static function run($server, $user, $line, $args) {
		if (isset($args[2])) {
			$chanTo = $args[1];
			$msgPosition = strpos($line, ":");
			if ($msgPosition !== false) {
				$msg = substr($line, $msgPosition + 1);
				if (substr($chanTo, 0, 1) == "#") {
					$chanExists = false;
					foreach ($server->allChannels as $chan) {
						if (strtolower($chanTo) == strtolower($chan->Name())) {
							$chanExists = true;
							$oldChanTo = $chanTo;
							$chanTo = $chan->Name();
							
							if ($chan->IsUserInChannel($user)) {
								if ($chan->IsBanned($user)) {
									$server->SocketHandler->sendData($user->Socket(), "404 " . $user->Nick() . " " . $chanTo . " :You are banned (" . $oldChanTo . ")");
								}
								elseif (($chan->ModerationMode() === true) && (!$chan->IsVoiceOrAbove($user))) {
									$server->SocketHandler->sendData($user->Socket(), "404 " . $user->Nick() . " " . $chanTo . " :You need voice (+v) (" . $oldChanTo . ")");
								}
								else {									
									foreach ($chan->users as $cUser) {
										if ($cUser != $user) {
											$server->SocketHandler->sendRaw($cUser->Socket(), ":" . Utilities::UserToFullHostmask($user) . " PRIVMSG " . $chanTo . " :" . $msg);
										}
									}
								}
							}
							else {
								$server->SocketHandler->sendData($user->Socket(), "404 " . $user->Nick() . " " . $chanTo . " :No external channel messages (" . $oldChanTo . ")");
							}
							break;
						}
					}
					
					if ($chanExists === false) {
						$server->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $chanTo . " :No such nick/channel");
					}
				}
				else {
					if (strtolower($chanTo) == "nickserv") {
						// to do: implement when we code services
					}
					elseif (strtolower($chanTo) == "chanserv") {
						// to do: implement when we code services
					}
					elseif (strtolower($chanTo) == "operserv") {
						// to do: implement when we code services
					}
					elseif (strtolower($chanTo) == "botserv") {
						// to do: implement when we code services
					}
					else {
						$userExists = false;
						
						foreach ($server->allUsers as $serverUser) {
							if (strtolower($serverUser->Nick()) == strtolower($chanTo)) {
								$userExists = true;
								$server->SocketHandler->sendRaw($serverUser->Socket(), ":" . Utilities::UserToFullHostmask($user) . " PRIVMSG " . $serverUser->Nick() . " :" . $msg);
								break;
							}
						}
						
						if ($userExists === false) {
							$server->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $chanTo . " :No such nick/channel");
						}
					}
				}
			}
		}
	}
}