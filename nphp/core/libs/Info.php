<?php
#/*
#* 9Tree Enviornment Info Class
#* Request headers, client and server information
#*/

class Info extends Nphp_static{
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