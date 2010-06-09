<?php
#/*
#* NaturePHP initialization file - v0.4.1
#* The only required include file - starts lib autoloader system
#* Use require('nphp/init.php'); to start using NaturePHP
# 
#* Desclares Nphp - Naturephp Core functionalities
#* Initializes NaturePHP Autoloader System
#*/

//check required PHP_VERSION
if(!version_compare(PHP_VERSION, '5.0.0', '>=')){
	trigger_error('NaturePhp requires at least PHP 5', E_USER_ERROR);
	die('NaturePhp requires at least php 5');
}


#/*
#* NaturePhp Nphp Class - v0.4.1
#* Default NaturePhp environment class
#*/

//Nphp core functionalities
class Nphp{
	static $version='0.4.1';
	static function lib_is_loaded($lib){
		if(class_exists($lib)) return true;
		return false;
	}
	static function lib_exists($lib, $complete_path=false){
		if(!$complete_path){
			if(self::lib_is_loaded($lib)) return true;
			$path=self::lib_path($lib);
		} else $path=$lib;
		
		return file_exists($path);
	}
	static function nphp_folder(){
		//base nphp folder
		static $nphp_folder;
		if(!isset($nphp_folder)){
			$nphp_folder=dirname(__FILE__).'/';
		}
		return $nphp_folder;
	}
	static function lib_path($lib){
		//folders system (for namespaces) - only available when running on PHP 5.3+
		$lib=str_replace("\\", "/", $lib);
		
		//builds path
		return self::nphp_folder().'libs/'.$lib.'.php';
	}
	static function load_lib($lib, $complete_path=false){
		//get path
		if(!$complete_path) $path=self::lib_path($lib);
			else $path=$lib;
		//try loading library
		if (!self::lib_exists($path, true)){
			//if possible use Log::kill()
			if(self::lib_exists('Log')) {	
				if(!self::lib_is_loaded('Log')) require_once(self::lib_path('Log'));
				Log::kill('File "'.$path.'" not found for class "'.$lib.'".');
			} else die('File "'.$path.'" not found for class "'.$lib.'".');
		} else require_once($path);
	}
}

//autoloader system
function __autoload($class) {
	Nphp::load_lib($class);
}
?>