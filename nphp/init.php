<?php
#/*
#* NaturePHP initialization file
#* Verifies compatibility with php version and starts lib autoloader system
#* Use require('nphp/init.php'); to start using NaturePHP
#*/

//check required PHP_VERSION
if(!version_compare(PHP_VERSION, '5.3.0', '>=')){
	trigger_error('NaturePhp 0.5 requires at least PHP 5.3', E_USER_ERROR);
	die('NaturePhp 0.5 requires at least php 5.3');
} else include(dirname(__FILE__).'/init_53.php');
?>