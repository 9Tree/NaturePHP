<?php
class Environments extends Nphp_static{
	
	static function init($filepath=null){
		if($filepath==null){
			$filepath = Path::to('../setup/environments.xml', __FILE__);
		}
		//load file
		
		//parse xml
		
			//set params
			
			//init databases
	}
	
	public static function instance_database($DB, $args){
		if(Nphp::lib_exists($DB)){
			trigger_error('<strong>Environments</strong> :: Cannot instance database "'.$DB.'", another library already exists with this name.', E_USER_ERROR);
			return;
		}
		eval("class $DB extends DatabaseStaticWrapper {};");
		$DB::setup($args);
	}
	
}
?>