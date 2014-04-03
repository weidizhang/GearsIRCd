<?php
namespace GearsIRCd\lib\cmd;

class cmd_user {
	public static function run($server, $user, $line, $args) {
		if ((count($args) >= 5) && ($user->Nick() != null) && ($user->Ident() == null)) {
			$ident = $args[1];
			$realName = ltrim($args[4], ":");
			$user->Ident($ident);
			$user->Realname($realName);
			
			$createdTS = file_get_contents("createdstamp-ircd");
			$timeCreated = date("D M d Y", $createdTS) . " at " . date("H:i:s", $createdTS);
			$operCount = 0;
			foreach ($server->allUsers as $user) {
				if ($user->Operator() === true) {
					$operCount++;
				}
			}
			
			$sendArray = array(
				array("001", ":Welcome to the " . $server->name . " IRC Network " . $user->Nick() . "!" . $user->Ident() . "@" . $user->Hostmask()),
				array("002", ":Your host is " . $server->addr . " running version " . $server->ircdVer),
				array("003", ":This server was created " . $timeCreated),
				array("004", ":" . $server->addr . " " . $server->ircdVer . "  iowghraAsORTVSxNCWqBzvdHtGp lvhopsmntikrRcaqOALQbSeIKVfMCuzNTGjZ"),
				array("005", "UHNAMES NAMESX SAFELIST HCN MAXCHANNELS=" . $server->maxChans . " CHANLIMIT=#:" . $server->maxChans . " MAXLIST=b:60,e:60,I:60 NICKLEN=30 CHANNELLEN=32 TOPICLEN=307 KICKLEN=307 AWAYLEN=307 MAXTARGETS=20 :are supported by this server"),
				array("005", "WALLCHOPS WATCH=128 WATCHOPTS=A SILENCE=15 MODES=12 CHANTYPES=# PREFIX=(qaohv)~&@%+ CHANMODES=beI,kfL,lj,psmntirRcOAQKVCuzNSMTGZ NETWORK=" . $ircSettings["name"] . " CASEMAPPING=ascii EXTBAN=~,qjncrRT ELIST=MNUCT STATUSMSG=~&@%+ :are supported by this server"),
				array("005", "EXCEPTS INVEX CMDS=KNOCK,MAP,DCCALLOW,USERIP :are supported by this server"),
				array("251", ":There are " . count($server->allUsers) . " users and " . count($server->allUsers) . " invisible on 1 servers"),
				array("252", $operCount . " :operator(s) online"),
				array("254", count($server->allChannels) . " :channels formed"),
				array("255", ":I have " . count($server->allUsers) . " clients and 0 servers"),
				array("265", ":Current Local Users: " . count($server->allUsers) . " Max: " . $server->maxUsers),
				array("265", ":Current Global Users: " . count($server->allUsers) . " Max: " . $server->maxUsers)
			);
			
			foreach ($sendArray as $msgArgs) {
				$server->SocketHandler->sendData($user->Socket(), $msgArgs[0] . " " . $user->Nick() . " " . $msgArgs[1]);
			}
			cmd_motd::run($server, $user, $line, $args);
		}
	}
}