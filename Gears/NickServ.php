<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class NickServ
{
	private $SocketHandler;
	private $Database;
	private $fakeUser;
	
	public function __construct($sh, $servAddr) {
		$this->SocketHandler = $sh;
		$this->Database = new \GearsIRCd\Database("./Database/NickServ.db");
		$this->Database->Query("CREATE TABLE IF NOT EXISTS Registered (Nick TEXT, Password TEXT, TimeCreated INTEGER);");
		
		$this->fakeUser = new \GearsIRCd\User(false, -1, "127.0.0.1", "localhost");
		$this->fakeUser->Operator(true);
		$this->fakeUser->Nick("NickServ", array(), array(), true);
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
				"NickServ allows you to register your nickname,",
				"which can prevent other users from using your",
				"nickname. It also allows you to do other things,",
				"such as register channels. To use a command, type:",
				"/msg NickServ command.",
				" ",
				"For more information on a command, type:",
				"/msg NickServ HELP command.",
				" ",
				"The following commands are available:",
				" "
			);
			$commandsList = array(
				"REGISTER" => "Register a nickname",
				"IDENTIFY" => "Identify yourself with your password",
				"SET" => "Set various options, such as password",
				"DROP" => "Cancel the registration of a nickname",
				"GHOST" => "Disconnects a \"ghost\" IRC session using your nick",
				"LOGOUT" => "Reverses effect of the identify command"
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
		$this->NoticeUser($user, "Unknown command " . $args[0] . ". \"/msg NickServ HELP\" for help.");
	}
	
	public function AsUser() {
		return $this->fakeUser;
	}
	
}
?>