<?php
class Aura extends Nphp_static{
	
	static $host="localhost";
	static $virtualpath="/";
	static $baseurl="http://localhost/";
	static $filepath="/";
	static $debug=true;
	static $id=null;
	static $databases=array();
	static $params=array();
	static $routes=array();
	
	static $environments=array();
	
	static function init(){
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
								'config' => null, 
								'route' => false));
		
		
		//load file
		libxml_use_internal_errors(true);
		$xml=simplexml_load_file($args['config']);
		
		//parse xml
		$response = array();
		$classesFolder=null;
		
		if(!$xml){
			trigger_error("<strong>Aura</strong> :: Failed Loading.", E_USER_WARNING);
			$errors = libxml_get_errors();
		    foreach($errors as $error) {
				trigger_error("<strong>Aura</strong> :: ".$error->message, E_USER_WARNING);
		    }
			trigger_error("<strong>Aura</strong> :: Unable to proceeed.", E_USER_ERROR);
		} else {
			//check faults
			if (count($xml->fault) > 0) {
	            //An error was returned
	            $fault = $this->parseValue($xml->fault->value);
	            trigger_error("<strong>Aura</strong> :: Fault ".$fault->faultCode.": ".$fault->faultString, E_USER_WARNING);
	        }
	
			//parse data
			//databases
			$g_dbs = self::parseDatabases($xml);
			//params
			$g_params = self::parseParams($xml);
			//parse routes
			$g_routes = self::parseRoutes($xml, $classesFolder);
			
			if(isset($xml->environment)){
				
				
				foreach($xml->environment as $env){
					
					//force id attribute!
					
					$new_env=array();
					//parse environment
					$new_env['config']=array_merge( array(
						"host"=>"localhost",
						"virtualpath"=>"/",
						"filepath"=>"/",
						"debug"=>"true",
						"id"=>null,
						), Xml_params::parseAttributes($env));
					//databases
					$new_env['dbs'] = self::parseDatabases($env);
					//params
					$new_env['params'] = self::parseParams($env);
					//parse routes
					$new_env['routes'] = self::parseRoutes($env, $classesFolder);
					
					self::$environments[$new_env['config']['id']] = $new_env;
				}
				
			} else {
				trigger_error("<strong>Aura</strong> :: No environments detected", E_USER_WARNING);
			}
			
			//select environment
			$depth=0;
			foreach(self::$environments as $env){
				if(stripos($_SERVER['HTTP_HOST'], $env['config']['host']) !== false){
					//match 1, host
					if(stripos($_SERVER['REQUEST_URI'], $env['config']['virtualpath']) !== false){
						//match 2, uri
						if(stripos($_SERVER['SCRIPT_FILENAME'], $env['config']['filepath']) !== false){
							//match 3 - full match
							self::$id = $env['config']['id'];
							break;
						} else if($depth<2){
							//consider match for now
							self::$id = $env['config']['id'];
							$depth=2;
						}
					} else if($depth<1){
						//consider match for now
						self::$id = $env['config']['id'];
						$depth=1;
					}
				}
			}
			
			//set matched environment
			if(self::$id===null){
				trigger_error("<strong>Aura</strong> :: No possible environments selected", E_USER_ERROR);
			} else {
				self::$host=self::$environments[self::$id]['config']['host'];
				self::$virtualpath=self::$environments[self::$id]['config']['virtualpath'];
				self::$filepath=self::$environments[self::$id]['config']['filepath'];
				self::$debug=self::$environments[self::$id]['config']['debug']=="true"?true:false;
				
				//setup baseurl
				self::$baseurl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . self::$virtualpath;
				
				//boot Log
				if(self::$debug){
					Log::init(true);
					Log::debug(true);
					
					//declare Aura info:
					Log::add(
						"Env ID : \"".self::$id."\"<br />", 
						"Aura Info", 
						"Host : \"".self::$host."\"<br />".
						"VirtualPath : \"".self::$virtualpath."\"<br />".
						"FilePath : \"".self::$filepath."\"<br />".
						"Baseurl : \"".self::$baseurl."\"<br />"
					);
				} else {
					Log::init(false);
				}
			}
			
			//merge stuff
			self::$params = array_merge($g_params, self::$environments[self::$id]['params']);
			self::$databases = array_merge($g_dbs, self::$environments[self::$id]['dbs']);
			self::$routes = array_merge($g_routes, self::$environments[self::$id]['routes']);
			
			if(self::$debug){
				Log::add(
					count(self::$databases)." item(s)<br />"
				, "Databases Info", Text::to_html(Utils::s_var_dump(self::$databases)));
			}
			
			//setup databases
			foreach(self::$databases as $id=>$db_args){
				self::instance_database($id, $db_args);
			}
			
			if(!empty(self::$routes)){
				
				//to-do: add routed classes folder
				Nphp::add_folder(Path::combine($classesFolder, $args['config']));
				//setup routes
				foreach(self::$routes as $id=>$route_args){
					$fargs = array_merge(array("path"=>"/", "to"=>"home.php", "type"=>"static", "first_null"=>"false"), $route_args);
					Routes::add($id, $fargs["path"], $fargs["to"], $fargs["type"], ($fargs["first_null"]=="true"));
				}
				//boot Routes class with Aura mode on
				if($args['route']) Routes::init(true);
			}
			
		}

	}
	
	public static function put($path){
		print self::$baseurl.$path;
	} 
	public static function url_to($path){
		return self::$baseurl.$path;
	}
	
	protected static function parseParams($xml){
		if(isset($xml->struct)){
			if(!is_array($xml->struct)){
				return Utils::mixed_to_array(Xml_params::parseValue($xml), true);
			} else {
				//trigger warning
				trigger_error("<strong>Aura</strong> :: Multiple struct elements detected. ", E_USER_WARNING);	
			}
		}
		return array();
	}
	
	protected static function parseDatabases($xml){
		if(isset($xml->database)){
			
			$ret = array();
			foreach($xml->database as $db){
				$atts = array_merge(array("id"=>null), Xml_params::parseAttributes($db));
				$ret[$atts['id']] = $atts;
			}
			
			return $ret;
		}
		return array();
	}
	
	protected static function parseRoutes($xml, &$folder){
		if(isset($xml->routes)){
			$routes_xml = $xml->routes;
			
			$atts = array_merge(array("controllers"=>null), Xml_params::parseAttributes($routes_xml));
			if($atts["controllers"]!=null) $folder=$atts["controllers"];
			
			$ret = array();
			if(isset($routes_xml->route)){

				$ret = array();
				foreach($routes_xml->route as $route){
					$atts = array_merge(array("id"=>null), Xml_params::parseAttributes($route));
					$ret[$atts['id']] = $atts;
				}
			}
			if(isset($routes_xml->error)){
				
				foreach($routes_xml->error as $error){
					$atts = array_merge(array("id"=>"404", "path"=>"/404", "first_null"=>"false"), Xml_params::parseAttributes($error));
					$ret["nphp-error-".$atts['id']] = $atts;
				}
			}
			return $ret;
		}
		return array();
	}
	
	public static function instance_database($DB, $args){
		if(Nphp::check_lib($DB)){
			trigger_error('<strong>Environments</strong> :: Cannot instance database "'.$DB.'", another library already exists with this name.', E_USER_ERROR);
			return;
		}
		eval("class $DB extends Nphp_static { 
			static public \$instance;
			static function setup(){
				\$args=Utils::combine_args(func_get_args(), 0);
				static::\$instance=Database::setup(\$args);
			}
			static function __callStatic(\$func, \$args){
				return call_user_func_array(array(static::\$instance, \$func), \$args);
			}
		};");
		$DB::setup($args);
	}
	
	public static function get($param){
		if(isset(self::$params[$param])) return self::$params[$param];
		return null;
	}
}
?>