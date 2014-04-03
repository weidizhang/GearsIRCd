<?php
namespace GearsIRCd\lib;

class User {
	private $nickName = null;
	private $ident = null;
	private $realName = null;
	private $socket;
	private $isOperator = false;
	
	public $failedOperAttempts = 0;
	
	private $ipAddr;
	private $hostName;
	private $hostMask;
	
	private $ping;
	private $pingTime;
	
	private $uniqueID;
	
	public function __construct($socket, $uniqueID, $ip, $hostname) {
		$this->Socket($socket);
		$this->UniqueID($uniqueID);
		$this->ipAddr = $ip;
		$this->hostName = $hostname;
	}
	
	public function Nick($newValue = "", $reserved = array(), $users = array()) {
		if (!empty($newValue)) {
			$newValue = trim($newValue);
			$isValid = Utilities::ValidateNick($newValue);
			
			foreach ($users as $user) {
				if (strtolower($user->Nick()) === strtolower($newValue)) {
					return 11;
				}
			}
			
			if (in_array(strtolower($newValue), array_map("strtolower", $reserved))) {
				return 12;
			}
			
			if ($isValid) {
				$this->nickName = $newValue;
				return true;
			}
			else {
				return 10;
			}
		}
		return $this->nickName;
	}
	
	public function Ident($newValue = "") {
		if (!empty($newValue)) {
			if (strlen($newValue > 10)) {
				$newValue = substr($newValue, 0, 10);
			}
			$this->ident = $newValue;
			return true;
		}
		return $this->ident;
	}
	
	public function Hostmask($newValue = "") {
		if (!empty($newValue)) {
			$this->hostMask = $newValue;
			return true;
		}
		return $this->hostMask;
	}
	
	public function Realname($newValue = "") {
		if (!empty($newValue)) {
			$this->realName = $newValue;
			return true;
		}
		return $this->realName;
	}
	
	public function Operator($newValue = null) {
		if ($newValue != null) {
			$this->isOperator = $newValue;
			return true;
		}
		return $this->isOperator;
	}
	
	public function Socket($sock = null) {
		if ($sock != null) {
			$this->socket = $sock;
			return true;
		}
		return $this->socket;
	}
	
	public function UniqueID($id) {
		$this->uniqueID = $id;
	}
}
?>