<?php
#/*
#* 9Tree Routes Class
#* Routing funcionalities
#*/

class Routes extends Nphp_static{
	//check wether init() has run
	public static $current_simple = true;
	//uri variable holders
	public static $_PATH = array();
	//current page - populated after init()
	public static $current = NULL;
	//configuration holder
	protected static $simple_pages = array();
	protected static $complex_pages = array();
	protected static $dyn_field_count = array();
	protected static $dyn_fields = array();
	protected static $dyn_first_null = array();
	protected static $pages_modes = array();
	protected static $pages_types = array();
	protected static $pages_targets = array();
	
	public static function init(){
		
		// Set new base path
		$request_uri = Path::request_uri();
		Path::setBaseUri($request_uri);	//corrects Path::put and Path::url_to calls
		
		//get current location path
		list($location,) = explode("?", $request_uri);
		$location = Path::relative($location, Aura::$virtualpath.'.', true);
		// !! WORKAROUND !!
		//for domains like '9tree.net/', instead of 'localhost/~9tree/site'
		if($location == './/') $location = '';
		if(substr($location, 0, 2)=='./') $location = substr($location, 2, strlen($location));
		if(substr($location, 0, 1)=="/") $location = substr($location, 1, strlen($location)-1);
		if(substr($location, -1, 1)=="/") $location = substr($location, 0, strlen($location)-1);
		
		//check within configured paths
		//first check within static paths (much faster)
		foreach(self::$simple_pages as $page => $path){
			if($path == $location) {
				//found our path!!
				self::$current = $page;
				self::$current_simple = true;
				self::addInfo($location, $page);
				self::renderTarget($page);
				return;
			}
		}
		
		//ok, let's check the dynamics
		$loc_fields = explode("/", $location);
		$loc_num_fields = count($loc_fields);
		
		//gather possible matches
		$dyn_top_matches=array();
		$dyn_matches=array();
		foreach(self::$dyn_field_count as $page=>$number){
			if(self::$dyn_first_null[$page] && $loc_num_fields==$number-1){
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
			$path = self::$complex_pages[$page];
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
				self::$current_simple = false;
				self::$current = $page;
				self::$_PATH = $tmpPath;
				self::addInfo($location, $page);
				self::renderTarget($page);
				return;
			}
		}
		
		//nothing was found - set 404
		if(isset(self::$simple_pages["nphp-error-404"])||isset(self::$complex_pages["nphp-error-404"])){
			self::addInfo($location, "nphp-error-404");
			self::renderTarget("nphp-error-404");
		} else {
			Log::add($_SERVER['SCRIPT_NAME'].":$location -- Matched nothing and no 404 found.", "Routes Info");
			die("Nothing to see here, move along.");
		}
	}
	
	protected static function addInfo($location, $page){
		Log::add($_SERVER['SCRIPT_NAME'].":$location -- Matched $page:".(isset(self::$simple_pages[$page])?self::$simple_pages[$page]:self::$complex_pages[$page]), "Routes Info");
	}
	
	protected static function renderTarget($id){
		if(self::$pages_types[$id]=='static'){
			//include directly
			include(Aura::$filepath.self::$pages_targets[$id]);
		} else {
			//get class / method / arg1 / arg2...
			$args = explode('/', self::$pages_targets[$id]);
			$class = array_shift($args);
			$method = array_shift($args);
			//set args
			$method_args=array();
			$path_keys = array_keys(self::$_PATH);
			foreach($args as $arg){
				$i=intval(str_replace('$','',$arg))-1;
				if($i>-1){
					if(isset($path_keys[$i])){
						$method_args[]=self::$_PATH[$path_keys[$i]];
					} else trigger_error('<strong>Routes</strong> :: Argument index not found in path "'.$id.'":"'.$arg.'" - misconfiguration?', E_USER_WARNING);
					
				} else trigger_error('<strong>Routes</strong> :: Invalid argument "'.$id.'":"'.$arg.'"', E_USER_WARNING);
			}
			$Class = new $class();
			call_user_func_array ( array($Class, $method) , $method_args );
			return;
		}
	}
	
	public static function add($id, $path, $target, $type, $first_null=false){
		self::$pages_types[$id] = $type=='static' ? 'static' : 'dynamic';
		$is_simple = strpos($path,':')===false;
		self::$pages_modes[$id] = $is_simple ? 'simple' : 'complex';
		self::$pages_targets[$id] = $target;
		//strip extra / from path
		$page_path = $path;
		if(substr($page_path, -1, 1)=="/") $page_path = substr($page_path, 0, strlen($page_path)-1);
		if(substr($page_path, 0, 1)=="/") $page_path = substr($page_path, 1, strlen($page_path)-1);
		
		if($is_simple) self::$simple_pages[$id] = $page_path;
		else {
			self::$complex_pages[$id] = $page_path;
			
			$fields = explode("/", $page_path);
			self::$dyn_fields[$id] = array();
			foreach($fields as $field){
				if(substr($field, 0, 1)==":"){
					self::$dyn_fields[$id][] = substr($field, 1, strlen($field)-1);
				}
			}
			self::$dyn_field_count[$id] = count($fields);
			self::$dyn_first_null[$id] = $first_null;
		}
	}
	
	public static function url_to($page_name, $vars=array(), $qs=array(), $ignore_unrecognized=false){
		
		//check that page exists						
		if(!isset(self::$pages_modes[$page_name])) {
			trigger_error('<strong>Routes</strong> :: page definition not found "'.$page_name.'" - perhaps your missing route configuration?', E_USER_WARNING);
			return '';
		}
		
		//static pages
		
		if(self::$pages_modes[$page_name]=='simple'){
			$qs = $vars;
			$page = self::$simple_pages[$page_name];
			
			
		} elseif(self::$pages_modes[$page_name]=='complex'){
			
			$page = self::$complex_pages[$page_name];

			foreach($vars as $field=>$content){
				
				$bfield = ":".$field;
				$extra = '';
				//field is in middle
				if(strpos($page, $bfield.'/')!==false) {
					$bfield = $bfield.'/';	
					$extra = '/';
					//or field is at the end
				} elseif(!$ignore_unrecognized&&strpos($page, $bfield)+strlen($bfield)!=strlen($page)){
					trigger_error('<strong>Routes</strong> :: unrecognized field "'.$field.'" in page "'.$page_name.'"', E_USER_WARNING);
				}
				
				//clear null fields
				if(!$content){
					//check if field can be null
					if(self::$dyn_first_null[$page_name] && self::$dyn_fields[$page_name][0]==$field){
						$page = str_replace($bfield, '', $page);
					} else trigger_error('<strong>Routes</strong> :: field "'.$field.'" in page "'.$page_name.'" cannot be null.', E_USER_WARNING);
					
				} else {
					$page = str_replace($bfield, urlencode($content).$extra, $page);
				}
				
				$first_field=false;
			}
			
			if(strpos($page, ":")!==false){
				trigger_error('<strong>Routes</strong> :: missing fields in page "'.$page_name.'" call. ('.$page.')', E_USER_WARNING);
			}
			
		}
		
		$qstr=Utils::build_querystring($qs);
		return Aura::url_to($page.((!empty($qstr))?"?".$qstr:''));
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
		$new_get = Path::this_GET($args);
		if(self::$current_simple){
			return self::url_to(self::$current, $new_get, array(), true);
		} else {
			return self::url_to(self::$current, array_merge(self::$_PATH, $vars), $new_get, true);
		}
	}
}
?>