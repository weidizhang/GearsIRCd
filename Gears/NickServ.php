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
	
	public $unidentifiedUsers = array();
	
	// To do list: Finish commands, finish on nick change hook (incl. on server connect), change nick to GuestXXXXX after 60s unidentified.
	public function __construct($sh, $servAddr) {
		$this->SocketHandler = $sh;
		$this->Database = new \GearsIRCd\Database("./Database/NickServ.db");
		$this->Database->Query("CREATE TABLE IF NOT EXISTS Registered (Nick TEXT COLLATE NOCASE, Password TEXT, Email TEXT, IPAddress TEXT, TimeCreated INTEGER);");
		
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
				
			case "identify":
				$this->RespondIdentify($user, $msgArgs);
				break;
				
			case "drop":
				$this->RespondDrop($user, $msgArgs);
				break;
				
			case "logout":
				$this->RespondLogout($user, $msgArgs);
				break;
				
			default:
				$this->RespondUnknownCmd($user, $msgArgs);
				break;
		}
	}
	
	public function NoticeUser($user, $msg) {
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
			
			if ($this->IsRegistered($user)) {
				$this->NoticeUser($user, "Nickname " . $user->Nick() . " is already registered!");
			}
			elseif (strlen($password) < 5) {
				$this->NoticeUser($user, "Please try again with a more obscure password. It must be at least five characters in length.");
			}
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$this->NoticeUser($user, $email . " is not a valid e-mail address.");
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
					$user->isLoggedIn = true;
					$this->NoticeUser($user, "Nickname " . $user->Nick() . " registered.");
				}
				else {
					$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
				}
			}
		}
		else {
			$this->NoticeUser($user, "Syntax: REGISTER password email");
			$this->NoticeUser($user, "/msg NickServ HELP REGISTER for more information.");
		}
	}
	
	public function RespondIdentify($user, $args) {
		if (isset($args[1])) {
			$password = sha1($args[1]);
			
			if ($this->IsRegistered($user)) {
				if ($user->isLoggedIn) {
					$this->NoticeUser($user, "You are already identified.");
				}
				else {
					$loginQuery = $this->Database->QueryAndFetch("SELECT * FROM `Registered` WHERE `Nick`=:user AND `Password`=:pass;", array(
						":user" => $user->Nick(),
						":pass" => $password
					));
					if (count($loginQuery) > 0) {
						$user->isLoggedIn = true;
						$this->NoticeUser($user, "Password accepted - you are now recognized.");
						
						foreach ($this->unidentifiedUsers as $userIndex => $uUser) {
							if ($uUser[0] === $user) {
								unset($this->unidentifiedUsers[$userIndex]);
								break;
							}
						}
					}
					else {
						$this->NoticeUser($user, "Password incorrect.");
					}
				}
			}
			else {
				$this->NoticeUser($user, "Your nick isn't registered.");
			}
		}
		else {
			$this->NoticeUser($user, "Syntax: IDENTIFY password");
			$this->NoticeUser($user, "/msg NickServ HELP IDENTIFY for more information.");
		}
	}
	
	public function RespondDrop($user, $args) {
		if (isset($args[1]) && !empty($args[1]) && (strtolower($args[1]) != strtolower($user->Nick()))) {
			$target = $args[1];
			if ($user->Operator()) {
				if ($this->IsRegistered($target, true)) {
					$dropQuery = $this->Database->Query("DELETE FROM `Registered` WHERE `Nick`=:nick;", array(":nick" => $target));
					if ($dropQuery) {
						$this->NoticeUser($user, "Nickname " . $target . " has been dropped.");
					}
					else {
						$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
					}
				}
				else {
					$this->NoticeUser($user, "Nick " . $target . " isn't registered.");
				}
			}
			else {
				$this->NoticeUser($user, "Access denied.");
			}
		}
		else {
			if ($this->IsRegistered($user)) {
				if ($user->isLoggedIn) {
					$dropQuery = $this->Database->Query("DELETE FROM `Registered` WHERE `Nick`=:nick;", array(":nick" => $user->Nick()));
					if ($dropQuery) {
						$this->NoticeUser($user, "Your nickname has been dropped.");
					}
					else {
						$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
					}
				}
				else {
					$this->NoticeUser($user, "Password authentication required for that command.");
					$this->NoticeUser($user, "Retry after typing /msg NickServ IDENTIFY password.");
				}
			}
			else {
				$this->NoticeUser($user, "Your nick isn't registered.");
			}
		}
	}
	
	public function RespondLogout($user, $args) {
		if (!isset($args[1])) {
			if ($this->IsRegistered($user)) {
				if ($user->isLoggedIn) {
					$user->isLoggedIn = false;
					$this->NoticeUser($user, "Your nick has been logged out.");
				}
				else {
					$this->NoticeUser($user, "Password authentication required for that command.");
					$this->NoticeUser($user, "Retry after typing /msg NickServ IDENTIFY password.");
				}
			}
			else {
				$this->NoticeUser($user, "Your nick isn't registered.");
			}
		}
		else {
			$this->NoticeUser($user, "Syntax: LOGOUT");
			$this->NoticeUser($user, "/msg NickServ HELP LOGOUT for more information.");
		}
	}
	
	public function RespondUnknownCmd($user, $args) {
		$this->NoticeUser($user, "Unknown command " . $args[0] . ". \"/msg NickServ HELP\" for help.");
	}
	
	public function IsRegistered($user, $nickIsInput = false) {
		$nick = null;
		if ($nickIsInput) {
			$nick = $user;
		}
		else {
			$nick = $user->Nick();
		}
		$checkExisting = $this->Database->QueryAndFetch("SELECT * FROM `Registered` WHERE `Nick`=:user;", array(":user" => $nick));
		return (count($checkExisting) > 0);
	}
	
	public function AsUser() {
		return $this->fakeUser;
	}
	
}
?>