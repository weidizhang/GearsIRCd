<?php
namespace GearsIRCd;

class Services
{
	public $OperServ;
	public $NickServ;
	public $ChanServ;
	public $BotServ;
	
	public function __construct($sh) {
		$this->OperServ = new \GearsIRCd\OperServ($sh);
	}
}
?>