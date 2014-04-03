<?php
namespace GearsIRCd\lib\cmd;

class cmd_topic {
	public static function run($server, $user, $line, $args, $chan = null) {
		$channel = "";
		if ($chan != null) {
			$chanTopic = $chan->Topic();
			if ($chanTopic[0] != null) {
				$server->SocketHandler->sendData($user->Socket(), "332 " . $user->Nick() . " " . $chan->Name() . " :" . $chanTopic[0]);
				$server->SocketHandler->sendData($user->Socket(), "333 " . $user->Nick() . " " . $chan->Name() . " " . $chanTopic[2] . " " . $chanTopic[1]);
			}
			else {
				$server->SocketHandler->sendData($user->Socket(), "331 " . $user->Nick() . " " . $chan->Name() . " :No topic is set.");
			}
			return true;
		}
		else {
			if (count($args) >= 1) {
				$channel = trim($args[1]);
			}
			else {
				return false;
			}
		}
		
		if (substr($channel, 0, 1) == "#") {
			foreach ($server->allChannels as $chan) {
				if (strtolower($channel) == strtolower($chan->Name())) {
					if (strpos($line, ":") !== false) {
						if ($chan->IsHalfOpOrAbove($user)) {
							$newTopic = substr($line, strpos($line, ":") + 1);
							$chanTopic = $chan->Topic($newTopic, $user);
							
							foreach ($chan->users as $cUser) {
								$server->SocketHandler->sendRaw($cUser->Socket(), ":" . Utilities::UserToFullHostmask($user) . " TOPIC " . $chan->Name() . " :" . $chanTopic[0]);
							}
							return true;
						}
						else {
							$server->SocketHandler->sendData($user->Socket(), "482 " . $user->Nick() . " " . $chan->Name() . " :You're not channel operator");
							return false;
						}
					}
				
					$chanTopic = $chan->Topic();
					if ($chanTopic[0] != null) {
						$server->SocketHandler->sendData($user->Socket(), "332 " . $user->Nick() . " " . $chan->Name() . " :" . $chanTopic[0]);
						$server->SocketHandler->sendData($user->Socket(), "333 " . $user->Nick() . " " . $chan->Name() . " " . $chanTopic[2] . " " . $chanTopic[1]);
					}
					else {
						$server->SocketHandler->sendData($user->Socket(), "331 " . $user->Nick() . " " . $chan->Name() . " :No topic is set.");
					}
				}
			}
		}
	}
}