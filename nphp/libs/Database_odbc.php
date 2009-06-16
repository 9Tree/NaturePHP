<?php
class Database_odbc extends Database {
	protected $type='odbc';
	protected $dsn_types=array('mssql');
	protected $dsn_type;

	// opens MySQL database connection
	protected function _open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
							'dsn_type'		=> 'mssql',
							'dsn'			=> null, 
							'user'			=> null, 
							'password'		=> null
							));
							
		//tries connection
		if(!$this->connection = odbc_connect($args['dsn'], $args['user'] , $args['password'])){
			trigger_error('<strong>Database</strong> :: ODBC Database connection failed: ' . $this->_error(), E_USER_WARNING);
			return null;
		}
		
		$this->dsn_type = $args['dsn_type'];

		return $this->connection;
	}
	
	function _close() {
		odbc_close($this->connection);
	}
	
	function _limit($sql, $var_limit){
		//limit
		switch($this->dsn_type){
			case 'mssql':
				//to-do...
			break;
		}

		return $sql;
	}
	
	protected function _escapeString($string) {
		return addslashes( get_magic_quotes_gpc()?stripslashes($string):$string );
	}
	
	function _escapeField($field){

		switch($this->dsn_type){
			case 'mssql':
				return '['.str_replace('[', '', str_replace(']', '', (get_magic_quotes_gpc()?stripslashes($field):$field) )).']';
			break;
		}
		
	}
	
	protected function _query($sql) {

		switch($this->dsn_type){
			case 'mssql':
				return odbc_exec($this->connection, "SET NOCOUNT ON\r\n".$sql);	//enables insert_id and num_rows functions
			break;
		}
	}
	
	protected function _affectedRows() {
		return odbc_num_rows($this->result);
	}

	protected function _error() {
		return odbc_errormsg($this->connection);
	}

	
	protected function _fetch() {
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		return odbc_fetch_array($this->result);
	}

	protected function _fetchRow() {
		return odbc_fetch_row($this->result);
	}

	protected function _lastID() {
		
		switch($this->dsn_type){
			case 'mssql':
				$result = odbc_exec($this->connection, 'SELECT @@IDENTITY AS Ident');
				odbc_fetch_into($result, $row);
				return $row[0];
			break;
		}
		
	}

	protected function _numberRows() {
		return odbc_num_rows($this->result);
	}
	
}
?>