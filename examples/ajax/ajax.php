<?php
// Application configuration / startup Nphp
require('includes/init.php');

//mandates the use of ajax
if( !Check::request_is_ajax() ) Log::kill("This page should only be requested via ajax.");

?>
Hello World! :)