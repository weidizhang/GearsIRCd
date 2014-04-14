<?php
/**
 * @package GearsIRCd
 * @author Weidi Zhang <weidiz999@yahoo.com>
 * @copyright 2014 Weidi Zhang
 * @license http://creativecommons.org/licenses/by-nc-sa/4.0/legalcode
 */
 
namespace GearsIRCd;

class Services
{
	public $OperServ;
	public $NickServ;
	public $ChanServ;
	public $BotServ;
	
	public function __construct($sh, $servicesAddr) {
		$this->OperServ = new \GearsIRCd\OperServ($sh, $servicesAddr);
	}
}
?>