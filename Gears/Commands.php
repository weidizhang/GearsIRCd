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
				$this->RespondNick($user, $index, $line, $recvArgs);
				break;
				
			case "user":
				$this->RespondUser($user, $index, $line, $recvArgs);
				break;
				
			case "motd":
				$this->RespondMotd($user);
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
	
	public function RespondNick($user, $index, $line, $args) {
		if (isset($args[1])) {
			$newNick = trim(ltrim($args[1], ":"));
			$changeNick = $user->Nick($newNick, $this->reservedNicks, $this->allUsers);
			if ($changeNick === true) {
				$this->allUsers[$index]->Nick($newNick, $this->reservedNicks, $this->allUsers);
			}
			else {
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
	
	public function RespondUser($user, $index, $line, $args) {
		if ((count($args) >= 5) && ($user->Nick() != null) && ($user->Ident() == null)) {
			$ident = $args[1];
			$realName = ltrim($args[4], ":");
			$user->Ident($ident);
			$user->Realname($realName);
			$this->allUsers[$index]->Ident($ident);
			$this->allUsers[$index]->Realname($realName);
			
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
}
?>