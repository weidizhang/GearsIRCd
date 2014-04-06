<?php
namespace GearsIRCd;

class OperServ
{
	private $SocketHandler;
	
	private $fakeUser;
	
	public function __construct($sh, $servAddr) {
		$this->SocketHandler = $sh;
		
		$this->fakeUser = new \GearsIRCd\User(false, -1, "127.0.0.1", "localhost");
		$this->fakeUser->Operator(true);
		$this->fakeUser->Nick("OperServ", array(), array(), true);
		$this->fakeUser->Ident("services");
		$this->fakeUser->Hostmask($servAddr);
		$this->fakeUser->Realname("IRCd Service");
	}
	
	private function NoticeOperators($all, $cmd, $exclude = null) {
		foreach ($all as $servUser) {
			if ($servUser->Operator()) {
				if ((($exclude != null) && ($exclude != $servUser)) || $exclude == null) {
					$this->SocketHandler->sendData($servUser->Socket(), "NOTICE " . $servUser->Nick() . " " . $cmd);
				}
			}
		}
	}
	
	public function AsUser() {
		return $this->fakeUser;
	}
	
	public function RespondOperUp($all, $user) {
		$this->NoticeOperators($all, ":" . $user->Nick() . " (" . \GearsIRCd\Utilities::UserToShortHostmask($user) . ") [" . $user->Nick() . "] is now a network administrator (N)", $user);
		$this->NoticeOperators($all, ":*** Global -- from OperServ: USERS: " . \GearsIRCd\Utilities::UserToFullHostmask($user, true) . " is now an IRC operator.");
	}
	
	public function RespondFailedOper($all, $user) {
		$this->NoticeOperators($all, ":Failed OPER attempt by " . $user->Nick() . " (" . \GearsIRCd\Utilities::UserToShortHostmask($user) . ") using UID " . $user->Nick() . " [FAILEDAUTH]");
	}
	
	public function RespondClientJoin($all, $user, $port) {
		$this->NoticeOperators($all, ":*** Notice -- Client connecting on port " . $port . ": " . $user->Nick() . " (" . \GearsIRCd\Utilities::UserToShortHostmask($user) . ") [clients]");
	}
	
	public function RespondClientQuit($all, $user, $quitMsg) {
		$this->NoticeOperators($all, ":*** Notice -- Client exiting: " . $user->Nick() . " (" . \GearsIRCd\Utilities::UserToShortHostmask($user) . ") [" . $quitMsg . "]");
	}
	
	public function RespondKill($all, $userKill, $user, $killMsg) {
		$this->NoticeOperators($all, ":*** Notice -- Received KILL message for " . \GearsIRCd\Utilities::UserToFullHostmask($userKill) . " from " . $user->Nick() . " Path: " . $user->Hostmask() . "!" . $user->Nick() . " (" . $killMsg . ")");
	}
	
}
?>