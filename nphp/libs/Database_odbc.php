<?php
class Database_odbc extends Database {
	public $type='odbc';
	protected $dsn_types=array('mssql');
	protected $dsn_type;
	protected $insert_default_values=array('mssql'=>" default values");

	// opens MySQL database connection
	protected function _open() {
		
		//get options
		$args=Utils::combine_args(func_get_args(), 0, array(
							'dsn_type'		=> 'mssql',
							'dsn'			=> null, 
							'user'			=> null, 
							'password'		=> null,
							'cursor_type'	=> SQL_CUR_USE_ODBC
							));
							
		//tries connection
		if(!$this->connection = odbc_connect($args['dsn'], $args['user'] , $args['password'], $args['cursor_type'])){
			trigger_error('<strong>Database</strong> :: ODBC Database connection failed', E_USER_WARNING);
			return null;
		}
		
		switch($this->dsn_type){
			case 'mssql':
				return $this->execute("SET QUOTED_IDENTIFIERS ON");
			break;
		}

		$this->dsn_type = $args['dsn_type'];

		return $this->connection;
	}
	
	function _close() {
		odbc_close($this->connection);
	}
	
	function _insert_default_values(){
    return $this->insert_default_values[$this->dsn_type];
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
    switch($this->dsn_type){
			case 'mssql':
				return str_replace("'", "''", (get_magic_quotes_gpc()?stripslashes($string):$string) );
			break;
		}
	}
	
	function _escapeField($field){

		switch($this->dsn_type){
			case 'mssql':
				return '"'.str_replace('"', '', str_replace("'", "''", (get_magic_quotes_gpc()?stripslashes($field):$field) )).'"';
			break;
		}
		
	}
	
	protected function _query($sql) {

		switch($this->dsn_type){
			case 'mssql':
			    //free last result if exists
			    if($this->result) odbc_free_result($this->result);
				return @odbc_exec($this->connection, "SET NOCOUNT ON\r\n".$sql);	//enables insert_id and num_rows functions
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
		$result=array();
		while($res=$this->_odbc_fetch_array($this->result)){
			$result[]=$res;
		}
		return $result;
	}

	protected function _fetchRow() {
		return $this->_odbc_fetch_array($this->result);
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
	
	function _odbc_fetch_array(& $odbc_result) {
		if (function_exists('odbc_fetch_object')){
			return odbc_fetch_array($odbc_result);
		}else{
			$rs = array();
			$rs_assoc = false;
			if (odbc_fetch_into($odbc_result, $rs)) {
				$rs_assoc=array();
				foreach ($rs as $k=>$v) {
					$field_name= odbc_field_name($odbc_result, $k+1);
					$rs_assoc[$field_name] = $v;
				}
			}
			return $rs_assoc;
		}
	}
	
}
?>