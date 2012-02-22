<?php
#/*
#* 9Tree Log Class
#* Error control/logging funcionalities
#*/
class Log extends Nphp_singleton{
	
	//private variables
	protected static $debug=false;
	protected static $warning=false;
	protected static $notification_email=null;
	protected static $killed=false;
	protected static $events=array();
	protected static $log_info=false;
	protected static $initiated=false;
	
	//init control system
	public static function init($log=true){
		self::handle_errors();
		self::getInstance();
		static::$log_info=$log;
		static::$initiated=true;
	}
	
	//set debug mode on/off
	public static function debug($debug=true){
		if(!static::$initiated) self::init(true);
		if($debug && !static::$log_info) static::$log_info=true;
		self::$debug=$debug;
	}
	
	//handle errors
	public static function handle_errors(){
		//visible errors
		ini_set("display_errors", "1");
		ini_set("display_startup_errors", "1");
		error_reporting(E_ALL);
		//error handling
		$error_callback="Log::errorHandler";
		$exception_callback="Log::exceptionHandler";
		
		//php
		set_error_handler($error_callback, E_ALL + E_STRICT);

		set_exception_handler($exception_callback);
	}
	
	//get error type from code
	public static function get_error_type($code){
		if($code==1) return 'E_ERROR';
		if($code==2) return 'E_WARNING';
		if($code==4) return 'E_PARSE';
		if($code==8) return 'E_NOTICE';
		if($code==16) return 'E_CORE_ERROR';
		if($code==32) return 'E_CORE_WARNING';
		if($code==64) return 'E_COMPILE_ERROR';
		if($code==128) return 'E_COMPILE_WARNING';
		if($code==256) return 'E_USER_ERROR';
		if($code==512) return 'E_USER_WARNING';
		if($code==1024) return 'E_USER_NOTICE';
		if($code==2048) return 'E_STRICT';
		if($code==4096) return 'E_RECOVERABLE_ERROR';
		if($code==8192) return 'E_DEPRECATED';
		if($code==16384) return 'E_USER_DEPRECATED';
		if( (version_compare(PHP_VERSION, '6.0.0', '>=') && $code==32767) ||
			(version_compare(PHP_VERSION, '5.3.0', '>=') && $code==30719) ||
			(version_compare(PHP_VERSION, '5.2.0', '>=') && $code==6143) ||
			($code==2047)
				){
			return 'E_ALL';
		}
		return 'UNKNOWN';
	}
	
	//error handler
	public static function errorHandler($errno, $errmsg, $filename, $linenum, $vars){
		if(!static::$log_info) return;
		
		if( (
			$errno==E_WARNING ||
			$errno==E_PARSE ||
			$errno==E_NOTICE ||
			$errno==E_CORE_WARNING ||
			$errno==E_USER_WARNING ||
			$errno==E_USER_NOTICE ||
			$errno==E_STRICT
			) ||
			(
			version_compare(PHP_VERSION, '5.3.0')===1 &&
				(
				$errno==E_DEPRECATED ||
				$errno==E_USER_DEPRECATED
				)
			)
				){
					switch($errno){
						case E_NOTICE:
						case E_WARNING:
						case E_CORE_WARNING:
						case E_USER_WARNING:
							self::$warning=true;
							self::add('<span class="NPHP_warning">'.$errmsg."</span><br /><small><em>".self::get_error_type($errno)." in <strong>".$filename."</strong> line <strong>".$linenum."</strong></em></small>", "");
						break;
						case E_STRICT:
						case E_PARSE:
							self::add('<span class="NPHP_info">'.$errmsg."</span><br /><small><em>".self::get_error_type($errno)." in <strong>".$filename."</strong> line <strong>".$linenum."</strong></em></small>", "");
						break;
						default:
						    self::add('<span class="NPHP_default">'.$errmsg."</span><br /><small><em>".self::get_error_type($errno)." in <strong>".$filename."</strong> line <strong>".$linenum."</strong></em></small>", "");
						break;
					}
		} else self::kill('<span class="NPHP_warning">'.$errmsg."</span><br /><small><em>".self::get_error_type($errno)." in <strong>".$filename."</strong> line <strong>".$linenum."</strong></em></small>");
	}
	
	//error handler
	public static function exceptionHandler($exception){
		self::kill($exception);
	}
	
	//check for critical errors
	public static function has_warnings(){
		return self::$warning;
	}
	
	//pretty die()
	public static function kill($FATAL_ERROR){		
		self::addInfo();
		
		$json_mode=Nphp::call('Info', 'is_json_requested', array(), false);
		$xml_mode=Nphp::call('Info', 'is_xml_requested', array(), false);
		$ajax_mode=Nphp::call('Info', 'request_is_ajax', array(), false);
		
		//http status 500 headers
		if(!headers_sent()){
			$protocol = $_SERVER["SERVER_PROTOCOL"];
			if ( ('HTTP/1.1' != $protocol) && ('HTTP/1.0' != $protocol) )
				$protocol = 'HTTP/1.0';
			$status_header = "$protocol 500 Internal Server Error";

			@header( $status_header, true, 500 );
		}
	

		if(self::$debug){ 		//debug mode
			
			//ouput
			if($json_mode){
				include(dirname(__FILE__).'/../includes/Log-tpls/json-error-debug.php');
			} elseif($xml_mode){		
				include(dirname(__FILE__).'/../includes/Log-tpls/xml-error-debug.php');
			} elseif($ajax_mode) {
				include(dirname(__FILE__).'/../includes/Log-tpls/xml-error-debug.php');
			} else {
				include(dirname(__FILE__).'/../includes/Log-tpls/html-error-debug.php');
			}
			
		} else {	//non-debug mode
			
			//email notification process
			if(self::$notification_email){
				
				$domain=$_SERVER['HTTP_HOST'];
				$subject='['.$domain.'] Error Notification ('.gmdate("Y/m/d H:i:s").')';
				$message='<h3>Error Notification</h3><h4>On '.$domain.' at "'.$_SERVER['PHP_SELF'].'".</h4>';
				$message.= '<ol>'.self::list_events().'<li><span class="NPHP_fatal-error"><strong>Fatal Error</strong> :: '.$FATAL_ERROR.'</span></li></ol>';

				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

				mail(self::$notification_email, $subject, $message, $headers);
			}

			//non-debug output
			if($json_mode){
				include(dirname(__FILE__).'/../includes/Log-tpls/json-error.php');
			} elseif($xml_mode){
				include(dirname(__FILE__).'/../includes/Log-tpls/xml-error.php');
			} elseif($ajax_mode) {
				include(dirname(__FILE__).'/../includes/Log-tpls/xml-error.php');
			} else {
				include(dirname(__FILE__).'/../includes/Log-tpls/html-error.php');
			}
		}
		
		
		//die
		self::$killed=true;
		die;		
	}
	
	//Notices management
	public static function add($desc, $type='Info', $hidden=false){
		if(!static::$log_info) return;
		$count=count(self::$events);
		self::$events[$count]['type']=$type;
		self::$events[$count]['desc']=$desc;
		self::$events[$count]['hidden']=$hidden;
	}
	
	//Sets notification email
	public static function notify($email){
		if( Nphp::call('Check', 'email', array($email), false) ){
			self::$notification_email=$email;
		} else {
			//invalid email...
			self::add("Invalid notification email \"$email\".", "Log");
		}
	}
	
	public static function list_events(){
		$dump=array();
		$imax=count(self::$events);
		for ($i=0; $i<$imax; $i++){
			if(self::$events[$i]['type']!=""){
				$dump[$i]=
					( self::$events[$i]['hidden'] ? 
						"<span onclick=\"javascript:(function(){document.getElementById('nphp_ev_$i').style.display=='none'?document.getElementById('nphp_ev_$i').style.display='inline':document.getElementById('nphp_ev_$i').style.display='none'})()\" style=\"cursor:pointer;cursor:hand;\">":''
					).
					'<strong>'.(self::$events[$i]['type']).'</strong> :: '.self::$events[$i]['desc'].
					( (self::$events[$i]['hidden']) ? 
						'</span><span id="nphp_ev_'.$i.'" style="display:none;"><br />'.
						(self::$events[$i]['hidden']).'</span>' : ''
					);
			} else {
				$dump[$i]=self::$events[$i]['desc'];
			}
		}
		return '<li>'.implode("</li>\n\n<li>", $dump).'</li>';
	}
	
	public static function addInfo(){
		self::add(count($_GET)." item(s)<br />", "\$_GET Data", Text::to_html(Utils::s_var_dump($_GET)));
		self::add(count($_POST)." item(s)<br />", "\$_POST Data", Text::to_html(Utils::s_var_dump($_POST)));
		if(isset($_SESSION)) self::add(count($_SESSION)." item(s)<br />", "\$_SESSION Data", Text::to_html(Utils::s_var_dump($_SESSION)));
		else self::add("No active session found.", "\$_SESSION Data");
		self::add(count($_COOKIE)." item(s)<br />", "\$_COOKIE Data", Text::to_html(Utils::s_var_dump($_COOKIE)));
	}
	
	public function __destruct() {
		if(self::$debug && !self::$killed){ 
			
			self::addInfo();
			
			$json_mode=Nphp::call('Info', 'is_json_requested', array(), false);
			$xml_mode=Nphp::call('Info', 'is_xml_requested', array(), false);
			$ajax_mode=Nphp::call('Info', 'request_is_ajax', array(), false);
			
			if($json_mode){
				//debug in json's comments
				include(dirname(__FILE__).'/../includes/Log-tpls/json-debug.php');
			} elseif($xml_mode){
				//debug in xml's comments
				include(dirname(__FILE__).'/../includes/Log-tpls/xml-debug.php');
			} elseif($ajax_mode){
				//no debug for now - raises many issues
			} else {
				//debug in html
				include(dirname(__FILE__).'/../includes/Log-tpls/html-debug.php');
			}
		}
	}
}

?>