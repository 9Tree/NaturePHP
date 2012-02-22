<?php
class Database_mssql extends Database {
	public $type='mssql';
	public $insert_default_values=" default values";

	// opens MySQL database connection
	protected function _open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
							'database'		=> null, 
							'user'			=> null, 
							'password'		=> null, 
							'host' 			=> 'localhost',
							'port' 			=> null
							));
					
		//tries connection
		if(!$this->connection = mssql_connect(($args['host'].($args['port']?':'.$args['port']:'')), $args['user'], $args['password'], true)){
			trigger_error('<strong>Database</strong> :: MSSQL Database connection failed', E_USER_WARNING);
			return null;
		}
		
		//tries database selection
		if(!@mssql_select_db($args['database'], $this->connection)){
			trigger_error('<strong>Database</strong> :: MSSQL Database selection failed: ' . $this->_error(), E_USER_WARNING);
			return null;
		}
		$this->is_connected = true;

		return $this->connection;
	}
	
	function _close() {
		mssql_close($this->connection);
	}
	
	function _insert_default_values(){
    return $this->insert_default_values;
  }
	
	function _limit($sql, $var_limit){
		//limit
		if(is_array($var_limit) && !empty($var_limit)){
			if(count($var_limit)==1){
				$sql .= ' limit '.intval($var_limit[0]);
			} else {
				$sql .= ' limit '.intval($var_limit[0]).', '.intval($var_limit[1]);
			}
		}
		return $sql;
	}
	
	protected function _escapeString($string) {
		return addslashes( get_magic_quotes_gpc()?stripslashes($string):$string );
	}
	
	function _escapeField($field){
		return '['.str_replace(array('[', ']'), '', (get_magic_quotes_gpc()?stripslashes($field):$field) ).']';
	}
	
	protected function _query($sql) {
		return mssql_query($sql, $this->connection);
	}
	
	protected function _affectedRows() {
		return mssql_rows_affected($this->connection);
	}

	protected function _error() {
		return $this->connection ? mssql_get_last_message($this->connection) : "MSSQL not connected";
	}

	
	protected function _fetch() {
		// use mssql_data_seek to get to row index ?
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = mssql_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		return $data;
	}

	protected function _fetchRow() {
		return mssql_fetch_assoc($this->result);
	}

	protected function _lastID() {
		$id = ""; 
		$rs = mssql_query("SELECT @@identity AS id", $this->connection); 
		if ($row = mssql_fetch_row($rs)) { 
			$id = trim($row[0]); 
		} 
		mssql_free_result($rs); 

		return $id;
	}

	protected function _numberRows() {
		return mssql_num_rows($this->result);
	}
	
}
?>