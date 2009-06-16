<?php
/**
 * Typical init file within an application (example)
 */


// start NaturePhp
$relative_path_to_nphp = "../../nphp/init.php";
require(dirname(__FILE__)."/".$relative_path_to_nphp);	//this require will always work as long as relative path is correct
														//even if this file is an included file somewhere else
														//equivalent to Path::to($relative_path_to_nphp, __FILE__)

//start logging errors / notices
Log::init($debug=true);

//This variable serves just to toggle this example working with mysql
Mem::set('use_db', true, 'example');	//change to true to use the Database connection


//Setup database connection unless use_mysql is false
if(Mem::get('use_db', 'example')){
	
	Log::add('Using MySQL database connection.');
	//Setup database connection
	$DB = Database::open(array('database'=>'nphp_example', 'user'=>'trutas', 'password'=>'trutas'));
	
} else {
	
	Log::add('MySQL disabled - using default variables.');
	
}

?>