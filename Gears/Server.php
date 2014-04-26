<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class Server extends Commands
{
	protected $name;
	protected $addr;
	protected $port = 6667;
	protected $motd;
	protected $maxUsers = 75;
	protected $packetLen = 512;
	protected $prefix = "irc";
	protected $maxChans = 25;	
	protected $ircdVer = "GearsIRCd-Alpha";	
	protected $guestPrefix = "Guest";
	
	private $servSocket;
	private $uniqCount = 0;
	
	protected $allUsers = array();
	protected $allChannels = array();
	protected $configOpers = array();
	protected $reservedNicks;
	protected $SocketHandler;
	
	protected $Services;
	
	public function __construct($servSettings) {
		$this->name = $servSettings["Name"];
		$this->addr = $servSettings["Address"];
		$this->port = $servSettings["Port"];
		$this->motd = $servSettings["MOTD"];
		$this->maxUsers = $servSettings["MaxUsers"];
		$this->packetLen = $servSettings["MaxPacketLen"];
		$this->prefix = $servSettings["HostPrefix"];
		$this->maxChans = $servSettings["MaxChans"];
		$this->guestPrefix = $servSettings["GuestPrefix"];
		
		$this->SocketHandler = new \GearsIRCd\Sockets($this->addr);
		$this->reservedNicks = array("nickserv", "chanserv", "operserv");
		
		$this->Services = new \GearsIRCd\Services($this->SocketHandler, $servSettings["ServicesAddress"]);
	}
	
	public function startServer() {
		$this->servSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_bind($this->servSocket, "0.0.0.0", $this->port);
		socket_listen($this->servSocket);
		socket_set_nonblock($this->servSocket);
		
		if (!file_exists("createdstamp-ircd")) {
			$cHandle = fopen("createdstamp-ircd", "w");
			$cData = time();
			fwrite($cHandle, $cData);
			fclose($cHandle);
		}
		
		\GearsIRCd\Debug::printLn("Server started");
	}
	
	public function addOperator($operSettings) {
		$this->configOpers[] = $operSettings;
	}
	
	public function listenOnce() {
		$this->acceptConnections();
		$this->readUserData();
		$this->runServices();
	}
	
	private function acceptConnections() {
		$incomingUsr = @socket_accept($this->servSocket);
		if ($incomingUsr) {
			socket_set_nonblock($incomingUsr);
			$this->uniqCount++;
			
			$this->SocketHandler->sendData($incomingUsr, "NOTICE AUTH :*** Looking up your hostname...");
			$usrIP = $this->SocketHandler->getSocketIP($incomingUsr);			
			$usrHostname = @gethostbyaddr($usrIP);
			if (!$usrHostname) {
				$usrHostname = $usrIP;
			}
			$usrHostmask = \GearsIRCd\Utilities::CreateHostmask($usrHostname, $this->prefix, $usrIP);
			$this->SocketHandler->sendData($incomingUsr, "NOTICE AUTH :*** Found your hostname");
			
			\GearsIRCd\Debug::printLn("Incoming user with IP " . $usrIP . ", Hostmask: " . $usrHostmask);
			
			$newUsrObj = New \GearsIRCd\User($incomingUsr, $this->uniqCount, $usrIP, $usrHostname);
			$newUsrObj->Hostmask($usrHostmask);
			$this->allUsers[] = $newUsrObj;
		}
	}
	
	private function readUserData() {
		foreach ($this->allUsers as $UsrIndex => $User) {
			while (@$readRaw = trim($this->SocketHandler->readData($User->Socket(), $this->packetLen))) {
				$readLines = explode("\n", $readRaw);
				
				foreach ($readLines as $readLine) {
					$this->HandleCommand($User, $UsrIndex, $readLine);
				}
			}
		}
	}
	
	private function runServices() {
		foreach ($this->Services->NickServ->unidentifiedUsers as $userIndex => $uUser) {
			if ((time() - $uUser[1]) >= 60) {
				$newNick = $this->guestPrefix . rand(100, 99999);
				$this->Services->NickServ->NoticeUser($uUser[0], "Your nickname is now being changed to " . $newNick);
				$this->RespondNick($uUser[0], null, array("NICK", ":" . $newNick));
				unset($this->Services->NickServ->unidentifiedUsers[$userIndex]);
				break;
			}
		}		

		foreach ($this->Services->NickServ->ghostQueue as $userIndex => $gUser) {
			$foundUser = false;
			
			foreach ($this->allUsers as $servUser) {
				if (strtolower($servUser->Nick()) === strtolower($gUser[1])) {
					$foundUser = true;
					
					if ($this->Services->NickServ->IsRegistered($gUser[1], true)) {
						$checkQuery = $this->Services->NickServ->Database->QueryAndFetch("SELECT * FROM `Registered` WHERE `Nick`=:user AND `Password`=:pass;", array(
							":user" => $gUser[1],
							":pass" => sha1($gUser[2])
						));
						
						if (count($checkQuery) > 0) {
							$this->RespondKill($this->Services->NickServ->AsUser(), "KILL " . $servUser->Nick() . " :GHOST command used by " . $gUser[0]->Nick(), array(true, $servUser->Nick(), true));
							$this->Services->NickServ->NoticeUser($gUser[0], "Ghost with your nick has been killed.");
						}
						else {
							$this->Services->NickServ->NoticeUser($gUser[0], "Access denied.");
						}
					}
					else {
						$this->Services->NickServ->NoticeUser($gUser[0], "Nick " . $gUser[1] . " isn't registered.");
					}					
					
					break;
				}
			}
			
			if (!$foundUser) {
				$this->Services->NickServ->NoticeUser($gUser[0], "Nick " . $gUser[1] . " isn't currently in use.");
			}			
			unset($this->Services->NickServ->ghostQueue[$userIndex]);
		}
	}
}
?>