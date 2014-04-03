<?php
namespace GearsIRCd\lib\cmd;

class cmd_part {
	public static function run($server, $user, $line, $args) {
		if (isset($args[1])) {
			$chansPart = explode(",", $args[1]);
			foreach ($chansPart as $chan) {
				if (substr($chan, 0, 1) == "#") {
					$chanExists = false;
					$chanIndex = 0;
					foreach ($server->allChannels as $cIndex => $chanx) {
						if (strtolower($chanx->Name()) == strtolower($chan)) {
							$chanExists = true;
							$chanIndex = $cIndex;
							break;
						}
					}
					
					if (!$chanExists) {
						$server->SocketHandler->sendData($user->Socket(), "403 " . $user->Nick() . " " . $chan . " :No such channel");
					}
					else {
						$chanObj = $server->allChannels[$chanIndex];
						if ($chanObj->IsUserInChannel($user)) {
							foreach ($chanObj->users as $cUser) {
								$server->SocketHandler->sendRaw($cUser->Socket(), ":" . Utilities::UserToFullHostmask($user) . " PART " . $chanObj->Name());
							}
							$chanObj->RemoveUser($user);
						}
						else {
							$server->SocketHandler->sendData($user->Socket(), "422 " . $user-Nick() . " " . $chan . " :You're not on that channel");
						}
					}
				}
			}
		}
	}
}