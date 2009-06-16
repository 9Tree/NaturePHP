<?php
#/*
#* 9Tree Time Class - v0.2.2
#* Time & Date funcionalities
#*/

class Time{
	//timers start microtimes
	public $stopwatch_timers=array();
	
	//general instance method
	private static function &getInstance(){
		static $instance;
		if(!isset($instance)){
			$c=__CLASS__;
			$instance=new $c;
		}
		return $instance;
	}
	
	//mysql formated time
	static function mysql_time($gmt_offset = 0) {
		if ( $gmt_offset==0 ) $d = gmdate('Y-m-d H:i:s');
		else $d = gmdate('Y-m-d H:i:s', (time() + ($gmt_offset * 3600)));
		return $d;
	}
	
	//microtimer functions - useful for performance checks (start, read, stop ideas from Drupal)
	static function stopwatch_start($name) {
		$me=&self::getInstance();
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$me->stopwatch_timers[$name] = $mtime[1] + $mtime[0];
		return $me->stopwatch_timers[$name];
	}
	
	static function stopwatch_read($name) {
		$me=&self::getInstance();
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$mtime = $mtime[1] + $mtime[0];
		return $me->stopwatch_timers[$name]-$mtime;
	}
	
	static function stopwatch_stop($name) {
		$ret = self::stopwatch_read($name);
		
		$me=&self::getInstance();
		unset($me->stopwatch_timers[$name]);
		
		return $ret;
	}
}
?>