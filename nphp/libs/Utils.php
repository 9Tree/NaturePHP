<?php
#/*
#* 9Tree Utilities Class - v0.2
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
				list($item, $value)=split('=', $str);
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
	static function combine_args($func_args, $start_index, $defaults=false){

		//emptyness test
		if(!isset($func_args[$start_index])){
			if(!$defaults){
				return (object) array();
			} else {
				return (object) self::mixed_to_array($defaults);
			}
		} 
		
		//defaults integrity
		if(!is_array($defaults)) $defaults=self::mixed_to_array($defaults);
		
		//already filtered test
		$a_lim=count($func_args);
		if(is_object($func_args[$start_index]) && $a_lim==$start_index+1){
			if(!$defaults) return $func_args[$start_index];
			return (object) array_merge($defaults, (array) $func_args[$start_index]);
		}
		
		//filter vars
		$args = array();
		$i = 0;
		for ( $a = $start_index; $a < $a_lim; $a++ ){
			self::ref_mixed_to_array($func_args[$a], $args, $i);
		}
		
		return (object) array_merge($defaults, $args);
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
		return self::ref_build_querystring($getArr, '', '');
	}

	//builds querystring from array (supports multi-dimensional arrays)
	private static function ref_build_querystring(&$newGet, $start, $end){
		$qstr='';
		foreach($newGet as $var=>$value){
			if(is_array($value)){
				$qstr.=self::build_querystring($value, $var."[", "]");
			} else {
				$qstr.="&";
				if($start && $end){
					$qstr.=$start.$var.$end."=".$value;
				} else $qstr.=$var."=".$value;
			}
		}
		return $qstr;
	}
}
?>