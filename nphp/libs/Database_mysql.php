<?php
class Database_mysql extends Database {
	private $database;
	protected $type='mysql';

	// user, password, database, host
	protected function _open($database, $user, $password, $host) {
		$this->database = $database;
		$this->connection = mysql_connect($host, $user, $password);
		if($this->connection)
			mysql_select_db($database, $this->connection);
		//$this->buildSchema();
		return $this->connection;
	}
	
	function close() {
		mysql_close($this->connection);
	}
	
	function _limit($sql, $var_limit){
		//limit
		if(is_string($var_limit)){
			$sql.=' limit '.$var_limit;
		} elseif(is_array($var_limit) && !empty($var_limit)){
			$limit = array_shift($var_limit);
			$sql .= ' limit '.$this->secure($limit, $var_limit);
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
		return mysql_error($this->connection);
	}

	
	protected function _fetch() {
		// use mysql_data_seek to get to row index
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = mysql_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		//mysql_free_result($this->result);
		// rewind?
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
	
} // mysql
?>