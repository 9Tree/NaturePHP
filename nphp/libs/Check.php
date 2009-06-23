<?php
#/*
#* 9Tree Enviornment Check Class - v0.3.5
#* Request headers funcionalities
#*/

class Check{
	
	//check if hostname is valid
	static function http_host($host) {		//from Drupal
	  return (bool)preg_match('/^\[?(?:[a-z0-9-:\]_]+\.?)+$/', $host);
	}
	
	//check if string is email
	static function email($mail) {	//based on Drupal
		$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
		$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
		$ipv4 = '(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}';
		$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';

		return (bool)preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
	}
	
	//check for valid username (most sites...)
	static function username($str){
		return (bool)preg_match('/^[a-z\d_]{4,28}$/i', $str);
	}
	
	//check for valid ipv4 addresses
	static function ip_address($str){
		return (bool)preg_match('^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$', $str);
	}
	
	//check for valid hexadecimal colors (#ffffff)
	static function hex_color($str){
		return (bool)preg_match('/^#(?:(?:[a-f\d]{3}){1,2})$/i', $str);
	}
	
	//check for valid phone number (6 to 13 digits, optional 3 international digits - allows (+351) 123456789, 351123456789, 123 45 67 89, etc)
	static function phone_number($str){
		return (bool)preg_match('/^\(?\+?[0-9]{3}\)?([0-9- ]){6,13}$/', $str);
	}
	
	//check if url is valid 
	static function url($url, $absolute = FALSE) {	//from Drupal
	  if ($absolute) {
	    return (bool)preg_match("
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
	    return (bool)preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
	  }
	}
	
	//Server Type Detection
	static function server_is_apache(){
		return (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') !== false) 
			|| (strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false);
	}
	static function server_is_iis(){return strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false;}
	
	//Server OS Detection
	static function server_is_unix(){return strpos($_SERVER['SERVER_SOFTWARE'], 'Unix') !== false;}
	static function server_is_windows(){return strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft') !== false;}
	
	//Client OS Detection
	static function client_is_mac(){return self::inBrowser('Macintosh', 'platform');}
	static function client_is_windows(){return self::inBrowser('Win', 'platform');}
	static function client_is_winxp(){return self::inBrowser('WinXP', 'platform') || self::inBrowser('Windows NT 5.');}
	static function client_is_linux(){return self::inBrowser('Linux', 'platform');}
	
	
	//Client Browser Detection
	static function browser_is_lynx(){return self::inBrowser('Lynx', 'browser');}
	static function browser_is_gecko(){return self::inBrowser('Gecko') && !self::browser_is_safari();}
	static function browser_is_ie(){return self::inBrowser('MSIE', 'browser');}
	static function browser_is_opera(){return self::inBrowser('Opera', 'browser');}
	static function browser_is_ns4(){return self::inBrowser('Navigator', 'browser');}
	static function browser_is_firefox(){return self::inBrowser('Firefox', 'browser');}
	static function browser_is_webkit(){return self::inBrowser('Webkit');}
	static function browser_is_safari(){return self::inBrowser('Safari', 'browser');}
	static function browser_is_chrome(){return self::inBrowser('Chrome', 'browser');}
	static function browser_supports_gzip(){	// Determine if the browser accepts gzipped data. Based on Drupal
	    return (@strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE && function_exists('gzencode'));
	} 
	
	
	//Request type detection
	static function request_is_ajax(){
		return self::request_inHeader('X-Requested-With', 'XMLHttpRequest');
	}
	static function request_is_ssl() {
		if ( isset($_SERVER['HTTPS']) ) {
			if ( 'on' == strtolower($_SERVER['HTTPS']) )
				return true;
			if ( '1' == $_SERVER['HTTPS'] )
				return true;
		} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}
	
	//Requested format detection
	static function is_json_requested(){
		return self::request_accepts('application/json') || 
			self::request_inHeader('Content-Type', 'application/jsonrequest') || 
			self::request_inHeader('X-Request', 'JSON');
	}
	static function is_javascript_requested(){
		return self::request_accepts('text/javascript');	//do not use, most browsers don't actually support it yet...
	}
	static function is_xhtml_requested(){
		return self::request_accepts('text/xhtml') || self::request_accepts('application/xhtml');
	}
	static function is_html_requested(){
		return self::request_accepts('text/html');
	}
	static function is_xml_requested(){
		return self::request_accepts('text/xml') || self::request_accepts('application/xml');
	}
	
	
	//generic functions
	static function request_accepts($str){
		return self::request_inHeader('Accept', $str);
	}
	static function request_inHeader($meta, $str){
		$headers=Headers::get_all();
		if(isset($headers[$meta]) && strpos($str, $headers[$meta]) !== false)
			return true;
		return false;
	}
	static function inBrowser($needle, $key='HTTP_USER_AGENT'){
		static $browser;
		static $browscap;
		if($browser===null) {		
			if(ini_get("browscap")){
				$browser=get_browser(null, true);
				$browscap=true;
			} else {
				$browser=array();
				$browscap=false;
			} 
			$browser['HTTP_USER_AGENT']=$_SERVER['HTTP_USER_AGENT'];
		}
		if(!$browscap) $key='HTTP_USER_AGENT';
		return stripos($browser[$key], $needle) !== false;
	}
}
?>