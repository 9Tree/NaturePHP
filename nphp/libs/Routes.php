<?php
#/*
#* 9Tree Routes Class - v0.2.2
#* Routing funcionalities
#*/

class Routes{
	//check wether init() has run
	private $initiated = false;
	public static $current_static = true;
	public static $is404 = false;
	//uri variable holders
	public static $_PATH = array();
	//current page - populated after init()
	public static $current = NULL;
	//configuration holder
	private $static_pages = array();
	private $dynamic_pages = array();
	private $dyn_field_count = array();
	private $dyn_first_null = array();
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
		//first check within static paths (much faster)
		foreach($me->static_pages as $page => $path){
			if($path == $location) {
				//found our path!!
				self::$current = $page;
				self::$current_static = true;
				return;
			}
		}
		
		//ok, let's check the dynamics
		$loc_fields = explode("/", $location);
		$loc_num_fields = count($loc_fields);
		
		//gather possible matches
		$dyn_top_matches=array();
		$dyn_matches=array();
		foreach($me->dyn_field_count as $page=>$number){
			if($me->dyn_first_null[$page] && $loc_num_fields==$number-1){
				$dyn_top_matches[$page]['number'] = $number;
				$dyn_top_matches[$page]['top'] = true;
			} elseif($loc_num_fields==$number){
				$dyn_matches[$page]['number'] = $number;
				$dyn_matches[$page]['top'] = false;
			}
		}
		
		$dyn_matches = array_merge($dyn_top_matches, $dyn_matches);
		
		foreach($dyn_matches as $page => $match){
			
			//field count
			$path = $me->dynamic_pages[$page];
			$fields = explode("/", $path);
			$num_fields = $match['number'];
			
			if($num_fields != count($fields)) 
				trigger_error('<strong>Routes</strong> :: there appears to be a strange field counting bug in the Routes class for page "'.$page_name.'"', E_USER_ERROR);
			
			$its_this=true;
			$tmpPath = array();
			$var_total = substr_count($path, ":");
			
			for($i=$num_fields-1; $i>=0; $i--){
				//assign loc fields index
				if($match['top']) {
					//skip last one
					$i2 = $i-1;
				} else $i2 = $i;
				//if field is dynamic, set it
				if(substr($fields[$i], 0, 1)==":") {
					//dyn field
					if($var_total==1 && $match['top'])
						$tmpPath[substr($fields[$i], 1, strlen($fields[$i])-1)] = null;
					else
						$tmpPath[substr($fields[$i], 1, strlen($fields[$i])-1)] = $loc_fields[$i2];
					$var_total--;
					continue;
				}
				//
				if($match['top'] && $i==0) continue;
				//if field is not dynamic, compare it
				if($fields[$i]!=$loc_fields[$i2]) {
					$its_this=false;
					break;
				}
			}
			
			if($its_this){
				//found a dynamic match!
				self::$current_static = false;
				self::$current = $page;
				self::$_PATH = $tmpPath;
				return;
			}
			
		}
		//nothing was found - set 404 true
		self::$is404 = true;
	}
	
	public static function simple($page_path, $page_name){
		$me = &self::getInstance();
		$me->pages_types[$page_name] = 'static';
		if(substr($page_path, -1, 1)=="/") $page_path = substr($page_path, 0, strlen($page_path)-1);
		$me->static_pages[$page_name] = $page_path;
	}
	
	public static function dynamic($page_path, $page_name, $first_null=false){
		$me = &self::getInstance();
		$me->pages_types[$page_name] = 'dynamic';
		if(substr($page_path, -1, 1)=="/") $page_path = substr($page_path, 0, strlen($page_path)-1);
		$me->dynamic_pages[$page_name] = $page_path;
		$me->dyn_field_count[$page_name] = substr_count($page_path, "/")+1;
		$me->dyn_first_null[$page_name] = $first_null;
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
			$first_field=true;
			foreach($vars as $field=>$content){
				
				$bfield = ":".$field;
				$extra = '';
				//field is in middle
				if(strpos($page, $bfield.'/')!==false) {
					$bfield = $bfield.'/';	
					$extra = '/';
					//or field is at the end
				} elseif(strpos($page, $bfield)+strlen($bfield)!=strlen($page)){
					trigger_error('<strong>Routes</strong> :: unrecognized field "'.$field.'" in page "'.$page_name.'"', E_USER_WARNING);
				}
				
				//clear null fields
				if(!$content){
					//check if field can be null
					if($first_field && $me->dyn_first_null[$page_name]){
						$page = str_replace($bfield, '', $page);
					} else trigger_error('<strong>Routes</strong> :: field "'.$field.'" in page "'.$page_name.'" cannot be null.', E_USER_WARNING);
					
				} else {
					$page = str_replace($bfield, urlencode($content).$extra, $page);
				}
				
				$first_field=false;
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
	
	public static function this_url($vars=array()){
		$args=Utils::combine_args(func_get_args(), 1, array(
				'get_in'=>array(),
				'get_out'=>array(),
				'#'=>''
				));
		$new_get = Path::this_qs($args);
		if(self::$current_static){
			return self::url_to(self::$current, $new_get);
		} else {
			return self::url_to(self::$current, array_merge(self::$_PATH, $vars), $new_get);
		}
	}
}
?>