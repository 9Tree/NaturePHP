<?php
#/*
#* 9Tree Uris Class
#* Uris and Urls funcionalities
#*/

class Path extends Nphp_static{
	
	private static $script_base=null;
	
	//combine physical paths (files and includes)
	static function to($new_path, $cur_file){
		return dirname($cur_file).'/'.$new_path;
	}
	
	//combine url paths (urls and templates) and print out
	static function put($new_path, $cur_file){
		print self::url_to($new_path, $cur_file);
	}
	
	//combine url paths (urls and templates)
	static function url_to($new_path, $cur_file){
		static $unix;
	    if(!isset($unix)) $unix=!Info::server_is_windows();
	
		$cur_path=self::relative($cur_file, self::myBase(), $unix);
		return self::combine($new_path, $cur_path);
	}
	
	//script base url
	static function myBase(){
		return self::$script_base ? self::$script_base : $_SERVER['SCRIPT_FILENAME'];
	}
	
	//set base script
	static function setBase($new_path, $cur_file){
		self::$script_base = 	self::combine(
								self::combine(
									$new_path, 
									self::relative($cur_file, $_SERVER['SCRIPT_FILENAME'])
								), 
								$_SERVER['SCRIPT_FILENAME']
							).".";
	}
	
	//set base script uri
	static function setBaseUri($request_uri){
		if(strpos("?", $request_uri)!==false){
			$parts = explode("?", $request_uri);
			$request_uri = $parts[0];
		}
		self::$script_base = self::combine(self::relative($request_uri, $_SERVER['SCRIPT_NAME']), $_SERVER['SCRIPT_FILENAME']).".";
	}
	 
	//combine $curPath as base and relative $path to get new fullpath
	static function combine($curPath, $path, $case_sensitive=true){

		if(empty($path)) return $curPath;
		$path=dirname($path).'/';
		$path=substr($path, 0, 2)=="./"?substr($path, 2, strlen($path)-2):$path;
		
		
		//case sensitivity
		if(!$case_sensitive){
			$pathFrom=strtolower($pathFrom);
			$pathTo=strtolower($pathTo);
		}
		
		//create final path
		while(substr($curPath, 0, 3)=="../"){
			//treats curPath
			$curPath=substr($curPath, 3, strlen($curPath)-3);

			//treats path
			$var=strlen($path);
			$prePath=substr($path, 0, $var-1); //takes out final 
			$path=($var && substr($path, $var-3)!='../')?
				preg_replace('#^(.*?\/)(?!.*?\/).*?$#', '\\1', $prePath):
				$path.'../';
			$path=$prePath==$path?'':$path;
		}
		
		return $path.$curPath;
	}
	
	//get relative path from $pathFrom to $pathTo
	static function relative($pathTo, $pathFrom, $case_sensitive=true){	//to review - unecessarily too complex
		if(empty($pathFrom) || $pathFrom=="/") return $pathTo;
		
		//case sensitivity
		if(!$case_sensitive){
			$pathFrom=strtolower($pathFrom);
			$pathTo=strtolower($pathTo);
		}
		
		$pathFrom = str_replace('\\','/',$pathFrom); // sanitize for Win32 installs
		$pathFrom = preg_replace('|/+|','/', $pathFrom); // remove any duplicate slash
		
		$pathTo = str_replace('\\','/',$pathTo);
		$pathTo = preg_replace('|/+|','/', $pathTo);
		
		//strips root
		if(substr($pathFrom, 0, 1)=="/") $pathFrom=substr($pathFrom, 1, strlen($pathFrom)-1);
		if(substr($pathTo, 0, 1)=="/") $pathTo=substr($pathTo, 1, strlen($pathTo)-1);
		
		//splits file
		
		if(substr($pathTo, -1, 1)=="/"){
			$pathTo=dirname($pathTo.".").'/';
			$add_after="";
		} else {
			$tmp_arr=explode("/", $pathTo);
			$add_after=$tmp_arr[count($tmp_arr)-1];
			$pathTo=dirname($pathTo).'/';
		}
		$pathFrom=dirname($pathFrom).'/';
		
		
		//strips common folders
		$pf_len=strlen($pathFrom);
		$pt_len=strlen($pathTo);
		
		while(1){
			
			//if same folder
			$pf_pos=strpos($pathFrom, "/");
			$pt_pos=strpos($pathTo, "/");
			if($pf_pos && $pt_pos //both have a folder
				&& $pf_pos==$pt_pos && substr($pathFrom, 0, $pf_pos)==substr($pathTo, 0, $pt_pos)){
				//strips this folder
				$pf_pos++;
				$pt_pos++;
				$pf_len-=$pf_pos;
				$pt_len-=$pt_pos;
				$pathFrom=substr($pathFrom, $pf_pos, $pf_len);
				$pathTo=substr($pathTo, $pt_pos, $pt_len);

			} elseif($pathFrom=='./') {
				$final_path=$pathFrom.$pathTo;
				break;
				
			} else {
				$pathFrom=preg_replace('#([^/]*\/)#', '../', $pathFrom);
				$final_path=$pathFrom.$pathTo;
				break;
				
			}
		}
		$url=$final_path.$add_after;
		return $url;
	}
	
	//get current url, allowing get inclusions and/or exclusions
	static function this_url(){
		$args=Utils::combine_args(func_get_args(), 0, array(
				'get_in'=>array(),
				'get_out'=>array(),
				'#'=>''
				));
		$qstr=self::this_qs($args);
		$var=(!empty($qstr))?"?".$qstr:'';
		
		return $_SERVER['SCRIPT_NAME'].$var;	
	}
	
	static function this_qs(){
		$args=Utils::combine_args(func_get_args(), 0, array(
				'get_in'=>array(),
				'get_out'=>array(),
				'#'=>''
				));
		return Utils::build_querystring(self::this_GET($args));
	}
	
	static function this_GET(){
		$args=Utils::combine_args(func_get_args(), 0, array(
				'get_in'=>array(),
				'get_out'=>array(),
				'#'=>''
				));
		if(!is_array($args['get_in'])) $args['get_in']=array();
		if(!is_array($args['get_out'])) $args['get_out']=array();
		$newGet=array();
		if(count($_GET)){ //mudar para suportar arrays
			foreach($_GET as $name=>$item){
				if(!in_array($name, $args['get_out'])){
					$newGet[$name]=&$_GET[$name];
				}
			}
		}
		if($args['get_in']){
			foreach($args['get_in'] as $name=>$value){
				$newGet[$name]=&$args['get_in'][$name];
			}
		}
		if(isset($args['#'])) $newGet['#']=$args['#'];
		return $newGet;	
	}
	
	//request uri for all systems (from Drupal)
	static function request_uri() {

	  if (isset($_SERVER['REQUEST_URI'])) {
	    $uri = $_SERVER['REQUEST_URI'];
	  }
	  else {
	    if (isset($_SERVER['argv'])) {
	      $uri = $_SERVER['SCRIPT_NAME'] .'?'. $_SERVER['argv'][0];
	    }
	    elseif (isset($_SERVER['QUERY_STRING'])) {
	      $uri = $_SERVER['SCRIPT_NAME'] .'?'. $_SERVER['QUERY_STRING'];
	    }
	    else {
	      $uri = $_SERVER['SCRIPT_NAME'];
	    }
	  }

	  return $uri;
	}
	
	//clean uri string
	function sanitize_url( $url ) {
		$return = $url;
		if ('' != $return) {
			$return = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@]|i', '', $return);
			$strip = array('%0d', '%0a');
			$return = str_replace($strip, '', $return);
			$return = str_replace(';//', '://', $return);
			/* If the URL doesn't appear to contain a scheme, we
			 * presume it needs http:// appended (unless a relative
			 * link starting with / or a php file).
			*/
			if ( strpos($return, ':') === false && substr( $return, 0, 1 ) != '/' && !preg_match('/^[a-z0-9-]+?\.php/i', $return) )
				$return = 'http://' . $return;
		}
		return self::fireHooks('sanitize_url', $return, array($url));	
	}
}
?>