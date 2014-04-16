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
	// To do list: Finish commands, on nick change hook (incl. on server connect), kill after 60s unidentified.
	public function __construct($sh, $servAddr) {
		$this->SocketHandler = $sh;
		$this->Database = new \GearsIRCd\Database("./Database/NickServ.db");
		$this->Database->Query("CREATE TABLE IF NOT EXISTS Registered (Nick TEXT, Password TEXT, Email TEXT, IPAddress TEXT, TimeCreated INTEGER);");
		
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
				
			case "register":
				$this->RespondRegister($user, $msgArgs);
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
	
	public function RespondRegister($user, $args) {
		if (isset($args[2])) {
			$password = $args[1];
			$email = $args[2];
			
			if (strlen($password) < 5) {
				$this->NoticeUser($user, "Please try again with a more obscure password. It must be at least five characters in length.");
			}
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->NoticeUser($user, $email . " is not a valid e-mail address.");
			}
			else {
				$checkExisting = $this->Database->QueryAndFetch("SELECT * FROM `Registered` WHERE `Nick`=:user;", array(":user" => $user->Nick()));
				if (count($checkExisting) > 0) {
					$this->NoticeUser($user, "Nickname " . $user->Nick() . " is already registered!");
				}
				else {
					$registerQuery = $this->Database->Query("INSERT INTO `Registered` VALUES(:user, :pass, :email, :ip, :time);", array(
						":user" => $user->Nick(),
						":pass" => sha1($password),
						":email" => $email,
						":ip" => $user->ipAddr,
						":time" => time()
					));
					
					if ($registerQuery) {
						$user->isRegistered = true;
						$user->isLoggedIn = true;
						$this->NoticeUser($user, "Nickname " . $user->Nick() . " registered.");
					}
					else {
						$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
					}
				}
			}
		}
		else {
			$this->NoticeUser($user, "Syntax: REGISTER password email");
			$this->NoticeUser($user, "/msg NickServ HELP REGISTER for more information.");
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