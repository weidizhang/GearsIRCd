<?php
$settings = array();
$settings["Name"] = "BasedIRC";
$settings["Address"] = "irc.basedgod.gov";
$settings["Port"] = 6667;
$settings["MOTD"] = "Welcome to the GearsIRCd test server. \nEnjoy your stay!";
$settings["MaxUsers"] = 25;
$settings["MaxPacketLen"] = 512; //leave alone if you don't know what this is for
$settings["HostPrefix"] = "irc";
$settings["MaxChans"] = 20; //max channels a user can join

$opers = array();
$opers[0]["Username"] = "BasedGod";
// this hash says "123456". PLAIN TEXT NOT SUPPORTED FOR SECURITY REASONS.
$opers[0]["Password"] = "0e3b78d8380844b0f697bb912da7f4d210382c6714194fd16039ef2acd924dcf";
// anything supported by php hash() method (most common: md5, sha1, sha256)
$opers[0]["PasswordHashMethod"] = "haval256,3";
?>