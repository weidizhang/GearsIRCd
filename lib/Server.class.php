<?php
namespace GearsIRCd\lib;

class Server extends Commands {
	protected $name;
	protected $addr;
	protected $port = 6667;
	protected $motd;
	protected $maxUsers = 75;
	protected $packetLen = 512;
	protected $prefix = "irc";
	protected $maxChans = 25;	
	protected $ircdVer = "GearsIRCd Alpha";	
	
	private $servSocket;
	private $uniqCount = 0;
	
	protected $allUsers = array();
	protected $allChannels = array();
	protected $configOpers = array();
	protected $reservedNicks;
	protected $SocketHandler;			
	
	public function __construct(Array $servSettings, Array $opers) {
		$this->name = $servSettings["Name"];
		$this->addr = $servSettings["Address"];
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
		socket_bind($this->servSocket, "0.0.0.0", $this->port);
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
					$this->HandleCommand($User, $UsrIndex, $readLine);
				}
			}
		}
	}
}
?>