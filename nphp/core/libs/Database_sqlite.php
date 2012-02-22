<?php
class Database_sqlite extends Database {
	public $type='sqlite';
	public $insert_default_values=" DEFAULT VALUES";

	// opens MySQL database connection
	protected function _open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
							'database'		=> null, 
							'user'			=> null, 
							'password'		=> null, 
							'mode' 			=> 0666
							));
					
		//tries connection
		if(!$this->connection = sqlite_open($args['database'], $args['mode'])){
			trigger_error('<strong>Database</strong> :: SQLite Database connection failed', E_USER_WARNING);
			return null;
		}
		$this->is_connected = true;

		return $this->connection;
	}
	
	function _close() {
		sqlite_close($this->connection);
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
		return sqlite_escape_string( get_magic_quotes_gpc()?stripslashes($string):$string );
	}
	
	function _escapeField($field){
		return '['.str_replace(array('[', ']'), '', (get_magic_quotes_gpc()?stripslashes($field):$field) ).']';
	}
	
	protected function _query($sql) {
		return sqlite_query($sql, $this->connection);
	}
	
	protected function _affectedRows() {
		return sqlite_changes($this->connection);
	}

	protected function _error() {
		return $this->connection ? sqlite_error_string(sqlite_last_error($this->connection)) : "SQLite not connected";
	}

	
	protected function _fetch() {
		// use mysql_data_seek to get to row index ?
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = sqlite_fetch_array($this->result, SQLITE_ASSOC)) {
			$data[] = $row;
		}
		return $data;
	}

	protected function _fetchRow() {
		return sqlite_fetch_array($this->result, SQLITE_ASSOC);
	}

	protected function _lastID() {
		return sqlite_last_insert_rowid($this->connection);
	}

	protected function _numberRows() {
		return sqlite_num_rows($this->result);
	}
	
}
?>