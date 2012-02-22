<?php
// Application configuration / startup Nphp
require('includes/init.php');

//mandates the use of ajax
if( !Info::request_is_ajax() ) Log::kill("This page should only be requested via ajax.");

//mandates the use of json
if( !Info::is_json_requested() ) Log::kill("This page should only be called as a json request.");
?>
(function(){
	alert('This is a typical json request');
	new Element('li', {})
})()