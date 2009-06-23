<?php
#/*
#* 9Tree Headers Class - v0.3.5
#* File/application headers funcionalities
#*/

class Headers{
	
	//redirect function
	static function redirect($link, $from=false){

		if($from && !preg_match("#(ht|f)tps?://#", $link)) {
			$link=Path::url_to($link, $from);
		}
			
		//print "redirect to ".$link;die;
		header ("Location: ".$link);
		exit(0);
	}
	
	//no cache headers
	static function nocache() {
		@ header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
		@ header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		@ header('Cache-Control: no-cache, must-revalidate, max-age=0');
		@ header('Pragma: no-cache');
	}
	
	//http status (403, 500, 404, etc)
	static function http_status( $header ) {
		$text = self::get_http_status_desc( $header );

		if ( empty( $text ) )
			return false;

		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( ('HTTP/1.1' != $protocol) && ('HTTP/1.0' != $protocol) )
			$protocol = 'HTTP/1.0';
		$status_header = "$protocol $header $text";

		return @header( $status_header, true, $header );
	}
	
	//json headers
	static function json(){
		$args=Utils::combine_args(func_get_args(), 0, array("mode" => "application", "cache"=>0));
		//set content type
		if($args['mode']=='text'){
			header('Content-Type: text/json; charset=utf-8');
		} else {
			header('Content-type: application/x-json; charset=utf-8');
		}
		//set cache / nocache
		if($args['cache']){
			if(is_int($args['cache'])) self::cache($args['cache']);
			else self::cache();
		} else self::nocache();
		// Handle proxies
		header("Vary: Accept-Encoding");	
	}
	
	//cache headers
	static function cache($offset=864000){
		$expires=time() + $offset;
		@ header("Expires: " . gmdate("D, d M Y H:i:s", $expires) . " GMT");
		@ header('Cache-Control: must-revalidate, max-age='.$expires);
		@ header('Pragma: ');
	}
	
	//javascript headers
	static function javascript($cache=0) {
		//set content type
		header("Content-Type: text/javascript; charset=utf-8");
		//set cache / nocache
		if($cache){
			if(is_int($cache)) self::cache($cache);
			else self::cache();
		} else self::nocache();
		// Handle proxies
		header("Vary: Accept-Encoding");
	}
	
	//gzip headers
	static function gzip(){
		header('Content-Encoding: gzip');
	}
	
	//get http status code description
	private function get_http_status_desc( $code ) {
		static $header_to_desc;

		$code = (int) $code;

		if ( !isset($header_to_desc) ) {
			$header_to_desc = array(
				100 => 'Continue',
				101 => 'Switching Protocols',

				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',

				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',

				400 => 'Bad Request',
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',

				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported'
			);
		}

		if ( isset( $header_to_desc[$code] ) ) {
			return $header_to_desc[$code];
		} else {
			return '';
		}
	}
	
	//better getallheaders for unsupported servers
	static function get_all(){
		static $headers;
		if($headers) return $headers;
		if (!function_exists('getallheaders')){
			foreach ($_SERVER as $name => $value){
				if (substr($name, 0, 5) == 'HTTP_'){
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
			
		} else $headers=getallheaders();	
		return $headers;
	}
}
?>