<?php
#/*
#* 9Tree Encrypt Class
#* Encryption tools
#*/

class Encrypt extends Nphp_static{
	
	//bit unsecure, but just for quick use
	public static $salt = "-Nphp default salt:";
	public static $pepper = ":Nphp default pepper-";
	public static $key = null;
	
	public static $mcrypt_cipher = null;
	public static $mcrypt_mode = null;
	public static $hash_function = 'tiger128,3';
	
	public static function hash($string){
		return self::fireHooks('hash', hash(self::$hash_function, self::$salt.$string.self::$pepper), array($string));
	}
	public static function encode($string){
		$args=Utils::combine_args(func_get_args(), 1, array('key'=>null, 'mode'=>null));
		if($args['mode']==null) $args['mode']=function_exists('mcrypt_encrypt')?'mcrypt':'simple';
		switch($args['mode']){
			case 'mcrypt':
				return self::mcrypt_encode($string, $args['key']);
			case 'simple':
				return self::simple_encode($string, $args['key']);
			break;
		}
	}
	public static function decode($string, $key=null){
		$args=Utils::combine_args(func_get_args(), 1, array('key'=>null, 'mode'=>null));
		if($args['mode']==null) $args['mode']=function_exists('mcrypt_encrypt')?'mcrypt':'simple';
		switch($args['mode']){
			case 'mcrypt':
				return self::mcrypt_decode($string, $args['key']);
			case 'simple':
				return self::simple_decode($string, $args['key']);
			break;
		}
	}
	protected static function check_mcrypt_settings(){
		self::$mcrypt_cipher = self::$mcrypt_cipher?:MCRYPT_RIJNDAEL_256;
		self::$mcrypt_mode = self::$mcrypt_mode?:MCRYPT_MODE_ECB;
	}
	public static function mcrypt_encode($string, $key=null){
		self::check_mcrypt_settings();
		$key=self::check_key($key);
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(self::$mcrypt_cipher, self::$mcrypt_mode), MCRYPT_RAND);
		return self::fireHooks('mcrypt_encode', $this->b64encode(mcrypt_encrypt(self::$mcrypt_cipher, $key, $string, self::$mcrypt_mode, $iv)), array($string, $key));
	}
	public static function mcrypt_decode($string, $key=null){
		self::check_mcrypt_settings();
		$key=self::check_key($key);
		$iv_size = mcrypt_get_iv_size(self::$mcrypt_cipher, self::$mcrypt_mode);
		$iv = substr($string, 0, $iv_size);
		$string = substr($string, $iv_size);
		return self::fireHooks('mcrypt_decode', rtrim(mcrypt_decrypt(self::$mcrypt_cipher, $key, $this->b64decode($string), self::$mcrypt_mode, self::get_mcrypt_iv()), "\0"), array($string, $key));
	}
	protected static function check_key($key){
		if($key!=null) return $key;
		if(self::$key==null) self::$key=self::$salt.self::$pepper;
		return self::fireHooks('check_key', self::$key, array($key));
	}
	public static function simple_encode($string, $key=null) {
		$key=self::check_key($key);
		$result = '';
		for($i=0; $i<strlen($string); $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % strlen($key))-1, 1);
			$char = chr(ord($char)+ord($keychar));
			$result.=$char;
		}
		return self::fireHooks('simple_encode', self::b64encode($result), array($string, $key));
	}
	public static function simple_decode($string, $key=null) {
		$key=self::check_key($key);
		$result = '';
		$string = self::b64decode($string);
		for($i=0; $i<strlen($string); $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($key, ($i % strlen($key))-1, 1);
			$char = chr(ord($char)-ord($keychar));
			$result.=$char;
		}
		return self::fireHooks('simple_decode', $result, array($string, $key));
	}
	public static function b64encode($string) {
		$data = base64_encode($string);
		$data = str_replace(array('+','/','='),array('-','_',''),$data);
		return self::fireHooks('b64encode', $data, array($string));
	}
	public static function b64decode($string) {
		$data = str_replace(array('-','_'),array('+','/'),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		return self::fireHooks('b64decode', base64_decode($data), array($string));
	}
}
?>