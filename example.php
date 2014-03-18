<?php
set_time_limit(0);
foreach (glob("./Gears/*.php") as $gearsClass) {
	require $gearsClass;
}
//do not modify anything about this line

$ircServer = new \GearsIRCd\Server(array(
	"Name" => "BasedIRC",
	"Address" => "irc.basedgod.gov",
	"Port" => 6667,
	"MOTD" => "Welcome to the GearsIRCd test server. \nEnjoy your stay!",
	"MaxUsers" => 25,
	"MaxPacketLen" => 512, //leave alone if you don't know what this is for
	"HostPrefix" => "irc",
	"MaxChans" => 20 //max channels a user can join
));

$ircServer->startServer();
while (true) {
	$ircServer->listenOnce();
}
?>