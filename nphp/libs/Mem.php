<?php
#/*
#* 9Tree Varbay Class - v0.3.5
#* Useful variables holding class
#*/

class Mem{
	
	//private variables
	private $keys=array(array());
	private $values=array(array());
	private $locks=array(array());
	
	//general instance method
	private static function &getInstance(){
		static $instance;
		if(!isset($instance)){
			$c=__CLASS__;
			$instance=new $c;
		}
		return $instance;
	}
	
	//functionS
	//set
	static function set($var, $value, $bay){return self::setVar($var, $value, $bay);}
	static function setRef($var, &$value, $bay){return self::setVar($var, $value, $bay);}
	//get
	static function get($var, $bay, $required=false){$var=&self::getVar($var, $bay, $required);return $var;}
	static function &getRef($var, $bay, $required=false){return self::getVar($var, $bay, $required);}
	//lock
	static function lock($var, $bay){
		$me=&self::getInstance();
		$index=array_search($var, $me->keys[$bay]);
		$me->locks[$bay][$index]=true;
	}
	static function unlock($var, $bay){
		$me=&self::getInstance();
		$index=array_search($var, $me->keys[$bay]);
		$me->locks[$bay][$index]=false;
	}
	//check
	static function is_locked($var, $bay){
		$me=&self::getInstance();
		$index=array_search($var, $me->keys[$bay]);
		if($index!==false){
			return $me->locks[$bay][$index];
		} else return false;
	}
	static function is_set($var, $bay){
		$me=&self::getInstance();
		if(!$var) return isset($me->keys[$bay]);
		if(!isset($me->keys[$bay])) return false;
		if(array_search($var, $me->keys[$bay])===false) return false;
		return true;
	}
	
	
	//PRIVATE FUNCTIONS
	private static function &getVar(&$var, &$bay, &$required){
		if(self::is_set($var, $bay)){
			$me=&self::getInstance();
			$index=array_search($var, $me->keys[$bay]);
			if(isset($me->keys[$bay][$index])){
				return $me->values[$bay][$index];
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
		$me=&self::getInstance();
		if(!isset($me->keys[$bay])) $me->keys[$bay]=array();
		$index=array_search($var, $me->keys[$bay]);
		if($index!==false){
			if($me->is_locked($var, $bay)){
				Log::add("Mem", "variable \"".$var."\" could not be set in bay \"".$bay."\". Variable is locked.");
				return false;
			}
		} else {
			$index=count($me->keys[$bay]);
		}
		$me->keys[$bay][$index]=&$var;
		$me->values[$bay][$index]=&$value;
		$me->locks[$bay][$index]=false;
		return true;
	}
}
?>