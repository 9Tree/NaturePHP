<?php 
//required for php 5.3 +
date_default_timezone_set('Europe/London');

//sending a specific file to routed init will lookup a match route to the local file/path
include(dirname(__FILE__).'/../../nphp/core/init.php');

//using Aura class will facilitate features such as auto-routing and configuration
Aura::init(array("route"=>true, "config"=>Path::to("application/config.xml", __FILE__)));

?>