<?php
/*
Database - enhanced dbfacile
v0.2
Based on greaterscope's dbFacile - A Database API that should have existed from the start
Version 0.4.2
http://www.greaterscope.net/projects/dbFacile

Notes:
functions declared starting with _ like _func() indicate functions that are not actually declareted in this class, 
but have to be implemented on the extended classes (eg. Database_mysql );
*/

abstract class Database extends Nphp_basic{
	protected $connection; 				// Database connection resource
	protected $name; 					// connection name
	public $is_opened = false;	//connection status
	public $is_connected = false;	//connection status
	protected $args = array();
	
	protected $result;					//used to store results
	
	//general construct
	function __construct() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array('resource' => null));
		
		$this->connection = $args['resource'];
		$this->result = null;
		$this->args = $args;
	}
	
	//general setup
	public static function setup() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array('type' => 'mysql'));
		
		if(!Nphp::check_lib("Database_".$args['type'])){
			trigger_error('<strong>Database</strong> :: Unrecognized database type "'.$type.'"', E_USER_ERROR);
			return null;
		}
		
		//creates the instance
		$class='Database_'.$args['type'];
		$o = new $class($args);
		
		return $o;
	}
	
	function check_init(){
		if(!$this->is_opened){
			$this->is_opened=true;
			$this->_open($this->args);
		}
	}
	
	//general open
	public static function open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0);
		
		$o=self::setup($args);
		
		$o->is_opened=true;
		$o->_open($args);
		
		return $o;
	}
	
	//close connection and remove references to instance
	function close() {
		$this -> _close();
	}
	
	//secure string
	function secure($sql, $parameters=array()){
		if(!empty($parameters)){
			$return=vsprintf(str_replace("?", "%s", str_replace("%", "%%", $sql)), $this->escapeValues($parameters));
		} else $return=$sql;
		return $return;
	}
	
	//builds query conditions from $args into $sql 
	function build_query_conditions($sql, $args){
		
		//where
		if(is_string($args['where'])){
			$sql .= ' where '.$args['where'];
		} elseif(is_array($args['where']) && !empty($args['where'])){
			$where = array_shift($args['where']);
			$sql .= ' where '.$this->secure($where, $args['where']);
		}
		
		//group
		if(is_string($args['group'])){
			$sql.=' group by '.$args['group'];
		} elseif(is_array($args['group']) && !empty($args['group'])){
			$group = array_shift($args['group']);
			$sql .= ' where '.$this->secure($group, $args['group']);
		}
		
		//order
		if(is_string($args['order'])){
			$sql.=' order by '.$args['order'];
		} elseif(is_array($args['order']) && !empty($args['order'])){
			$order = array_shift($args['order']);
			$sql .= ' where '.$this->secure($order, $args['order']);
		}
		
		//limit (depends on sql system)
		return $this->_limit($sql, $args['limit']);
	}
	
	//escape value function
	public function escapeValue($str){
		switch (gettype($str)){
			case 'string'	:	$ret = "'".$this->_escapeString($str)."'";	//escape strings
				break;
			case 'double'	:	$ret = "'$str'";	//put doubles as strings
				break;
			case 'boolean'	:	$ret = ($str === FALSE) ? 0 : 1;	//booleans to 1/0
				break;
			case 'integer'	:	$ret = $str;	//leave integers be
				break;
			default			:	$ret = 'NULL';		//rest put to null
				break;
		}
		return $ret;
	}
	
	//escape values array
	function escapeValues($arr){
		$key = array_keys($arr);
		$size = count($key);
		for ($i=0; $i<$size; $i++) $arr[$key[$i]] = $this->escapeValue($arr[$key[$i]]);
		return $arr;
	}
	
	//escape fields array
	function escapeFields($arr){
		$key = array_keys($arr);
		$size = count($key);
		for ($i=0; $i<$size; $i++) $arr[$key[$i]] = $this->_escapeField($arr[$key[$i]]);
		return $arr;
	}
	
	
	
	
	/*
	 * Performs a query using the given string and parameters.
	 * Used by the other query functions.
	 * */
	function execute($sql, $parameters = array()) {
		
		$this->check_init();
		
		//benchmarking is important
		$time_start = microtime(true);
		 
		//put all the parameters into the sql
		$fullSql = $this->secure($sql, $parameters);
		
		$this->result = $this->_query($fullSql); // sets $this->result
		
		//trigger notice
		trigger_error('<strong>Database</strong> :: ' . $fullSql . " :: <small>" . (number_format(microtime(true) - $time_start, 8))." secs</small>", E_USER_NOTICE);
		
		//if there's an error, report it
		if(!$this->result && (error_reporting() & 1))
			trigger_error('<strong>Database</strong> :: Error in query: ' . $this->_error(), E_USER_WARNING);
		
		return ($this->result?true:false);
	}
	
	//insert
	function insert($data, $table=array()){
		
		$this->check_init();
		
		if(is_string($table)&&is_array($data)){
			//data mode
			
			$parameters=array();
			//insert query
			$sql = 'insert into ' . $this->_escapeField($table);

			if(!empty($data)){	//insert data
	      		$sql .= ' (';
	      		$values = 'values(';
				foreach($data as $key => $value) {
					$sql .= $this->_escapeField($key) . ',';
					$values .= '?,';
					$parameters[] = $value;
				}
				$sql = substr($sql, 0, -1).') '.substr($values, 0, -1).')'; // strip off last commas and concat the sql statement

			} else {
	      		$sql .= $this->_insert_default_values();
	    	}
		} elseif(is_array($table)&&is_string($data)) {
			//direct query mode
			$sql = $data;
			$parameters = $table;
		} else trigger_error('<strong>Database</strong> :: unexpected set of parameters at insert()', E_USER_ERROR);
		

		//execute	
		if($this->execute($sql, $parameters)) {
			//return inserted id
			return $this->_lastID($table);
		} else {
			return false;
		}
	}
	
	//update
	function update($data, $table=array()){
		
		$this->check_init();
		
		if(is_string($table)&&is_array($data)){
			//data mode
			//get options
			$args=Utils::combine_args(func_get_args(), 2, array('where'=>null, 'group'=>null, 'order'=>null, 'limit'=>null));

			$parameters=array();

			//create base query
			$sql = 'update ' . $this->_escapeField($table);
			if(!empty($data)){	//insert data
				$sql .= ' set ';
				foreach($data as $key => $value) {
					$sql .= $this->_escapeField($key) . '=?,';
					$parameters[] = $value;
				}
				$sql = substr($sql, 0, -1); // strip off last comma

			} else {	//no data to insert
				$sql = $sql.') '.$values.')'; // strip off last commas and concat the sql statement
			}

			//insert query conditions (sql is changed by reference)
			$sql = $this->build_query_conditions($sql, $args);
		
		} elseif(is_array($table)&&is_string($data)) {
			//direct query mode
			$sql = $data;
			$parameters = $table;
		} else trigger_error('<strong>Database</strong> :: unexpected set of parameters at insert()', E_USER_ERROR);
		
		//execute
		$this->execute($sql, $parameters);
		
		//return affected rows
		return $this->_affectedRows();
	}
	
	//delete
	function delete($table) {
		
		$this->check_init();
		
		//get options
		$args=Utils::combine_args(func_get_args(), 1, array('where'=>null, 'group'=>null, 'order'=>null, 'limit'=>null));
		
		//create base query
		$sql = 'delete from ' . $table;
		
		//insert query conditions (sql is changed by reference)
		$sql = $this->build_query_conditions($sql, $args);
		
		//execute
		$this->execute($sql);
		
		//return affected rows
		return $this->_affectedRows();
	}
	
	//number of available rows in $this->result
	function numberRows(){
		return $this->_numberRows();
	}
	
	/*
	 * Fetches all of the rows (associatively) from the query.
	 * Most other retrieval functions build off this
	 * */
	function fetchAll($sql, $parameters = array()) {
		$this->check_init();
		//$sql = $this->transformPlaceholders(func_get_args());
		$this->execute($sql, $parameters);
		if($this->_numberRows()) {
			return $this->_fetchAll();
		}
		// no records, thus return empty array
		// which should evaluate to false, and will prevent foreach notices/warnings 
		return array();
	}
	
	/*
	 * This is intended to be the method used for large result sets.
	 * It is intended to return an iterator, and act upon buffered data.
	 * */
	function fetch($sql = null, $parameters = array()) {
		$this->check_init();
		if($sql != null)
			$this->execute($sql, $parameters);
		return $this->_fetch();
	}

	/*
	 * Like fetch(), accepts any number of arguments
	 * The first argument is an sprintf-ready query stringTypes
	 * */
	function fetchRow($sql = null, $parameters = array()) {
		$this->check_init();
		if($sql != null)
			$this->execute($sql, $parameters);
		if($this->result)
			return $this->_fetchRow();
		return null;
	}

	/*
	 * Fetches the first call from the first row returned by the query
	 * */
	function fetchCell($sql, $parameters = array()) {
		$this->check_init();
		if($this->execute($sql, $parameters)) {
			if($this->result && is_array($data=$this->_fetchRow())) 	//verification added by 9Tree  (20/12/2008)
				return array_shift($data); // shift first field off first row
		}
		return null;
	}

	/*
	 * This method is quite different from fetchCell(), actually
	 * It fetches one cell from each row and places all the values in 1 array
	 * */
	function fetchColumn($sql, $parameters = array()) {
		$this->check_init();
		if($this->execute($sql, $parameters)) {
			$cells = array();
			foreach($this->_fetchAll() as $row) {
				$cells[] = array_shift($row);
			}
			return $cells;
		} else {
			return array();
		}
	}

	/*
	 * Should be passed a query that fetches two fields
	 * The first will become the array key
	 * The second the key's value
	 */
	function fetchKeyValue($sql, $parameters = array()) {
		$this->check_init();
		if($this->execute($sql, $parameters)) {
			$data = array();
			foreach($this->_fetchAll() as $row) {
				$key = array_shift($row);
				if(count($row) == 1) { // if there were only 2 fields in the result
					// use the second for the value
					$data[ $key ] = array_shift($row);
				} else { // if more than 2 fields were fetched
					// use the array of the rest as the value
					$data[ $key ] = $row;
				}
			}
			return $data;
		} else
			return array();
	}
	
	function __destruct() {
		if ($this->connection) {
			$this -> close();
		}
	}
	
	
	//easy access functionality
	protected static $call_cache=array();
	protected static $call_use_cache=true;
	
	function use_call_cache($cache){
		$this->$call_use_cache = $cache;
	}
	
	function __call($name, $arguments){
		//check
		$args_num = count($arguments);
		$pos_by = strpos($name, '_by_');
		$has_by = $pos_by?true:false;
		$has_all = strpos($name, 'all_')===0;
		$has_fields = ($has_by && $args_num==2) || (!$has_by && $args_num==1);
		$unique_field = false;
		$all_fields = $args_num==0 || ($has_by && $args_num==1);
		
		if(	($has_by && $args_num==0 || $args_num>2) ||
			(!$has_by && $args_num>1)
			) {
			trigger_error('<strong>Database</strong> :: Dynamic call error for '.$name, E_USER_WARNING);
			return null;
		}
		
		
		//build query
		//what
		if($all_fields) $what = "*";
		elseif($args_num==1) {
			if(is_array($arguments[0])){
				$tmp_first=true;
				foreach($arguments[0] as $field){
					if(!$tmp_first) $what .= ", ";
					else $tmp_first=false;
					$what .= $this->_escapeField($field);
				}
				if(count($arguments[0])==1) $unique_field = true;
			} else {
				$unique_field = true;
				$what = $this->_escapeField($arguments[0]);
			}
			
		} elseif($args_num==2) {
			if(is_array($arguments[1])){
				$tmp_first=true;
				foreach($arguments[1] as $field){
					if(!$tmp_first) $what .= ", ";
					else $tmp_first=false;
					$what .= $this->_escapeField($field);
				}
				if(count($arguments[1])==1) $unique_field = true;
			} else {
				$unique_field = true;
				$what = $this->_escapeField($arguments[1]);
			}
			
		}
		
		//table
		if($has_all && $has_by){
			$table = substr($name, 4, $pos_by-4);
			$by_field = substr($name, $pos_by+4);
		} elseif($has_by){
			$table = substr($name, 0, $pos_by);
			$by_field = substr($name, $pos_by+4);
		} elseif($has_all){
			$table = substr($name, 4);
		} else {
			$table = $name;
		}
		
		//where
		$params=array();
		$where="";
		if($has_by){
			$params[]=$arguments[0];
			$where = "where $by_field=?";
		}
		
		$query = "SELECT $what from $table $where";
		
		if($has_all) {
			return $this->fetch($query, $params);
		} elseif($unique_field) {
			if($has_by && static::$call_use_cache){
				if(	!isset(static::$call_cache[$table]) ||
					!isset(static::$call_cache[$table][$by_field]) ||
					!isset(static::$call_cache[$table][$by_field][$params[0]]) ||
					!isset(static::$call_cache[$table][$by_field][$params[0]][$what])
				){
					static::$call_cache[$table][$by_field][$params[0]][$what] = $this->fetchCell($query, $params);
				}

				return static::$call_cache[$table][$by_field][$params[0]][$what];
			} else return $this->fetchCell($query, $params);
		} else {
			return $this->fetchRow($query, $params);
		}
	}
}

?>
