<?php
#/*
#* NaturePHP initialization file
#* Desclares Nphp - Naturephp Core functionalities
#* Initializes NaturePHP Autoloader System
#*/

#/*
#* NaturePhp abstract classes
#*/
abstract class Nphp_basic{
	//basic abstract class that everything uses as base
	//no features for now, but could have quite a few in the future
}
abstract class Nphp_static extends Nphp_basic{
	final private function  __construct(){}
	final private function  __clone(){}
	final private function __destruct(){}
}
abstract class Nphp_singleton extends Nphp_basic{
	final private function  __construct(){}
	final private function  __clone(){}
	protected static $_instance = NULL;
	//general instance method
	final public static function getInstance(){
		if(null !== static::$_instance){
			return static::$_instance;
		}
		static::$_instance = new static();
		return static::$_instance;
	}
}

#/*
#* NaturePhp Nphp Class
#* Default NaturePhp environment class
#*/

//Nphp core functionalities
class Nphp extends Nphp_static{
	static protected $version='0.5.5';
	static private $extraFolders=array();
	static public function lib_is_loaded($lib){
		if(class_exists($lib, false)) return true;
		return false;
	}
	static function check_lib($lib, $complete_path=false){
		if(!$complete_path){
			if(self::lib_is_loaded($lib)) return true;
			$path=self::match_lib_path($lib);
		} else $path=file_exists($lib)?$lib:false;
		
		return $path;
	}
	static function nphp_folder(){
		//base nphp folder
		static $nphp_folder;
		if(!isset($nphp_folder)){
			$nphp_folder=dirname(__FILE__).'/../';
		}
		return $nphp_folder;
	}
	static function add_folder($folder){
		static::$extraFolders[]=$folder;
	}
	static function lib_path($folder, $lib){
		//folders system (for namespaces)
		$lib=str_replace("\\", "/", $lib);
		
		//builds path
		return $folder.$lib.'.php';
	}
	static function match_lib_path($lib){
		//check for possible paths
		$path=self::lib_path(self::nphp_folder().'libs/', $lib);
		$verified=false;
		$i=0;
		do{
			if(file_exists($path)) break;
			if(isset(static::$extraFolders[$i])){
				$path=self::lib_path(static::$extraFolders[$i], $lib);
			} else return false;
			$i++;
		} while(!$verified);
		return $path;
	}
	static function load_lib($lib, $complete_path=false){
		
		//get path
		if(!$complete_path) {
			//all done
			if(self::lib_is_loaded($lib)) return;
			
			$path = self::match_lib_path($lib);
		} else {
			$path = self::check_lib($lib, true);
			$path = $path ? $path : false;
		}

		//try loading library
		if ($path===false){

			//if possible use Log::kill()
			if(self::check_lib('Log')) {	
				if(!self::lib_is_loaded('Log')) require_once(self::match_lib_path('Log'));
				Log::kill('No matching class found for "'.$lib.'".');
			} else die('No matching class found for "'.$lib.'".');
		} else require_once($path);
	}
	static function call($lib, $method, $args, $default=false){
		if(self::check_lib($lib)) return forward_static_call_array(array($lib, $method), $args);
		return $default;
	}
	static function get($lib, $property, $default=false){
		if(self::check_lib($lib)) return $lib::$$property;
		return $default;
	}
	static function set($lib, $property, $value){
		if(self::check_lib($lib)) {
			$lib::$$property=$value;
			return true;
		}
		return false;
	}
}

//autoloader system
spl_autoload_register("Nphp::load_lib");

?>