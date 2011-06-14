<?php
#/*
#* 9Tree Check Class
#* Check variables/data consistency
#*/

class Check extends Nphp_static{
	
	//check if hostname is valid
	static function http_host($host) {		//from Drupal
	  return self::fireHooks('http_host', (bool)preg_match('/^\[?(?:[a-z0-9-:\]_]+\.?)+$/', $host), array($host));
	}
	
	//check if string is email
	static function email($mail) {	//based on Drupal
		$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
		$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
		$ipv4 = '(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}';
		$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

		return self::fireHooks('email', (bool)preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail), array($mail));
	}
	
	//check for valid username (most sites...)
	static function username($str){
		return self::fireHooks('username', (bool)preg_match('/^[a-z\d_]{4,28}$/i', $str));
	}
	
	//check for valid ipv4 addresses
	static function ip_address($str){
		return self::fireHooks('ip_address', (bool)preg_match('^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$', $str), array($str));
	}
	
	//check for valid hexadecimal colors (#ffffff)
	static function hex_color($str){
		return self::fireHooks('hex_color', (bool)preg_match('/^#(?:(?:[a-f\d]{3}){1,2})$/i', $str), array($str));
	}
	
	//check for valid phone number (6 to 13 digits, optional 3 international digits - allows (+351) 123456789, 351123456789, 123 45 67 89, etc)
	static function phone_number($str){
		return self::fireHooks('phone_number', (bool)preg_match('/^\(?\+?[0-9]{3}\)?([0-9- ]){6,13}$/', $str), array($str));
	}
	
	//check if url is valid 
	static function url($url, $absolute = FALSE) {	//from Drupal
	  if ($absolute) {
	    $return=(bool)preg_match("
	      /^                                                      # Start at the beginning of the text
	      (?:ftp|https?):\/\/                                     # Look for ftp, http, or https schemes
	      (?:                                                     # Userinfo (optional) which is typically
	        (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
	        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
	      )?
	      (?:
	        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
	        |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
	      )
	      (?::[0-9]+)?                                            # Server port number (optional)
	      (?:[\/|\?]
	        (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
	      *)?
	    $/xi", $url);
	  }
	  else {
	    $return=(bool)preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
	  }
	
		return self::fireHooks('url', $return, array($str));
	}
}
?>