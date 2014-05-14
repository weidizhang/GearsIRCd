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
	private $fakeUser;
	
	public $Database;
	
	public $unidentifiedUsers = array();
	public $ghostQueue = array();
	
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
				
			case "set":
				$this->RespondSet($user, $msgArgs);
				break;
				
			case "drop":
				$this->RespondDrop($user, $msgArgs);
				break;
				
			case "ghost":
				$this->RespondGhost($user, $msgArgs);
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
			$helpInfo = array();
			
			$cmdHelp = strtolower($args[1]);
			switch ($cmdHelp) {
				case "register":
					$helpInfo = array(
						"Syntax: REGISTER password email",
						" ",
						"Registers your nickname in the NickServ database. It",
						"gives you access to the SET commands, and prevents",
						"other users from using your nickname. Make sure you",
						"choose a password that you will remember. (Note: case",
						"matters! TEST, Test, test are all different passwords)"
					);
					break;
				
				case "identify":
					$helpInfo = array(
						"Syntax: IDENTIFY password",
						" ",
						"Tells NickServ you are really the owner of this",
						"nickname. Many commands require you to authenticate",
						"yourself with this command before you use them."
					);
					break;
					
				case "set":
					if (isset($args[2])) {
						$setOption = strtolower($args[2]);
						switch ($setOption) {
							case "password":
								$helpInfo = array(
									"Syntax: SET PASSWORD new-password",
									" ",
									"Changes the password used to identify you as the nick's",
									"owner."
								);
								break;
								
							case "email":
								$helpInfo = array(
									"Syntax: SET EMAIL address",
									" ",
									"Changes the email associated with your nickname."
								);
								break;
								
							default:	
								$helpInfo = array(
									"No help available for set option " . $args[2]
								);
								break;
						}
					}
					else {
						$helpInfo = array(
							"Syntax: SET option parameters",
							" ",
							"Set various nickname options. option can be one of:",
							"SET PASSWORD Set your nickname password",
							"SET EMAIL Set your nickname email",
							"Type /msg NickServ HELP set option for more information",
							"on a specific option."
						);
					}
					break;
					
				case "drop":
					$helpInfo = array(
						"Syntax: DROP [nickname]",
						" ",
						"Drops the given nick from the database. Once dropped,",
						"any other user may gain control of this nickname."
					);
					break;
					
				case "ghost":
					$helpInfo = array(
						"Syntax: GHOST nickname password",
						" ",
						"The user that is currently holding the nickname will",
						"be killed."
					);
					break;
					
				case "logout":
					$helpInfo = array(
						"Syntax: LOGOUT",
						" ",
						"Reverse the effect of the IDENTIFY command."
					);
					break;
					
				default:
					$helpInfo = array(
						"No help available for " . $args[1]
					);
					break;
			}
			
			foreach ($helpInfo as $help) {
				$this->NoticeUser($user, $help);
			}
		}
	}
	
	public function RespondRegister($user, $args) {
		if (isset($args[2])) {
			$password = $args[1];
			$email = $args[2];
			
			if ($this->IsRegistered($user)) {
				$this->NoticeUser($user, "Nickname " . $user->Nick() . " is already registered!");
			}
			elseif ((strlen($password) < 5) || (strpos($password, "\t") !== false) || (strtolower($password) === strtolower($user->Nick()))) {
				$this->NoticeUser($user, "Please try again with a more obscure password. Passwords should be at least five characters long, should not be something easily guessed (e.g. your nick), and cannot contain the space or tab characters.");
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
	
	public function RespondSet($user, $args) {
		if (isset($args[2])) {
			if ($this->IsRegistered($user)) {
				if ($user->isLoggedIn) {
					$command = strtolower($args[1]);
					switch ($command) {
						case "password":
							$password = $args[2];
							if ((strlen($password) < 5) || (strpos($password, "\t") !== false) || (strtolower($password) === strtolower($user->Nick()))) {
								$this->NoticeUser($user, "Please try again with a more obscure password. Passwords should be at least five characters long, should not be something easily guessed (e.g. your nick), and cannot contain the space or tab characters.");
							}
							else {
								$updateQuery = $this->Database->Query("UPDATE `Registered` SET `Password`=:pass WHERE `Nick`=:user;", array(
									":pass" => sha1($password),
									":user" => $user->Nick()
								));
								if ($updateQuery) {
									$this->NoticeUser($user, "Password changed.");
								}
								else {
									$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
								}
							}
							break;
						
						case "email":
							$email = $args[2];
							if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
								$this->NoticeUser($user, $email . " is not a valid e-mail address.");
							}
							else {
								$updateQuery = $this->Database->Query("UPDATE `Registered` SET `Email`=:email WHERE `Nick`=:user;", array(
									":email" => $email,
									":user" => $user->Nick()
								));
								if ($updateQuery) {
									$this->NoticeUser($user, "E-mail address changed to " . $email . ".");
								}
								else {
									$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
								}
							}
							break;
							
						default:
							$this->NoticeUser($user, "Unknown SET option " . $args[1] . ".");
							break;
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
		else {
			$this->NoticeUser($user, "Syntax: SET option parameters");
			$this->NoticeUser($user, "/msg NickServ HELP SET for more information.");
		}
	}
	
	public function RespondDrop($user, $args) {
		// To-do: Drop channels owned by user as well.
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
	
	public function RespondGhost($user, $args) {
		if (isset($args[2])) {
			$nick = $args[1];
			$password = $args[2];
			
			if (strtolower($nick) === strtolower($user->Nick())) {
				$this->NoticeUser($user, "You can't ghost yourself!");
			}
			else {
				$this->ghostQueue[] = array($user, $nick, $password);
			}
		}
		else {
			$this->NoticeUser($user, "Syntax: GHOST nickname password");
			$this->NoticeUser($user, "/msg NickServ HELP GHOST for more information.");
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