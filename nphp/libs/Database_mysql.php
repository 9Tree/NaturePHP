<?php
class Database_mysql extends Database {
	public $type='mysql';
	public $insert_default_values=" () values()";

	// opens MySQL database connection
	protected function _open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
							'database'		=> null, 
							'user'			=> null, 
							'password'		=> null, 
							'host' 			=> 'localhost',
							'port' 			=> null,
							'charset'		=> 'utf8',
							'collation'		=> 'utf8_general_ci'
							));
					
		//tries connection
		if(!$this->connection = mysql_connect(($args['host'].($args['port']?':'.$args['port']:'')), $args['user'], $args['password'], true)){
			trigger_error('<strong>Database</strong> :: MySQL Database connection failed', E_USER_WARNING);
			return null;
		}
		
		//tries database selection
		if(!@mysql_select_db($args['database'], $this->connection)){
			trigger_error('<strong>Database</strong> :: MySQL Database selection failed: ' . $this->_error(), E_USER_WARNING);
			return null;
		}
		$this->is_connected = true;
		
		//sets charset and collation
		$this->execute("SET NAMES '".$args['charset']."' collate '".$args['collation']."'");
		$this->execute("SET CHARACTER SET '".$args['charset']."'");

		return $this->connection;
	}
	
	function _close() {
		mysql_close($this->connection);
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
		return mysql_real_escape_string( get_magic_quotes_gpc()?stripslashes($string):$string );
	}
	
	function _escapeField($field){
		return '`'.str_replace('`', '', (get_magic_quotes_gpc()?stripslashes($field):$field) ).'`';
	}
	
	protected function _query($sql) {
		return mysql_query($sql, $this->connection);
	}
	
	protected function _affectedRows() {
		return mysql_affected_rows($this->connection);
	}

	protected function _error() {
		return $this->connection ? mysql_error($this->connection) : "MySQL not connected";
	}

	
	protected function _fetch() {
		// use mysql_data_seek to get to row index ?
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = mysql_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		return $data;
	}

	protected function _fetchRow() {
		return mysql_fetch_assoc($this->result);
	}

	protected function _lastID() {
		return mysql_insert_id($this->connection);
	}

	protected function _numberRows() {
		return mysql_num_rows($this->result);
	}
	
}
?>