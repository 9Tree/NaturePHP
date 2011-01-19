<?php
#/*
#* 9Tree Utilities Class - v0.3.5
#* Useful stuff
#*/

class Utils{
	
	//transforms mixed variables (querystring, object or array) into array
	private static function ref_mixed_to_array(&$mixed, &$args, &$i){

		if(is_array($mixed) || is_object($mixed)){
			foreach($mixed as $item=>$value){
				$args['i'][$i] = $value;
				if(is_string($item)) $args[$item]=&$args['i'][$i];
				$i++;
			}	
		} elseif(is_string($mixed)){
			$arr=explode('&', $mixed);
			foreach($arr as $str){
				list($item, $value)=explode('=', $str);
				$args['i'][$i] = $value;
				if(is_string($item)) $args[$item]=&$args['i'][$i];
				$i++;
			}
		}
	}
	
	//ref_mixed_to_array simple port
	static function mixed_to_array($mixed){
		$args=array(array());
		$i=0;
		self::ref_mixed_to_array($mixed, $args, $i);
		return $args;
	}
	
	//combines function mixed arguments set with default variables, returns as object
	static function combine_args($func_args, $start_index, $defaults=array()){

		//emptyness test
		if(!isset($func_args[$start_index])){
			if(!is_array($defaults)){
				return $defaults;
			} else {
				return self::mixed_to_array($defaults);
			}
		} 
		
		//defaults integrity
		if(!is_array($defaults)) $defaults=self::mixed_to_array($defaults);
		
		//already filtered test
		$a_lim=count($func_args);
		if(is_array($func_args[$start_index]) && $a_lim==$start_index+1){
			if(!$defaults) return $func_args[$start_index];
			return array_merge($defaults, (array) $func_args[$start_index]);
		}
		
		//filter vars
		$args = array();
		$i = 0;
		for ( $a = $start_index; $a < $a_lim; $a++ ){
			self::ref_mixed_to_array($func_args[$a], $args, $i);
		}
		
		return array_merge($defaults, $args);
	}
	
	//all famous var_dump in pretty string form
	static function s_var_dump($var){
		static $ident;
		//checks ident
		if(!isset($ident)) {
			$prev_ident=false;
			$ident='';
		} else {
			$prev_ident=$ident;
			$ident.="\t";
		}
		//gets var content
		$str='';
		$is_obj=is_object($var);
		$is_arr=is_array($var);
		if($is_obj || $is_arr) {
			foreach($var as $name=>$value){
				$is_obj=is_object($value);
				$is_arr=is_array($value);
				$str.=$ident.'['.(is_string($name)?'"'.$name.'"':$name).'] :: '.ucfirst(gettype($value)).'('.($is_obj?'':($is_arr?count($value):strlen($value))).') = ';
				if($is_obj || $is_arr) {
					$str.="(\n".self::s_var_dump($value)."\n".$ident."   );\n";
				} else {
					$is_str=is_string($value);
					$str.=($is_str?'"':'').($is_str?str_replace('"', "\\\"", $value):$value).($is_str?'"':'')." ;\n";
				}
			}
		} else {
			$is_str=is_string($var);
			$str.=($is_str?'"':'').($is_str?str_replace('"', "\\\"", $var):$var).($is_str?'"':'')." ;\n";
		}
		
		//resets ident
		if($prev_ident!==false) {
			$ident=$prev_ident;
		} else {
			unset($ident);
		}
		//returns string
		return $str;
	}
	
	//public port for ref_build_querystring
	static function build_querystring($getArr){
		if(is_array($getArr))
			$qs = self::ref_build_querystring($getArr, '', '');
			$hash=(isset($getArr['#']))?"#".urlencode($getArr['#']):'';
			return $qs.$hash;
		return (string) $getArr;
	}

	//builds querystring from array (supports multi-dimensional arrays)
	private static function ref_build_querystring(&$newGet, $start, $end){
		$qstr='';
		foreach($newGet as $var=>$value){
			if($var!='#'){
				if(is_array($value)){
					$qstr.="&".self::ref_build_querystring($value, $start.$var.$end."[", "]");
				} else {
					$qstr.="&";
					if($start && $end){
						$qstr.=$start.$var.$end."=".urlencode($value);
					} else $qstr.=$var."=".urlencode($value);
				}
			}
		}
		
		return substr($qstr, 1);
	}
	
	//insert a value into a specific position in an array (following int values get shifted forward +1)
	static function array_insert(&$array, $insert, $position) {
		if(!isset($array[$position])){
			$array[$position] = $insert;
		} elseif(!is_numeric($position)){
			trigger_error('<strong>Utils::array_insert()</strong> :: string position "'.$position.'" will be overwritten.', E_USER_NOTICE);
			$array[$position]=$insert;
		} else {
			//inserts value into position and shifts other values forward
			print 'rebuilding array at '.$position.'...';
			$count=count($array);
			$tmp=null;
			$tmp2=null;
			for($i=$position; true; $i++){
				if(isset($array[$i])){
					if($i==$position){
						$tmp=$array[$i];
						$array[$i]=$insert;
					} else {
						$tmp2=$tmp;
						$tmp=$array[$i];
						$array[$i]=$tmp2;
					}
				} else {
					$array[$i]=$tmp;
					break;
				}
			}
		}	
	}
	
	//same as array_key_exists() but case insensitive
	function array_ikey_exists($key,$arr){
	    $key=strtolower($key);
	    if($arr && is_array($arr))
	    {
	        foreach($arr as $k => $v)
	        {
	            if(strtolower($k) == $key) return $k;
	        }
	    }
	    return false;
	}
	
	//same as shuffle but works with associative arrays
	function shuffle($array) {
	    $keys = array_keys($array);

	    shuffle($keys);
		$new = array();
	    foreach($keys as $key) {
	        $new[$key] = $array[$key];
	    }

	    return $new;
	}
	
	//generate password
	static function generate_password(){
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
								'length' => 8, 
								'possible' => "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
								'repeat' => true));
								
		$password = "";
		$i = 0; 
		while ($i < $args['length']):

			# pick a random character from the possible ones
			$char = substr($args['possible'], mt_rand(0, strlen($args['possible'])-1), 1);

			# we don't want this character if it's already in the password
			if ($args['repeat'] || !strpos($password, $char)!==false):
				$password .= $char;
				$i++;
			endif;

		endwhile;

		# done!
		return $password;
	}
	
	static function is_assoc($array) {
	    return (is_array($array) && (count($array)==0 || 0 !== count(array_diff_key($array, array_keys(array_keys($array))) )));
	}
}
?>