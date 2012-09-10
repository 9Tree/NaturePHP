<?php
#/*
#* 9Tree Cookies Class
#* Supports security, encryption and multiple data in cookie
#*/

class Cookies extends Nphp_static{
	
	//private variables
	protected static $initiated = false;
	protected static $cookies=array();
	protected static $cookie_prefix='nphp-';
	protected static $empty_opts = array(
			'ssl'=>false,
			'httponly'=>false,
			'expire'=>null,
			'valid'=>1296000,	//15 days
			'path'=>null,
			'domain'=>null,
			'encrypt'=>false
			);
	protected static $secure_opts = array(
			'ssl'=>true,	//to be checked
			'httponly'=>true,
			'expire'=>null,
			'valid'=>1296000,	//15 days
			'path'=>null,
			'domain'=>null,
			'encrypt'=>true
			);
	//setup cookie options
	public static function setup($cookie){
		self::check_init();
		$func_args=func_get_args();
		$args=Utils::combine_args($func_args, 1, self::$empty_opts);
		if(!isset(self::$cookies[$cookie]) || self::$cookies[$cookie]['options'] != $args)
			self::$cookies[$cookie]['options'] = $args;
	}
	//setup cookie options with advanced security defaults
	public static function setup_secure($cookie){
		$args=Utils::combine_args(func_get_args(), 1, self::$secure_opts);
		if(!Info::request_is_ssl()) $args['ssl']=false;
		self::setup($cookie, $args);
	}
	public static function set($cookie, $holder, $data){
		self::check_init();
		if(!isset(self::$cookies[$cookie])) self::setup_secure($cookie);	//security as a default
		self::$cookies[$cookie]['data'][$holder] = $data;
		self::make_cookie($cookie);
	}
	public static function get($cookie, $holder){
		self::check_init();
		if(!isset(self::$cookies[$cookie]) || !isset(self::$cookies[$cookie]['data'][$holder])) return;
		return self::$cookies[$cookie]['data'][$holder];
	}
	public static function remove($cookie, $holder){
		self::check_init();
		if(!isset(self::$cookies[$cookie]) || !isset(self::$cookies[$cookie]['data'][$holder])) return;
		unset(self::$cookies[$cookie]['data'][$holder]);
		self::make_cookie($cookie);
	}
	public static function clear($cookie){
		self::check_init();
		if(!isset(self::$cookies[$cookie])) return;
		self::$cookies[$cookie]['data']=array();
		self::make_cookie($cookie);
	}
	public static function clear_all(){
		self::check_init();
		$keys=array_keys(self::$cookies);
		foreach($keys as $cookie){
			self::$cookies[$cookie]['data']=array();
			self::make_cookie($cookie);
		}
	}
	static function check_init(){
		if(!self::$initiated){
			//load cookies
			$prefix_len=strlen(self::$cookie_prefix);
			foreach($_COOKIE as $cookie=>$val){
				if(strpos($cookie, self::$cookie_prefix)===0){
					//valid Session class cookie
					$content = Utils::unserialize($val);
					$cookie_name=substr($cookie, $prefix_len);
					self::$cookies[$cookie_name]['options'] = $content['options'];
					if($content['options']['encrypt']){
						$content['data'] = Encrypt::decode($content['data']);
					}
					self::$cookies[$cookie_name]['data'] = Utils::unserialize($content['data']);
				}
			}
			self::$initiated = true;
		}
	}
	static function make_cookie($cookie){
		// Serialize the userdata for the cookie
		$cookie_data = Utils::serialize(self::$cookies[$cookie]['data']);
		$opts = self::$cookies[$cookie]['options'];
		//ecrypt if necessary
		if($opts['encrypt']) $cookie_data = Encrypt::encode($cookie_data);
		$data = Utils::serialize(array('options'=>$opts, 'data'=>$cookie_data));
		
		//calculate expire
		if(count(self::$cookies[$cookie]['data'])==0){
			//delete cookie
			$expire = time() - 3600;
		} else {
			$expire = $opts['expire']?:time()+$opts['valid'];
		}
		// Set the cookie
		$check = setcookie(
					self::$cookie_prefix.$cookie,
					$data,
					$expire,
					$opts['path'],
					$opts['domain'],
					$opts['ssl'],
					true // Sets HttpOnly to avoid XSS attacks.
				);
		if(!$check)
			trigger_error('<strong>Cookies</strong> :: Unable to create cookie "'.$cookie.'"', E_USER_WARNING);
		return $check;
	}
}
?>