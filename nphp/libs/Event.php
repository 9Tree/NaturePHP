<?php
#/*
#* 9Tree Event Class - v0.3.5
#* Event/action and filtering functionalities (based on Wordpress add_action and add_filter)
#*/

class Event
{
	
	//private variables
	private static $events=array(array());
	private static $filters=array(array());
	
	//add action
	static function add($event, $func, $priority=false){
		if($priority===false){
			self::$events[$event][] = $func;
		} else {
			Utils::array_insert(self::$events[$event], $func, $priority);
		}
	}
	
	//add filter
	static function add_filter($filter, $func, $priority=0){
		if($priority===false){
			self::$filters[$filter][] = $func;
		} else {
			Utils::array_insert(self::$filters[$filter], $func, $priority);
		}
	}
	
	//add action
	static function fire($event){
		if(!isset(self::$filters[$filter])){	//nothing to do
			trigger_error('<strong>Event</strong> :: No events to fire to "'.$event.'"', E_USER_NOTICE);
			return $str;
		}
		
		foreach(self::$events[$event] as $func){
			$func($event);
		}
	}
	
	//add filter
	static function filter($filter, $str){
		
		if(!isset(self::$filters[$filter])){	//nothing to do
			trigger_error('<strong>Event</strong> :: No filters to apply to "'.$filter.'"',E_USER_NOTICE);
			return $str;
		}
		
		//apply filters
		foreach(self::$filters[$filter] as $func){
			$str=$func($str, $filter);
		}
		return $str;
	}
}

?>