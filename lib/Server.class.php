<?php
namespace GearsIRCd\lib;

class Server {
	public $name;
	public $addr;
	public $ip = "0.0.0.0";
	public $port = 6667;
	public $motd;
	public $maxUsers = 75;
	public $packetLen = 512;
	public $prefix = "irc";
	public $maxChans = 25;	
	public $ircdVer = "GearsIRCd Alpha";	
	
	private $servSocket;
	private $uniqCount = 0;
	
	public $allUsers = array();
	public $allChannels = array();
	public $configOpers = array();
	public $reservedNicks;
	public $SocketHandler;			
	
	public function __construct(Array $servSettings, Array $opers) {
		$this->name = $servSettings["Name"];
		$this->addr = $servSettings["Address"];
		$this->ip = $servSettings["IP"];
		$this->port = $servSettings["Port"];
		$this->motd = $servSettings["MOTD"];
		$this->maxUsers = $servSettings["MaxUsers"];
		$this->packetLen = $servSettings["MaxPacketLen"];
		$this->prefix = $servSettings["HostPrefix"];
		$this->maxChans = $servSettings["MaxChans"];
		
		$this->SocketHandler = new Sockets($this->addr);
		$this->reservedNicks = array("nickserv", "chanserv", "botserv", "operserv");
		$this->addOperators($opers);
	}
	
	public function startServer() {
		$this->servSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_bind($this->servSocket, $this->ip, $this->port);
		socket_listen($this->servSocket, $this->maxUsers);
		socket_set_nonblock($this->servSocket);
		
		if (!file_exists("createdstamp-ircd")) {
			$cHandle = fopen("createdstamp-ircd", "w");
			$cData = time();
			fwrite($cHandle, $cData);
			fclose($cHandle);
		}
		
		Debug::printLn("Server started");
		
		while (true) {
			$this->listenOnce();
		}
	}
	
	public function addOperators($operSettings) {
		$this->configOpers = $operSettings;
	}
	
	public function listenOnce() {
		$this->acceptConnections();
		$this->readUserData();
	}
	
	private function acceptConnections() {
		$incomingUsr = @socket_accept($this->servSocket);
		if ($incomingUsr) {
			$this->uniqCount++;
			
			$this->SocketHandler->sendData($incomingUsr, "NOTICE AUTH :*** Looking up your hostname...");
			$usrIP = $this->SocketHandler->getSocketIP($incomingUsr);			
			$usrHostname = @gethostbyaddr($usrIP);
			if (!$usrHostname) {
				$usrHostname = $usrIP;
			}
			$usrHostmask = Utilities::CreateHostmask($usrHostname, $this->prefix, $usrIP);
			$this->SocketHandler->sendData($incomingUsr, "NOTICE AUTH :*** Found your hostname");
			
			Debug::printLn("Incoming user with IP " . $usrIP . ", Hostmask: " . $usrHostmask);
			
			$newUsrObj = New User($incomingUsr, $this->uniqCount, $usrIP, $usrHostname);
			$newUsrObj->Hostmask($usrHostmask);
			$this->allUsers[] = $newUsrObj;
		}
	}
	
	private function readUserData() {
		foreach ($this->allUsers as $UsrIndex => $User) {
			while ($readRaw = trim($this->SocketHandler->readData($User->Socket(), $this->packetLen))) {
				$readLines = explode("\n", $readRaw);
				
				foreach ($readLines as $readLine) {
					CommandHandler::handle($this, $User, $UsrIndex, $readLine);
				}
			}
		}
	}
}
?>