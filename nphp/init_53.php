<?php
#/*
#* NaturePHP initialization file
#* Desclares Nphp - Naturephp Core functionalities
#* Initializes NaturePHP Autoloader System
#*/

#/*
#* NaturePhp abstract classes
#*/
abstract class Nphp_hookable{
	//hooks
	protected static $hooks=array();
	final public static function addHook($method, $func){
		if(!isset(self::$hooks[$method])) self::$hooks[$method]=array();
		self::$hooks[$method][]=$func;
	}
	final protected static function fireHooks($method, $return=null, $params=array(), $instance=null){
		if(!isset(self::$hooks[$method])) return $return;
		foreach(self::$hooks[$method] as $func){
			$return=$func($return, $params, $instance);
		}
		return $return;
	}
}
abstract class Nphp_static extends Nphp_hookable{
	final private function  __construct(){}
	final private function  __clone(){}
	final private function __destruct(){}
}
abstract class Nphp_singleton extends Nphp_hookable{
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
	static protected $version='0.5.0';
	static public $routing=false;
	static public function lib_is_loaded($lib){
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
		return self::fireHooks('nphp_folder', $nphp_folder);
	}
	static function lib_path($lib){
		//folders system (for namespaces)
		$lib=str_replace("\\", "/", $lib);
		
		//builds path
		return self::fireHooks('lib_path', self::nphp_folder().'libs/'.$lib.'.php');
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