<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class ChanServ
{
	private $SocketHandler;
	
	private $fakeUser;
	
	public function __construct($sh, $servAddr) {
		$this->SocketHandler = $sh;
		
		$this->fakeUser = new \GearsIRCd\User(false, -1, "127.0.0.1", "localhost");
		$this->fakeUser->Operator(true);
		$this->fakeUser->Nick("ChanServ", array(), array(), true);
		$this->fakeUser->Ident("services");
		$this->fakeUser->Hostmask($servAddr);
		$this->fakeUser->Realname("IRCd Service");
	}
	
	public function HandleCommand($user, $line, $msg) {
		$msgArgs = explode(" ", $msg);
		$cmdRecv = strtolower($msgArgs[0]);
		
		switch($cmdRecv) {
			case "help":
				$this->RespondHelp($user, $msgArgs);
				break;
				
			default:
				$this->RespondUnknownCmd($user, $msgArgs);
				break;
		}
	}
	
	private function NoticeUser($user, $msg) {
		$this->SocketHandler->sendRaw($user->Socket(), ":" . \GearsIRCd\Utilities::UserToFullHostmask($this->AsUser()) . " NOTICE " . $user->Nick() . " :" . $msg);
	}
	
	public function RespondHelp($user, $args) {
		if (!isset($args[1])) {
			$helpInfo = array(
				"ChanServ allows you to register and control many",
				"aspects of channels. It can prevent users from",
				"taking over your channel, and allow you to auto",
				"assign privileges to registered users. The commands",
				"for ChanServ can be found below, and can be used by:",
				"/msg ChanServ commands.",
				" ",
				"For more information on a command, type:",
				"/msg ChanServ HELP command.",
				" ",
				"The following commands are available:",
				" "
			);
			$commandsList = array(
				"REGISTER" => "Register a channel",
				"IDENTIFY" => "Identify yourself with your password",
				"SET" => "Set channel options and information",
				"SOP" => "Modify the list of SOP users",
				"AOP" => "Modify the list of AOP users",
				"HOP" => "Maintains the HOP (HalfOP) list for a channel",
				"VOP" => "Maintains the VOP (VOicePeople) list for a channel",
				"DROP" => "Cancel the registration of a channel",
				"UNBAN" => "Removes all bans preventing you from entering a channel",
				"SYNC" => "Give all users the status the access list grants them"
			);
			$longestLength = max(array_map("strlen", array_flip($commandsList)));
			
			foreach ($helpInfo as $help) {
				$this->NoticeUser($user, $help);
			}
			foreach ($commandsList as $command => $description) {
				$cmdFriendly = $command . str_repeat(" ", $longestLength - strlen($command));
				$this->NoticeUser($user, "     " . $cmdFriendly . "  " . $description);
			}
		}
		else {
			// specific help for commands
		}
	}
	
	public function RespondUnknownCmd($user, $args) {
		$this->NoticeUser($user, "Unknown command " . $args[0] . ". \"/msg ChanServ HELP\" for help.");
	}
	
	public function AsUser() {
		return $this->fakeUser;
	}
	
}
?>