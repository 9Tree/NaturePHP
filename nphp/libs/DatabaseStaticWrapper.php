<?php

class DatabaseStaticWrapper{
	static protected $instance;
	static function setup(){
		$args=Utils::combine_args(func_get_args(), 0);
		static::$instance=Database::setup($args);
	}
	static function __callStatic($func, $args){
		return call_user_func_array(array(static::$instance, $func), $args);
	}
}

?>