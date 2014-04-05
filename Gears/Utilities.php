<?php
namespace GearsIRCd;

class Utilities
{
	public static function ValidateNick($nick) {
		return preg_match("/\A[a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]{1,17}\z/i", $nick);
	}
	
	public static function ValidateChannel($chan) {
		return preg_match('/^#[a-zA-Z0-9`~!@#$%^&*\(\)\'";|}{\]\[.<>?]{0,20}$/', $chan);
	}
	
	public static function ValidateHostmask($mask) {
		return (!preg_match("/[^-A-Za-z0-9.]/", $mask));
	}
	
	public static function UserToFullHostmask($user, $opermode = false) {
		if ($opermode === false) {
			return $user->Nick() . "!" . $user->Ident() . "@" . $user->Hostmask();
		}
		return $user->Nick() . "!" . $user->Ident() . "@" . $user->hostName;
	}
	
	public static function MatchHostmask($orig, $toMatch) {
		$orig = strtolower($orig);
		$toMatch = strtolower($toMatch);
		$toMatch = str_replace('\\*', '.+', preg_quote($toMatch, '/'));
		return preg_match('/^' . $toMatch . '$/', $orig);
	}
	
	public static function CreateHostmask($hostname, $prefix, $ip) {
		$makeUsingIP = false;
		$hostArgs = explode(".", $hostname);
		
		if ($ip == "127.0.0.1") {
			return $prefix . "-" . substr(strtoupper(md5("localhost")), 5, 8);
		}
		
		if ($hostname != $ip) {
			if (isset($hostArgs[1])) {
				$makeUsingIP = false;
				$hashedIP = strtoupper(md5($hostArgs[0]));
				unset($hostArgs[0]);
				return $prefix . "-". $hashedIP . substr($hashedIP, 5, 8) . implode(".", $hostArgs);
			}
			else {
				$makeUsingIP = true;
			}
		}
		
		if ($makeUsingIP) {
			$newMask = "";
			unset($hostArgs[3]);
			foreach ($hostArgs as $arg) {
                $newMask .= substr(strtoupper(md5($arg)), 5, 8) . ".";
            }
			$newMask .= "IP";
			return $newMask;
		}
	}
	
	public static function RemoveFromArray($array, $obj) {
		$gKey = array_search($obj, $array);
		if ($gKey === false) {
			return $array;
		}
		return array_merge(array_slice($array, 0, $gKey), array_slice($array, $gKey + 1));
	}
}
?>