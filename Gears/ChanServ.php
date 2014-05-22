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
	
	public $Database;
	public $NickServ;
	
	public $modeQueue = array();
	
	public function __construct($sh, $servAddr) {
		$this->SocketHandler = $sh;
		$this->Database = new \GearsIRCd\Database("./Database/ChanServ.db");
		$this->Database->Query("CREATE TABLE IF NOT EXISTS Channels (Channel TEXT COLLATE NOCASE, Founder TEXT COLLATE NOCASE, Password TEXT, AccessList TEXT, TimeCreated INTEGER);");
		
		$this->fakeUser = new \GearsIRCd\User(false, -1, "127.0.0.1", "localhost");
		$this->fakeUser->Operator(true);
		$this->fakeUser->Nick("ChanServ", array(), array(), true);
		$this->fakeUser->Ident("services");
		$this->fakeUser->Hostmask($servAddr);
		$this->fakeUser->Realname("IRCd Service");
	}
	
	public function HandleCommand($user, $line, $msg, $allChannels) {
		$msgArgs = explode(" ", $msg);
		$cmdRecv = strtolower($msgArgs[0]);
		
		switch($cmdRecv) {
			case "help":
				$this->RespondHelp($user, $msgArgs);
				break;
				
			case "register":
				$this->RespondRegister($user, $msgArgs, $allChannels);
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
	
	public function RespondRegister($user, $args, $allChannels) {
		if (isset($args[2])) {
			$channel = $args[1];
			$password = $args[2];
			
			if ($this->NickServ->IsRegistered($user)) {
				if ($user->isLoggedIn !== true) {
					$this->NoticeUser($user, "Password authentication required for that command.");
					$this->NoticeUser($user, "Retry after typing /msg NickServ IDENTIFY password.");
				}
				elseif (substr($channel, 0, 1) != "#") {
					$this->NoticeUser($user, "Please use the symbol of # when attempting to register.");
				}
				elseif ((strlen($password) < 5) || (strpos($password, "\t") !== false) || (strtolower($password) === strtolower($user->Nick()))) {
					$this->NoticeUser($user, "Please try again with a more obscure password. Passwords should be at least five characters long, should not be something easily guessed (e.g. your nick), and cannot contain the space or tab characters.");
				}
				elseif ($this->IsRegistered($channel)) {
					$this->NoticeUser($user, "Channel " . $channel . " is already registered!");
				}
				else {
					$chanObj = $this->StringToChannelObject($channel, $allChannels);
					if ($chanObj !== false) {
						if ($chanObj->IsOpOrAbove($user)) {
							$accessList = array();
							$registerQuery = $this->Database->Query("INSERT INTO `Channels` VALUES (:chan, :founder, :pass, :acclist, :time);", array(
								":chan" => $channel,
								":founder" => $user->Nick(),
								":pass" => sha1($password),
								":acclist" => serialize($accessList),
								":time" => time()
							));
							
							if ($registerQuery) {
								$this->NoticeUser($user, "Channel " . $channel . " registered under your account: " . $user->Nick());
								
								$this->modeQueue[] = "MODE " . $channel . " +qo " . $user->Nick() . " " . $user->Nick();
							}
							else {
								$this->NoticeUser($user, "An error occurred. Please contact an IRC operator for help.");
							}
						}
						else {
							$this->NoticeUser($user, "You must be a channel operator to register the channel.");
						}
					}
					else {
						$this->NoticeUser($user, "Channel " . $channel . " doesn't exist.");
					}
				}
			}
			else {
				$this->NoticeUser($user, "You must register your nickname first. Type /msg NickServ HELP for information on registering nicknames.");
			}
		}
		else {
			$this->NoticeUser($user, "Syntax: REGISTER channel password");
			$this->NoticeUser($user, "/msg ChanServ HELP REGISTER for more information.");
		}
	}
	
	public function RespondUnknownCmd($user, $args) {
		$this->NoticeUser($user, "Unknown command " . $args[0] . ". \"/msg ChanServ HELP\" for help.");
	}
	
	public function IsRegistered($chan) {
		$checkExisting = $this->Database->QueryAndFetch("SELECT * FROM `Channels` WHERE `Channel`=:chan;", array(":chan" => $chan));
		return (count($checkExisting) > 0);
	}
	
	public function AsUser() {
		return $this->fakeUser;
	}
	
	private function StringToChannelObject($chan, $chanArr) {
		foreach ($chanArr as $chanObj) {
			if (strtolower($chanObj->Name()) === strtolower($chan)) {
				return $chanObj;
			}
		}
		return false;
	}
	
}
?>