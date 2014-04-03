<?php
namespace GearsIRCd\lib;

class Debug {
	public static $debugEnabled = true;
	
	public static function printLn($str) {
		if (self::$debugEnabled === true) {
			echo "[" . date("m/d/Y H:i:s A") . "] " . $str . PHP_EOL;
		}
	}
}
?>