<?php

//required for php 5.3 +
date_default_timezone_set('Europe/London');

//load Nphp Library
include(dirname(__FILE__).'/../../nphp/core/init.php');

//disabling routing makes this page independent (no route matches will be looked up)
//everything in the framework environment is still the same, variables, paths, routing configurations, etc
Aura::init(array("route"=>false, "config"=>Path::to("application/config.xml", __FILE__)));

?>
<a href="<?php Routes::put('home', array('lang'=>null)); ?>">Home</a> | <a href="<?php Routes::put('view-post', array('lang'=>null, 'alias'=>1)); ?>">View post 1</a> | <a href="<?php Routes::put('contacts', array('lang'=>null)); ?>">Contacts</a>