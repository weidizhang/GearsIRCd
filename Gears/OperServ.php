<?php
namespace GearsIRCd;

class OperServ
{
	private $SocketHandler;
	
	public function __construct($sh) {
		$this->SocketHandler = $sh;
	}
	
	public function RespondOperUp($all, $user) {
		foreach ($all as $servUser) {
			if ($servUser->Operator()) {
				if ($servUser != $user) {
					$this->SocketHandler->sendData($servUser->Socket(), "NOTICE " . $servUser->Nick() . " :" . $user->Nick() . " (" . $user->Ident() . "@" . $user->hostName . ") [" . $user->Nick() . "] is now a network administrator (N)");
				}
				$this->SocketHandler->sendData($servUser->Socket(), "NOTICE " . $servUser->Nick() . " :*** Global -- from OperServ: USERS: " . \GearsIRCd\Utilities::UserToFullHostmask($user, true) . " is now an IRC operator.");
			}
		}
	}
	
	public function RespondClientJoin($all, $user) {
		// :irc.foonet.com NOTICE FNGScraper :*** Notice -- Client connecting on port 6667: himeko (uid19529@charlton.irccloud.com) [clients]
	}
	
	public function RespondClientQuit($all, $user) {
		// :irc.foonet.com NOTICE FNGScraper :*** Notice -- Client exiting: himeko (uid19529@charlton.irccloud.com) [Quit: ]
	}
	
}
?>