<?php
namespace GearsIRCd\lib\cmd;
use GearsIRCd\lib\Utilities;
use GearsIRCd\lib\Channel;

class cmd_join {
	public static function run($server, $user, $line, $args) {
		if (isset($args[1])) {
			$chansJoin = explode(",", $args[1]);
			foreach ($chansJoin as $channel) {
				$channel = trim($channel);
				if (substr($channel, 0, 1) == "#") {
				
					if (!Utilities::ValidateChannel($channel)) {
						$server->SocketHandler->sendData($user->Socket(), "403 " . $user->Nick() . " " . $channel . " :No such channel");
						break;
					}
				
					$channelExists = false;
					$channelIndex = 0;
					
					foreach ($server->allChannels as $cIndex => $chan) {
						if (strtolower($chan->Name()) == strtolower($channel)) {
							$channelExists = true;
							$channelIndex = $cIndex;
							break;
						}
					}
					
					if ($channelExists === true) {
						if (!$server->allChannels[$cIndex]->IsBanned($user)) {
							$addUser = $server->allChannels[$cIndex]->AddUser($user);
							if ($addUser === true) {
								foreach ($server->allChannels[$cIndex]->users as $cUser) {
									$server->SocketHandler->sendRaw($cUser->Socket(), ":" . Utilities::UserToFullHostmask($user) . " JOIN " . $server->allChannels[$cIndex]->Name());
								}
								// services stuff here for later (chanserv)
							}
						}
						else {
							$server->SocketHandler->sendData($user->Socket(), "474 " . $user->Nick() . " " . $server->allChannels[$cIndex]->Name(). " :Cannot join channel (+b)");
							break;
						}
					}
					else {
						$newChannel = new Channel($channel);
						$newChannel->AddUser($user);
						$server->allChannels[] = $newChannel;
						$server->SocketHandler->sendCommand($user, "JOIN " . $channel);
					}
					cmd_names::run($server, $user, $line, $args, $channel);
				}
			}
		}
	}
}