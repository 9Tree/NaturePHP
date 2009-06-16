<?php
// Application configuration / startup Nphp
require('includes/init.php');

//mandates the use of ajax
if( !Check::request_is_ajax() ) Log::kill("This page should only be requested via ajax.");

//mandates the use of json
if( !Check::is_json_requested() ) Log::kill("This page should only be called as a json request.");
?>
(function(){
	alert('This is a typical json request');
	new Element('li', {})
})()