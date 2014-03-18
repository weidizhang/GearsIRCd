<?php
namespace GearsIRCd;

class Utilities
{
	public static function ValidateNick($nick) {
		return preg_match("/\A[a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]{1,17}\z/i", $nick);
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
			return $prefix . "-" . substr(strtoupper(md5(microtime(true))), 5, 8) . "-localhost";
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
}
?>