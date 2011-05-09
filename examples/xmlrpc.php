<?php
// Application configuration / startup Nphp
require('includes/init.php');

//layout variables (it's just the way i usually do it...)
$PAGE['id']				= 'xmlrpc';
$PAGE['title'] 			= 'NaturePhp Example - XmlRpc';
$PAGE['active_menu'] 	= 'xmlrpc';

//application header
include('includes/header.php');

//post
$host = "test.xmlrpc.wordtracker.com";
$path = "/";

$params = array("guest");
$rpc = new Xmlrpc($host, $path);
//$rpc->setCredentials($username, $password);
$rpc->setDebug(true);
var_dump($rpc->call('ping', $params));

?>