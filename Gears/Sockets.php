<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class Sockets
{
	private $serverName;
	
	public function __construct($addr) {
		$this->serverName = $addr;
	}
	
	public function sendRaw($socket, $raw) {
		$raw .= "\n";
		socket_write($socket, $raw);
		
		\GearsIRCd\Debug::printLn("Sent - " . trim($raw));
	}
	
	public function sendData($socket, $raw) {
		$buildStr = ":" . $this->serverName . " " . $raw;
		$this->sendRaw($socket, $buildStr);
	}
	
	public function sendCommand($userObj, $cmd) {
		$buildStr = ":" . \GearsIRCd\Utilities::UserToFullHostmask($userObj) . " " . $cmd;
		$this->sendRaw($userObj->Socket(), $buildStr);
	}
	
	public function readData($socket, $maxLen) {
		$sockRead = socket_read($socket, $maxLen);
		if (trim($sockRead) == null || !$sockRead) {
			return "";
		}
		$sockRead = str_replace("\r\n", "\n", $sockRead);
		$sockRead = str_replace("\r", "\n", $sockRead);
		$sockRead = preg_replace("/\n{2,}/", "\n", $sockRead);
		
		\GearsIRCd\Debug::printLn("Received - " . trim($sockRead));
		return $sockRead;
	}
	
	public function getSocketIP($socket) {
		$usrIP = "0.0.0.0";
		socket_getpeername($socket, $usrIP);
		return $usrIP;
	}

}
?>