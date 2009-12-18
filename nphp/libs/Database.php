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

abstract class Database {
	protected $connection; 				// Database connection resource
	protected $name; 					// connection name
	
	private static $types = array('mysql', 'odbc');
	
	protected $result;					//used to store results
	
	public static $instance; 			// last created instance
	protected $instance_id;				//used to store $this instance number
	public static $last_instance_id;	//used to store $this instance number
	public static $instances = array();	// for holding more than 1 instance
	
	//general construct
	function __construct() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array('name' => null, 'resource' => null));
		
		$this->connection = $args['resource'];
		$this->result = null;
		$this->parameters = array();

		Database::$instance = &$this;						//set's this instance as the last added one
		
		Database::$instances[] = &$this;					//add's the instance on top of the the instance stack
		$this->instance_id = ++Database::$last_instance_id;	//set's $this instance_id
		
		if($args['name']) Database::$instances[$args['name']] = &$this;		//add's the instance to the instance stack with the provided name
	}
	
	//general open
	public static function open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array('type' => 'mysql'));
		
		if(!in_array($args['type'], Database::$types)){
			trigger_error('<strong>Database</strong> :: Unrecognized database type "'.$type.'"', E_USER_ERROR);
			return null;
		}
		
		//creates the instance
		$class='Database_'.$args['type'];
		$o = new $class($args);
		$o->_open($args);

		return $o;
	}
	
	//close connection and remove references to instance
	function close() {
		$this->close();
		unset(Database::$instances[$this->instance_id]);
		if($this->instance_id==Database::$last_instance_id) unset(Database::$instance);
	}
	
	//secure string
	function secure($sql, $parameters=array()){
		if(!empty($parameters)){
			return vsprintf(str_replace("?", "%s", str_replace("%", "%%", $sql)), $this->escapeValues($parameters));
		} else return $sql;
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
			case 'string'	:	$str = "'".$this->_escapeString($str)."'";	//escape strings
				break;
			case 'double'	:	$str = "'$str'";	//put doubles as strings
				break;
			case 'boolean'	:	$str = ($str === FALSE) ? 0 : 1;	//booleans to 1/0
				break;
			case 'integer'	: break;	//leave integers be
			default			:	$str = 'NULL';		//rest put to null
				break;
		}
		return $str;
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
		
		return $this->result?true:false;
	}
	
	//insert
	function insert($data, $table){
		
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
		
		
		//execute	
		if($this->execute($sql, $parameters)) {
			//return inserted id
			return $this->_lastID($table);
		} else {
			return false;
		}
	}
	
	//update
	function update($data, $table){
		
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
		
		//execute
		$this->execute($sql, $parameters);
		
		//return affected rows
		return $this->_affectedRows();
	}
	
	//delete
	function delete($table) {
		
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
		if($sql != null)
			$this->execute($sql, $parameters);
		return $this->_fetch();
	}

	/*
	 * Like fetch(), accepts any number of arguments
	 * The first argument is an sprintf-ready query stringTypes
	 * */
	function fetchRow($sql = null, $parameters = array()) {
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
}

?>
