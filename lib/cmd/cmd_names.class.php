<?php
namespace GearsIRCd\lib\cmd;

class cmd_names {
	public static function run($server, $user, $line, $args, $chan = null) {
		$channel = "";
		if (!empty($chan)) {
			$channel = $chan;
		}
		else{
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
					$usersList = "";
					foreach ($chan->users as $cUser) {
						if ($chan->OwnerMode($cUser)) {
							$usersList .= "~" . $cUser->Nick() . " ";
						}
						elseif ($chan->AdminMode($cUser)) {
							$usersList .= "&" . $cUser->Nick() . " ";
						}
						elseif ($chan->OperatorMode($cUser)) {
							$usersList .= "@" . $cUser->Nick() . " ";
						}
						elseif ($chan->HalfopMode($cUser)) {
							$usersList .= "%" . $cUser->Nick() . " ";
						}
						elseif ($chan->VoiceMode($cUser)) {
							$usersList .= "+" . $cUser->Nick() . " ";
						}
						else {
							$usersList .= $cUser->Nick() . " ";
						}
					}
					$usersList = trim($usersList);
					
					$server->SocketHandler->sendData($user->Socket(), "353 " . $user->Nick() . " = " . $chan->Name() . " :" . $usersList);
					$server->SocketHandler->sendData($user->Socket(), "366 " . $user->Nick() . " " . $chan->Name() . " :End of /NAMES list.");
					
					cmd_topic::run($server, $user, "", array(), $chan);
					
					break;
				}
			}
		}
	}
}