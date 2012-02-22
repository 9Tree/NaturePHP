<?php
/**
 * Typical init file within an application (example)
 */

//required for php 5.3 +
date_default_timezone_set('Europe/London');

// load NaturePhp Library
require(dirname(__FILE__)."/../../../nphp/core/init.php");	// this require will always work as long as relative path is correct
													// even if this file is an included file somewhere else which is included
													// somewhere else and so on...
														// equivalent to Path::to("/../../../nphp/init.php", __FILE__)

//start logging errors / notices
Log::init(true);
Log::debug(true);

//This variable serves just to toggle this example working with mysql
Mem::set('use_db', false, 'example');	//change to true to use the Database connection


//Setup database connection unless use_mysql is false
if(Mem::get('use_db', 'example')){
	
	Log::add('Using MySQL database connection.');
	//Setup database connection
	$DB = Database::open(array('database'=>'nphp_example', 'user'=>'trutas', 'password'=>'trutas'));
	
} else {
	
	Log::add('MySQL disabled - using default variables.');
	
}

?>