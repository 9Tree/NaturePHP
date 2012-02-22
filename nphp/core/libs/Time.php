<?php
#/*
#* 9Tree Time Class
#* Time & Date funcionalities
#*/

class Time extends Nphp_static{
	//timers start microtimes
	protected static $stopwatch_timers=array();
	
	//mysql formated time
	static function mysql_time($offset = 0) {
		if ( $offset==0 ) $d = date('Y-m-d H:i:s');
		else $d = date('Y-m-d H:i:s', (time() + $offset));
		return $d;
	}
	
	static function utc_timestamp($gmt_offset = 0){
		if ( $gmt_offset==0 ) $d = gmdate('Y-m-d\TH:i:s\Z');
		else $d = gmdate('Y-m-d\TH:i:s\Z', (time() + $gmt_offset));
		return $d;
	}
	
	static function ymd_date($offset = 0){
		if ( $offset==0 ) $d = date('Ymd');
		else $d = date('Ymd', (time() + $offset));
		return $d;
	}
	
	//microtimer functions - useful for performance checks (start, read, stop ideas from Drupal)
	static function stopwatch_start($name) {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		self::$stopwatch_timers[$name] = $mtime[1] + $mtime[0];
		return self::$stopwatch_timers[$name];
	}
	
	static function stopwatch_read($name) {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$mtime = $mtime[1] + $mtime[0];
		return self::$stopwatch_timers[$name]-$mtime;
	}
	
	static function stopwatch_stop($name) {
		$ret = self::stopwatch_read($name);
		
		$me=&self::getInstance();
		unset(self::$stopwatch_timers[$name]);
		
		return $ret;
	}
}
?>