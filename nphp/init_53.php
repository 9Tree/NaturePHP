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
	static private $extraFolders=array();
	static public function lib_is_loaded($lib){
		if(class_exists($lib, false)) return true;
		return false;
	}
	static function lib_exists($lib, $complete_path=false){
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
			$nphp_folder=dirname(__FILE__).'/';
		}
		return self::fireHooks('nphp_folder', $nphp_folder);
	}
	static function add_folder($folder){
		static::$extraFolders[]=$folder;
	}
	static function lib_path($folder, $lib){
		//folders system (for namespaces)
		$lib=str_replace("\\", "/", $lib);
		
		//builds path
		return self::fireHooks('lib_path', $folder.$lib.'.php');
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
		} else $path = self::lib_exists($lib, true) ? $path : false;

		//try loading library
		if ($path===false){

			//if possible use Log::kill()
			if(self::lib_exists('Log')) {	
				if(!self::lib_is_loaded('Log')) require_once(self::match_lib_path('Log'));
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