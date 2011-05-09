<?php
#/*
#* 9Tree Mem Class
#* Useful variables holding class
#*/

class Mem extends Nphp_static{
	
	//private variables
	private static $keys=array(array());
	private static $values=array(array());
	private static $locks=array(array());
	
	//functionS
	//set
	static function set($var, $value, $bay){return self::setVar($var, $value, $bay);}
	static function setRef($var, &$value, $bay){return self::setVar($var, $value, $bay);}
	//get
	static function get($var, $bay, $required=false){$var=&self::getVar($var, $bay, $required);return $var;}
	static function &getRef($var, $bay, $required=false){return self::getVar($var, $bay, $required);}
	//lock
	static function lock($var, $bay){
		$index=array_search($var, self::$keys[$bay]);
		self::$locks[$bay][$index]=true;
	}
	static function unlock($var, $bay){
		$index=array_search($var, self::$keys[$bay]);
		self::$locks[$bay][$index]=false;
	}
	//check
	static function is_locked($var, $bay){
		$index=array_search($var, self::$keys[$bay]);
		if($index!==false){
			return self::$locks[$bay][$index];
		} else return false;
	}
	static function is_set($var, $bay){
		if(!$var) return isset(self::$keys[$bay]);
		if(!isset(self::$keys[$bay])) return false;
		if(array_search($var, self::$keys[$bay])===false) return false;
		return true;
	}
	
	
	//PRIVATE FUNCTIONS
	private static function &getVar(&$var, &$bay, &$required){
		if(self::is_set($var, $bay)){
			$index=array_search($var, self::$keys[$bay]);
			if(isset(self::$keys[$bay][$index])){
				return self::$values[$bay][$index];
			} elseif($required) {
				Log::kill("Mem :: required variable \"".$class."\" value not found in bay \"".$bay."\".");
			}
		} elseif($required){
			Log::kill("Mem :: required variable \"".$var."\" not existent in bay \"".$bay."\".");
		} else Log::add("Mem", "variable \"".$var."\" not existent in bay \"".$bay."\".");
		$var=false;
		return $var;
	}
	private static function setVar(&$var, &$value, &$bay){
		if(!isset(self::$keys[$bay])) self::$keys[$bay]=array();
		$index=array_search($var, self::$keys[$bay]);
		if($index!==false){
			if(self::$is_locked($var, $bay)){
				Log::add("Mem", "variable \"".$var."\" could not be set in bay \"".$bay."\". Variable is locked.");
				return false;
			}
		} else {
			$index=count(self::$keys[$bay]);
		}
		self::$keys[$bay][$index]=&$var;
		self::$values[$bay][$index]=&$value;
		self::$locks[$bay][$index]=false;
		return true;
	}
}
?>