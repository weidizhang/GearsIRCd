<?php
namespace GearsIRCd;

class Commands
{	
	public function HandleCommand($user, $index, $line) {
		$recvArgs = explode(" ", $line);
		$cmdRecv = strtolower($recvArgs[0]);
		
		switch ($cmdRecv) {
			case "ping":
				$this->RespondPing($user, $line);
				break;
				
			case "nick":
				$this->RespondNick($user, $line, $recvArgs);
				break;
				
			case "user":
				$this->RespondUser($user, $line, $recvArgs);
				break;
				
			case "motd":
				$this->RespondMotd($user);
				break;
				
			case "join":
				$this->RespondJoin($user, $line, $recvArgs);
				break;
				
			case "part":
				$this->RespondPart($user, $line, $recvArgs);
				break;
				
			case "names":
				$this->RespondNames($user, $recvArgs);
				break;
				
			case "topic":
				$this->RespondTopic($user, $line, $recvArgs);
				break;
				
			case "privmsg":
				$this->RespondPrivmsg($user, $line, $recvArgs);
				break;
				
			case "oper":
				$this->RespondOper($user, $recvArgs);
				break;
				
			case "chghost":
				$this->RespondChghost($user, $recvArgs);
				break;
				
			case "quit":
				$this->RespondQuit($user, $line);
				break;
				
			case "kill":
				$this->RespondKill($user, $line, $recvArgs);
				break;
				
			case "mode":
				$this->RespondMode($user, $recvArgs);
				break;
				
			default:
				break;
		}
	}
	
	public function RespondPing($user, $line) {
		$pingResp = substr($line, 5);
		if (substr($pingResp, 0, 1) != ":") {
			$pingResp = ":" . $pingResp;
		}
		$this->SocketHandler->sendRaw($user->Socket(), "PONG " . $this->addr . " " . $pingResp);
	}
	
	public function RespondNick($user, $line, $args) {
		if (isset($args[1])) {
			$newNick = trim(ltrim($args[1], ":"));
			$changeNick = $user->Nick($newNick, $this->reservedNicks, $this->allUsers);
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
				$this->SocketHandler->sendData($user->Socket(), $errorMsg);
			}
		}
	}
	
	public function RespondUser($user, $line, $args) {
		if ((count($args) >= 5) && ($user->Nick() != null) && ($user->Ident() == null)) {
			$ident = $args[1];
			$realName = ltrim($args[4], ":");
			$user->Ident($ident);
			$user->Realname($realName);
			
			$createdTS = file_get_contents("createdstamp-ircd");
			$timeCreated = date("D M d Y", $createdTS) . " at " . date("H:i:s", $createdTS);
			$operCount = 0;
			foreach ($this->allUsers as $user) {
				if ($user->Operator() === true) {
					$operCount++;
				}
			}
			
			$sendArray = array(
				array("001", ":Welcome to the " . $this->name . " IRC Network " . $user->Nick() . "!" . $user->Ident() . "@" . $user->Hostmask()),
				array("002", ":Your host is " . $this->addr . " running version " . $this->ircdVer),
				array("003", ":This server was created " . $timeCreated),
				array("004", ":" . $this->addr . " " . $this->ircdVer . "  iowghraAsORTVSxNCWqBzvdHtGp lvhopsmntikrRcaqOALQbSeIKVfMCuzNTGjZ"),
				array("005", "UHNAMES NAMESX SAFELIST HCN MAXCHANNELS=" . $this->maxChans . " CHANLIMIT=#:" . $this->maxChans . " MAXLIST=b:60,e:60,I:60 NICKLEN=30 CHANNELLEN=32 TOPICLEN=307 KICKLEN=307 AWAYLEN=307 MAXTARGETS=20 :are supported by this server"),
				array("005", "WALLCHOPS WATCH=128 WATCHOPTS=A SILENCE=15 MODES=12 CHANTYPES=# PREFIX=(qaohv)~&@%+ CHANMODES=beI,kfL,lj,psmntirRcOAQKVCuzNSMTGZ NETWORK=" . $ircSettings["name"] . " CASEMAPPING=ascii EXTBAN=~,qjncrRT ELIST=MNUCT STATUSMSG=~&@%+ :are supported by this server"),
				array("005", "EXCEPTS INVEX CMDS=KNOCK,MAP,DCCALLOW,USERIP :are supported by this server"),
				array("251", ":There are " . count($this->allUsers) . " users and " . count($this->allUsers) . " invisible on 1 servers"),
				array("252", $operCount . " :operator(s) online"),
				array("254", count($this->allChannels) . " :channels formed"),
				array("255", ":I have " . count($this->allUsers) . " clients and 0 servers"),
				array("265", ":Current Local Users: " . count($this->allUsers) . " Max: " . $this->maxUsers),
				array("265", ":Current Global Users: " . count($this->allUsers) . " Max: " . $this->maxUsers)
			);
			
			foreach ($sendArray as $msgArgs) {
				$this->SocketHandler->sendData($user->Socket(), $msgArgs[0] . " " . $user->Nick() . " " . $msgArgs[1]);
			}
			$this->RespondMotd($user);
			
			$this->Services->OperServ->RespondClientJoin($this->allUsers, $user, $this->port);
		}
	}
	
	public function RespondMotd($user) {
		$this->SocketHandler->sendData($user->Socket(), "375 " . $user->Nick() . " :- " . $this->name . " Message of the Day -");
		$motdLines = explode("\n", $this->motd);
		foreach ($motdLines as $line) {
			$this->SocketHandler->sendData($user->Socket(), "372 " . $user->Nick() . " :- " . rtrim($line));
		}
		$this->SocketHandler->sendData($user->Socket(), "376 " . $user->Nick() . " :End of /MOTD command.");
	}
	
	public function RespondJoin($user, $line, $args) {
		if (isset($args[1])) {
			$chansJoin = explode(",", $args[1]);
			foreach ($chansJoin as $channel) {
				$channel = trim($channel);
				if (substr($channel, 0, 1) == "#") {
				
					if (!\GearsIRCd\Utilities::ValidateChannel($channel)) {
						$this->SocketHandler->sendData($user->Socket(), "403 " . $user->Nick() . " " . $channel . " :No such channel");
						break;
					}
				
					$channelExists = false;
					$channelIndex = 0;
					
					foreach ($this->allChannels as $cIndex => $chan) {
						if (strtolower($chan->Name()) == strtolower($channel)) {
							$channelExists = true;
							$channelIndex = $cIndex;
							break;
						}
					}
					
					if ($channelExists === true) {
						if (!$this->allChannels[$cIndex]->IsBanned($user)) {
							$addUser = $this->allChannels[$cIndex]->AddUser($user);
							if ($addUser === true) {
								foreach ($this->allChannels[$cIndex]->users as $cUser) {
									$this->SocketHandler->sendRaw($cUser->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " JOIN " . $this->allChannels[$cIndex]->Name());
								}
								// services stuff here for later (chanserv)
							}
						}
						else {
							$this->SocketHandler->sendData($user->Socket(), "474 " . $user->Nick() . " " . $this->allChannels[$cIndex]->Name(). " :Cannot join channel (+b)");
							break;
						}
					}
					else {
						$newChannel = new \GearsIRCd\Channel($channel);
						$newChannel->AddUser($user);
						$this->allChannels[] = $newChannel;
						$this->SocketHandler->sendCommand($user, "JOIN " . $channel);
					}
					$this->RespondNames($user, $args, $channel);
				}
			}
		}
	}
	
	public function RespondPart($user, $line, $args) {
		if (isset($args[1])) {
			$chansPart = explode(",", $args[1]);
			foreach ($chansPart as $chan) {
				if (substr($chan, 0, 1) == "#") {
					$chanExists = false;
					$chanIndex = 0;
					foreach ($this->allChannels as $cIndex => $chanx) {
						if (strtolower($chanx->Name()) == strtolower($chan)) {
							$chanExists = true;
							$chanIndex = $cIndex;
							break;
						}
					}
					
					if (!$chanExists) {
						$this->SocketHandler->sendData($user->Socket(), "403 " . $user->Nick() . " " . $chan . " :No such channel");
					}
					else {
						$chanObj = $this->allChannels[$chanIndex];
						if ($chanObj->IsUserInChannel($user)) {
							foreach ($chanObj->users as $cUser) {
								$this->SocketHandler->sendRaw($cUser->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " PART " . $chanObj->Name());
							}
							$chanObj->RemoveUser($user);
						}
						else {
							$this->SocketHandler->sendData($user->Socket(), "422 " . $user-Nick() . " " . $chan . " :You're not on that channel");
						}
					}
				}
			}
		}
	}
	
	public function RespondNames($user, $args, $chan = "") {
		$channel = "";
		if (!empty($chan)) {
			$channel = $chan;
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
			foreach ($this->allChannels as $chan) {
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
					
					$this->SocketHandler->sendData($user->Socket(), "353 " . $user->Nick() . " = " . $chan->Name() . " :" . $usersList);
					$this->SocketHandler->sendData($user->Socket(), "366 " . $user->Nick() . " " . $chan->Name() . " :End of /NAMES list.");
					
					$this->RespondTopic($user, "", array(), $chan);
					
					break;
				}
			}
		}
	}
	
	public function RespondTopic($user, $line, $args, $chan = null) {
		$channel = "";
		
		if ($chan != null) {
			$chanTopic = $chan->Topic();
			if ($chanTopic[0] != null) {
				$this->SocketHandler->sendData($user->Socket(), "332 " . $user->Nick() . " " . $chan->Name() . " :" . $chanTopic[0]);
				$this->SocketHandler->sendData($user->Socket(), "333 " . $user->Nick() . " " . $chan->Name() . " " . $chanTopic[2] . " " . $chanTopic[1]);
			}
			else {
				$this->SocketHandler->sendData($user->Socket(), "331 " . $user->Nick() . " " . $chan->Name() . " :No topic is set.");
			}
			return true;
		}
		else {
			if (count($args) > 1) {
				$channel = trim($args[1]);
			}
			else {
				return false;
			}
		}
		
		if (substr($channel, 0, 1) == "#") {
			foreach ($this->allChannels as $chan) {
				if (strtolower($channel) == strtolower($chan->Name())) {
					if (strpos($line, ":") !== false) {
						if ($chan->IsHalfOpOrAbove($user)) {
							$newTopic = substr($line, strpos($line, ":") + 1);
							$chanTopic = $chan->Topic($newTopic, $user);
							
							foreach ($chan->users as $cUser) {
								$this->SocketHandler->sendRaw($cUser->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " TOPIC " . $chan->Name() . " :" . $chanTopic[0]);
							}
							return true;
						}
						else {
							$this->SocketHandler->sendData($user->Socket(), "482 " . $user->Nick() . " " . $chan->Name() . " :You're not channel operator");
							return false;
						}
					}
				
					$chanTopic = $chan->Topic();
					if ($chanTopic[0] != null) {
						$this->SocketHandler->sendData($user->Socket(), "332 " . $user->Nick() . " " . $chan->Name() . " :" . $chanTopic[0]);
						$this->SocketHandler->sendData($user->Socket(), "333 " . $user->Nick() . " " . $chan->Name() . " " . $chanTopic[2] . " " . $chanTopic[1]);
					}
					else {
						$this->SocketHandler->sendData($user->Socket(), "331 " . $user->Nick() . " " . $chan->Name() . " :No topic is set.");
					}
				}
			}
		}
	}
	
	public function RespondPrivmsg($user, $line, $args) {
		if (isset($args[2])) {
			$chanTo = $args[1];
			$msgPosition = strpos($line, ":");
			if ($msgPosition !== false) {
				$msg = substr($line, $msgPosition + 1);
				if (substr($chanTo, 0, 1) == "#") {
					$chanExists = false;
					foreach ($this->allChannels as $chan) {
						if (strtolower($chanTo) == strtolower($chan->Name())) {
							$chanExists = true;
							$oldChanTo = $chanTo;
							$chanTo = $chan->Name();
							
							if ($chan->IsUserInChannel($user)) {
								if ($chan->IsBanned($user)) {
									$this->SocketHandler->sendData($user->Socket(), "404 " . $user->Nick() . " " . $chanTo . " :You are banned (" . $oldChanTo . ")");
								}
								elseif (($chan->ModerationMode() === true) && (!$chan->IsVoiceOrAbove($user))) {
									$this->SocketHandler->sendData($user->Socket(), "404 " . $user->Nick() . " " . $chanTo . " :You need voice (+v) (" . $oldChanTo . ")");
								}
								else {									
									foreach ($chan->users as $cUser) {
										if ($cUser != $user) {
											$this->SocketHandler->sendRaw($cUser->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " PRIVMSG " . $chanTo . " :" . $msg);
										}
									}
								}
							}
							else {
								$this->SocketHandler->sendData($user->Socket(), "404 " . $user->Nick() . " " . $chanTo . " :No external channel messages (" . $oldChanTo . ")");
							}
							break;
						}
					}
					
					if ($chanExists === false) {
						$this->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $chanTo . " :No such nick/channel");
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
						
						foreach ($this->allUsers as $serverUser) {
							if (strtolower($serverUser->Nick()) == strtolower($chanTo)) {
								$userExists = true;
								$this->SocketHandler->sendRaw($serverUser->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " PRIVMSG " . $serverUser->Nick() . " :" . $msg);
								break;
							}
						}
						
						if ($userExists === false) {
							$this->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $chanTo . " :No such nick/channel");
						}
					}
				}
			}
		}
	}
	
	public function RespondOper($user, $args) {
		if ($user->Operator()) {
			$this->SocketHandler->sendData($user->Socket(), "381 " . $user->Nick() . " :You are now an IRC Operator");
		}		
		elseif (isset($args[2])) {
			$username = $args[1];
			$password = $args[2];
			
			$isOper = false;
			foreach ($this->configOpers as $oper) {
				if (($oper["Username"] == $username) && (hash($oper["PasswordHashMethod"], $password) === $oper["Password"])) {
					$isOper = true;
					$user->Operator(true);
					$this->SocketHandler->sendData($user->Socket(), "381 " . $user->Nick() . " :You are now an IRC Operator");
					$this->Services->OperServ->RespondOperUp($this->allUsers, $user);
					break;
				}
			}
			
			if ($isOper === false) {
				$this->SocketHandler->sendData($user->Socket(), "491 " . $user->Nick() . " :No O-lines for your host");
				$user->failedOperAttempts++;
				$this->Services->OperServ->RespondFailedOper($this->allUsers, $user);
				if ($user->failedOperAttempts >= 5) {
					$this->RespondKill($this->Services->OperServ->AsUser(), "KILL " . $user->Nick() . " :Too many failed oper attempts", array(true, $user->Nick(), true));
				}
			}
		}
	}
	
	public function RespondChghost($user, $args) {
		if ($user->Operator()) {
			if (isset($args[2])) {				
				$nick = $args[1];
				$newHost = $args[2];
				
				$userExists = false;
				foreach ($this->allUsers as $servUser) {
					if (strtolower($servUser->Nick()) === strtolower($nick)) {
						$userExists = true;
						if (\GearsIRCd\Utilities::ValidateHostmask($newHost)) {
							$user->Hostmask($newHost);
						}
						else {
							$this->SocketHandler->sendData($user->Socket(), "NOTICE " . $user->Nick() . " :*** /ChgHost Error: A hostname may contain a-z, A-Z, 0-9, '-' & '.' - Please only use them");
						}
						break;
					}
				}
				
				if (!$userExists) {
					$this->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $nick . " :No such nick/channel");
				}
			}
			else {
				$this->SocketHandler->sendData($user->Socket(), "461 " . $user->Nick() . " CHGHOST :Not enough parameters");
			}
		}
		else {
			$this->SocketHandler->sendData($user->Socket(), "481 " . $user->Nick() . " :Permission Denied- You do not have the correct IRC operator privileges");
		}
	}
	
	public function RespondQuit($user, $line, $killMsg = "") {
		socket_close($user->Socket());
		$quitMessage = "Quit: ";
		$customQuit = strpos($line, ":");
		if ($customQuit !== false) {
			$quitMessage = "Quit: " . substr($line, $customQuit + 1);
		}
		if (!empty($killMsg)) {
			$quitMessage = $killMsg;
		}
		
		$sentTo = array($user);
		foreach ($this->allChannels as $chan) {
			if ($chan->IsUserInChannel($user)) {
				$chan->RemoveUser($user);
				
				foreach ($chan->users as $cUser) {
					if (!in_array($cUser, $sentTo)) {
						$this->SocketHandler->sendRaw($cUser->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " QUIT :" . $quitMessage);
						$sentTo[] = $cUser;
					}
				}
			}
		}
				
		$this->allUsers = \GearsIRCd\Utilities::RemoveFromArray($this->allUsers, $user);
		$this->Services->OperServ->RespondClientQuit($this->allUsers, $user, $quitMessage);		
	}
	
	public function RespondKill($user, $line, $args) {
		if ($user->Operator()) {
			if (isset($args[2])) {
				$nick = $args[1];
				$userExists = false;
				$userKill = null;
				foreach ($this->allUsers as $servUser) {
					if (strtolower($servUser->Nick()) === strtolower($nick)) {
						$userExists = true;
						$userKill = $servUser;
						break;
					}
				}
				
				if ($userExists) {
					$killReason = substr($line, strpos($line, ":") + 1);
					$killMsg = "[" . $this->addr . "] Local kill by " . $user->Nick() . " (" . $killReason . ")";
					$this->Services->OperServ->RespondKill($this->allUsers, $userKill, $user, $killReason);
					$this->SocketHandler->sendRaw($userKill->Socket(), "ERROR :Closing Link: " . $userKill->Nick() . "[" . $userKill->hostName . "] " . $user->Nick() . " (" . $killMsg . ")");
					$this->RespondQuit($userKill, "", $killMsg);
				}
				else {
					$this->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $nick . " :No such nick/channel");
				}
			}
			else {
				$this->SocketHandler->sendData($user->Socket(), "461 " . $user->Nick() . " KILL :Not enough parameters");				
			}
		}
		else {
			$this->SocketHandler->sendData($user->Socket(), "481 " . $user->Nick() . " :Permission Denied- You do not have the correct IRC operator privileges");
		}
	}
	
	public function RespondMode($user, $args) {
		if (isset($args[1])) {
			$channel = $args[1];
			if (substr($channel, 0, 1) == "#") {
				$chanObj = null;
				$chanExists = false;
				foreach ($this->allChannels as $chan) {
					if (strtolower($channel) === strtolower($chan->Name())) {
						$chanObj = $chan;
						$chanExists = true;
						break;
					}
				}
				
				if ($chanExists) {
					if (isset($args[2])) {
						if ($chanObj->IsHalfOpOrAbove($user)) {
							$curMode = "+";
							$modes = str_split($args[2]);
							$modeArgIndex = 2;						
							
							$lastMode = "+";
							$changeModes = "";
							$changeArgs = "";
							
							foreach ($modes as $mode) {
								switch ($mode) {
									case "+":
									case "-":
										$curMode = $mode;
										break;
										
									case "q":
									case "a":
									case "o":
									case "h":
									case "v":
										$userExists = false;
										$chanUser = null;
										$modeArgIndex++;
										if (isset($args[$modeArgIndex])) {
											$userNick = $args[$modeArgIndex];
											foreach ($chanObj->users as $cUser) {
												if (strtolower($userNick) === strtolower($cUser->Nick())) {
													$userExists = true;
													$chanUser = $cUser;
													break;
												}
											}
											
											if ($userExists) {
												$modeFunction = strtr($mode, array(
													"q" => "OwnerMode",
													"a" => "AdminMode",
													"o" => "OperatorMode",
													"h" => "HalfopMode",
													"v" => "VoiceMode"
												));
												$permissionFunction = strtr($mode, array(
													"q" => "OwnerMode",
													"a" => "OwnerMode",
													"o" => "IsOpOrAbove",
													"h" => "IsOpOrAbove",
													"v" => "IsHalfOpOrAbove"
												));
												
												$isAlreadyMode = call_user_func(array($chanObj, $modeFunction), $chanUser);
												if ((!$isAlreadyMode && $curMode == "+") || ($isAlreadyMode && $curMode == "-")) {
													$userCanSet = call_user_func(array($chanObj, $permissionFunction), $user);
													if ($userCanSet) {
														call_user_func(array($chanObj, $modeFunction), $chanUser, true);
														if (($changeModes == "") || ($lastMode != $curMode)) {
															$changeModes .= $curMode . $mode;
															$lastMode = $curMode;
														}
														else {
															$changeModes .= $mode;
														}
														$changeArgs .= $chanUser->Nick() . " ";
													}
													else {
														if ($chanObj->HalfopMode($user) && !$chanObj->IsOpOrAbove($user)) {
															$this->SocketHandler->sendData($user->Socket(), "460 " . $user->Nick() . " :Halfops cannot set mode " . $mode);
														}
														else {
															$this->SocketHandler->sendData($user->Socket(), "499 " . $user->Nick() . " " . $chanObj->Name() . " :You're not a channel owner");
														}
													}
												}
											}
											else {
												$userIsOnServer = false;
												$actualNick = null;
												foreach ($this->allUsers as $serverUser) {
													if (strtolower($serverUser->Nick()) === strtolower($userNick)) {
														$userIsOnServer = true;
														$actualNick = $serverUser->Nick();
														break;
													}
												}
												
												if ($userIsOnServer) {
													$this->SocketHandler->sendData($user->Socket(), "441 " . $user->Nick() . " " . $actualNick . " " . $chanObj->Name() . " :They aren't on that channel");
												}
												else {
													$this->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $userNick . " :No such nick/channel");
												}
											}
										}
										break;
										
									case "m":
										$successfulChange = false;
										if ($chanObj->ModerationMode() && ($curMode == "-")) {
											$chanObj->ModerationMode(false);
											$successfulChange = true;
										}
										elseif (!$chanObj->ModerationMode() && ($curMode == "+")) {
											$chanObj->ModerationMode(true);
											$successfulChange = true;
										}
										
										if ($successfulChange) {
											if (($changeModes == "") || ($lastMode != $curMode)) {
												$changeModes .= $curMode . $mode;
												$lastMode = $curMode;
											}
											else {
												$changeModes .= $mode;
											}
										}
										break;
										
									default:
										$this->SocketHandler->sendData($user->Socket(), "472 " . $user->Nick() . " " . $mode . " :is unknown mode char to me");
										break;
								}
							}
							
							if (!empty($changeModes)) {
								$changeArgsStr =  " " . trim($changeArgs);
								$modeCommand = ":" . \GearsIRCd\Utilities::UserToFullHostmask($user) . " MODE " . $chanObj->Name() . " " . $changeModes;
								if ($changeArgsStr != " ") {
									$modeCommand .= $changeArgsStr;
								}
								foreach ($chanObj->users as $chanUser) {
									$this->SocketHandler->sendRaw($chanUser->Socket(), $modeCommand);
								}
							}
						}
						else {
							$this->SocketHandler->sendData($user->Socket(), "482 " . $user->Nick() . " " . $chanObj->Name() . " :You're not channel operator");
						}					
					}
					else {
						// to-do: return channel modes
					}
				}
				else {
					$this->SocketHandler->sendData($user->Socket(), "401 " . $user->Nick() . " " . $channel . " :No such nick/channel");
				}
			}
			else {
				// to-do: user modes
			}
		}
		else {
			$this->SocketHandler->sendData($user->Socket(), "461 " . $user->Nick() . " MODE :Not enough parameters");
		}
	}
}
?>