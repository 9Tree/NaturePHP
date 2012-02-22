<?php
/**
 * NaturePhp v0.3.5 example application
 * This is a simple example to show some of the possibilities of NaturePhp
 *
 * please remember NaturePhp is just an autoloaded php library system - you don't really 
 * have to use most of these examples/functionalities as they are library specific
 * 
 * This is just for the sake of exemplifying how the system works as well as some included libraries
 */


// Application configuration / startup Nphp
require('includes/init.php');

//layout variables (it's just the way i usually do it...)
$PAGE['id']				= 'session';
$PAGE['title'] 			= 'NaturePhp Example - Sessions';
$PAGE['active_menu'] 	= 'session';


//get current session values if existent
$test = Session::get('test');
$test2 = Session::get('test2');
$test3 = Cookies::get('myCookie', 'test');
$test4 = Cookies::get('myCookie', 'test2');

//set the action
if(isset($_GET['clear'])){
	//logout
	Session::clear();
	Cookies::clear_all();
	$test = null;
	$test2 = null;
	$test3 = null;
	$test4 = null;
} elseif(isset($_GET['remove'])){
	//remove some info
	Session::remove('test');
	Cookies::remove('myCookie', 'test');
} else {
	//set new session values
	Session::set('test', 1);
	Session::set('test2', 2);
	Cookies::set('myCookie', 'test', 3);
	Cookies::set('myCookie', 'test2', 4);
}


//application header
include('includes/header.php');

//this page content
Log::add('Index content starting.');


print 'php test:'.$test.' - php test2:'.$test2.' - cookie test:'.$test3.' - cookie test2:'.$test4;



?>
	<br />
	<br />
	<?php var_dump($_SESSION); ?>
	<br />
	<br />
	<?php var_dump($_COOKIE); ?>
	<br />
	hit refresh for session stored values
<?php

Log::add('Index content ended.');

//the footer
include('includes/footer.php');
?>