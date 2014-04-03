<?php
set_time_limit(0);
foreach (glob("./Gears/*.php") as $gearsClass) {
	require $gearsClass;
}
//do not modify anything about this line

$ircServer = new \GearsIRCd\Server(array(
	"Name" => "BasedIRC",
	"Address" => "irc.basedgod.gov",
	"IP" => "0.0.0.0",
	"Port" => 6667,
	"MOTD" => "Welcome to the GearsIRCd test server. \nEnjoy your stay!",
	"MaxUsers" => 25,
	"MaxPacketLen" => 512, //leave alone if you don't know what this is for
	"HostPrefix" => "irc",
	"MaxChans" => 20 //max channels a user can join
));

$ircServer->addOperator(array(
	"Username" => "BasedGod",
	"Password" => "0e3b78d8380844b0f697bb912da7f4d210382c6714194fd16039ef2acd924dcf", // this hash says "123456". PLAIN TEXT NOT SUPPORTED FOR SECURITY REASONS.
	"PasswordHashMethod" => "haval256,3" // anything supported by php hash() method (most common: md5, sha1, sha256)
));

$ircServer->startServer();
while (true) {
	$ircServer->listenOnce();
}
?>