<?php
#/*
#* 9Tree Session Class
#* PHP Session functionalities
#*/

class Session extends Nphp_static{
	
	//protected variables
	protected static $session_started = false;

	static function set($holder, $data){
		self::check_session();
		$_SESSION[$holder] = $data;
	}
	static function get($holder){
		if(self::has($holder)) return $_SESSION[$holder];
		return null;
	}
	static function has($holder){
		self::check_session();
		if(isset($_SESSION[$holder])) return true;
		return false;
	}
	public static function remove($holder){
		if(self::has($holder)) unset($_SESSION[$holder]);
	}
	public static function check_session(){
		if(!self::$session_started){
			if(headers_sent()) trigger_error('<strong>Session</strong> :: Session must be called at least once before any output is sent, you can use Session::check_session();', E_USER_WARNING);
			//load php session data
			if(!session_id()) session_start();
			self::$session_started = true;
		}
	}
	public static function clear(){
		self::check_session();
		session_destroy();
		unset($_SESSION);
		$_SESSION = array();
		self::$session_started = false;
	}
}
?>