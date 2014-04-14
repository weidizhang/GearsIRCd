<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class Debug
{
	public static $debugEnabled = true;
	
	public static function printLn($str) {
		if (self::$debugEnabled === true) {
			echo "[" . date("m/d/Y H:i:s A") . "] " . $str . PHP_EOL;
		}
	}
}
?>