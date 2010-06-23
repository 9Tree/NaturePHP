<?php
#/*
#* 9Tree Routes Class - v0.2.2
#* Routing funcionalities
#*/

class Routes{
	//check wether init() has run
	private $initiated = false;
	//uri variable holders
	public static $_PATH = array();
	//current page - populated after init()
	public static $current = NULL;
	//configuration holder
	private $static_pages = array();
	private $dynamic_pages = array();
	private $pages_types = array();
	
	//general instance method
	private static function &getInstance(){
		static $instance;
		if(!isset($instance)){
			$c=__CLASS__;
			$instance=new $c;
		}
		return $instance;
	}
	
	public static function init(){
		// Set new base path
		$request_uri = Path::request_uri();
		Path::setBaseUri($request_uri);
		
		//get current location path
		list($location,) = explode("?", $request_uri);
		$location = Path::relative($location, $_SERVER['SCRIPT_NAME']);
		if(substr($location, -1, 1)=="/") $location = substr($location, 0, strlen($location)-1);
		
		$me = &self::getInstance();
		//check within configured paths
		//first check within static paths
		foreach($me->static_pages as $page => $path){
			$myPath = $path;
			if(substr($myPath, -1, 1)=="/") $myPath = substr($myPath, 0, strlen($myPath)-1);
			
			if($myPath == $location) {
				//found our path!!
				self::$current = $page;
				return;
			}
		}
		
		//ok, let's check the dynamics
		$loc_fields = explode("/", $location);
		
		foreach($me->dynamic_pages as $page => $path){
			$myPath = $path;
			if(substr($myPath, -1, 1)=="/") $myPath = substr($myPath, 0, strlen($myPath)-1);

			$fields = explode("/", $myPath);
			
			$count = count($loc_fields);
			if(count($fields) != $count) continue; //not the same for sure
			
			$its_this=true;
			$tmpPath = array();
			
			for($i=0; $i<$count; $i++){
				if(substr($fields[$i], 0, 1)==":") {
					$tmpPath[substr($fields[$i], 1, strlen($fields[$i])-1)] = $loc_fields[$i];
					continue;
				}
				
				if($fields[$i]!=$loc_fields[$i]) {
					$its_this=false;
					break;
				}
			}
			
			if($its_this){
				//found a dynamic match!
				self::$current = $page;
				self::$_PATH = $tmpPath;
				return;
			}
			
		}
		
	}
	
	public static function simple($page_path, $page_name){
		$me = &self::getInstance();
		$me->pages_types[$page_name] = 'static';
		$me->static_pages[$page_name] = $page_path.(substr($page_path, -1)=="."||!$page_path?"":"/");
	}
	
	public static function dynamic($page_path, $page_name){
		$me = &self::getInstance();
		$me->pages_types[$page_name] = 'dynamic';
		$me->dynamic_pages[$page_name] = $page_path.(substr($page_path, -1)=="."||!$page_path?"":"/");
	}
	
	public static function url_to($page_name, $vars=array(), $qs=array()){
		$me = &self::getInstance();
		
		//check that page exists						
		if(!isset($me->pages_types[$page_name])) {
			trigger_error('<strong>Routes</strong> :: page definition not found "'.$page_name.'" - perhaps your missing route configuration?', E_USER_WARNING);
			return '';
		}
		
		//static pages
		
		if($me->pages_types[$page_name]=='static'){
			$qs = $vars;
			$page = $me->static_pages[$page_name];
			
			
		} elseif($me->pages_types[$page_name]=='dynamic'){
			
			$page = $me->dynamic_pages[$page_name];
			foreach($vars as $field=>$content){
				//ignore static fields
				$new_page = str_replace(":".$field, urlencode($content), $page);
				if($page==$new_page) {
					trigger_error('<strong>Routes</strong> :: unrecognized field "'.$field.'" in page "'.$page_name.'"', E_USER_WARNING);
				} else $page=$new_page;
			}
			
			if(strpos(":", $page)!==false){
				trigger_error('<strong>Routes</strong> :: missing fields in page "'.$page_name.'" call. ('.$page.')', E_USER_WARNING);
			}
			
		}
		
		$qstr=Utils::build_querystring($qs);
		return Path::url_to($page.((!empty($qstr))?"?".$qstr:''), $_SERVER['SCRIPT_NAME']);
	}
	
	public static function put($page_name, $vars=array(), $qs=array()){
		print self::url_to($page_name, $vars, $qs);
	}
}
?>