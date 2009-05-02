<?php
/*
Database - enhanced dbfacile
v0.1
Based on greaterscope's dbFacile - A Database API that should have existed from the start
Version 0.4.2
http://www.greaterscope.net/projects/dbFacile
*/

abstract class Database {
	protected $connection; // handle to Database connection
	protected $query;
	protected $result;
	protected $fields;
	protected $fieldNames;
	protected $schemaNameField;
	protected $schemaTypeField;

	// these flags are not yet implemented (20080630)
	public $cacheQueries = false; // caches query results in memory by query string
	public $cacheRepeatQueries = false; // caches query results in memory by query string
	//protected $cache = array();
	
	// new, more robust caching
	protected $previousQuery; // cache of previous query
	protected $previousResult; // cache of previous result set
	
	public $schema = array(); // this will probably become protected
	//public static $schemaCache; // filename to use when saving/reading full database schema cache
	public static $instance; // last created instance
	public static $instances = array(); // for holding more than 1 instance

	// 2007-08-25
	protected $foreignKeys; // array('TABLE'=>array('FIELD'=>'TO_TABLE.FIELD'))
	protected $reverseForeignKeys; // a data structure that holds the reverse of normal foreign key mappings

	// implement these methods when creating driver subclasses
	// need to add _open() to the mix somehow
	public abstract function beginTransaction();
	public abstract function commitTransaction();
	public abstract function rollbackTransaction();
	public abstract function close();
	protected abstract function _affectedRows();
	protected abstract function _error();
	protected abstract function _escapeString($string);
	protected abstract function _fetch();
	protected abstract function _fetchAll();
	protected abstract function _fetchRow();
	protected abstract function _fields($table);
	protected abstract function _foreignKeys($table);
	protected abstract function _lastID();
	protected abstract function _numberRows();
	protected abstract function _query($sql);
	protected abstract function _rewind($result);
	protected abstract function _tables();

	function __construct($handle = null) {
		$this->connection = $handle;
		$this->query = $this->result = null;
		$this->parameters = array();
		//$this->numberRecords = 0; // probably no longer needed

		$this->fields = array();
		$this->fieldNames = array();
		$this->primaryKeys = array();
		$this->foreignKeys = array();
		$this->reverseForeignKeys = null;
		$this->schema = null;

		Database::$instance = $this;
	}
	
	public static function open($type, $database, $user = '', $password = '', $host = 'localhost') {
		// try to use PDO if available
		switch($type) {
			case 'mssql':
			case 'mysql':
			case 'postgresql':
				$name = 'Database_' . $type;
				if(is_resource($database)) {
					$o = new $name($database);
				}
				if(is_string($database)) {
					$o = new $name();
					$o->_open($database, $user, $password, $host);
				}
				return $o;
				break;
			case 'sqlite':
				if(is_resource($database)) {
					$o = new Database_sqlite($database);
				}
				if(is_string($database)) {
					$o = new Database_sqlite();
					$o->_open($database);
				}
				return $o;
				break;
		}
	}

	/*
	 * Performs a query using the given string.
	 * Used by the other _query functions.
	 * */
	function execute($sql, $parameters = array(), $cache = true) {
		$this->query = $sql;
		$this->parameters = $parameters;
			
		$fullSql = $this->makeQuery($sql, $parameters);

		if($cache && $this->previousQuery == $fullSql) {
			$this->result = $this->previousResult;
			$this->_rewind($this->result);
				
			trigger_error('<strong>Database</strong> :: (cached) ' . $fullSql . '', E_USER_NOTICE);
			
			return ($this->result !== false);
		} else {
			$this->previousQuery = $this->previousResult = null;
		}

		$time_start = microtime(true);

		$this->result = $this->_query($fullSql); // sets $this->result
		
		trigger_error('<strong>Database</strong> :: ' . $fullSql . " :: <small>" . (number_format(microtime(true) - $time_start, 8))." secs</small>", E_USER_NOTICE);
		

		if(!$this->result && (error_reporting() & 1))
			trigger_error('<strong>Database</strong> :: Error in query: ( ' . $this->query . ' )<br /><br />' . $this->_error(), E_USER_WARNING); // arguments were in wrong order - 9Tree (31/12/2008)

		if($this->result) {
			if($cache) {
				$this->previousQuery = $fullSql;
				$this->previousResult = $this->result;
			}
			return true;
		} else {
			$this->previousQuery = $this->previousResult = null;
			return false;
		}
	}
	
	/*
	 * Alias for insert
	 * */
	function add($data, $table) {
		return $this->insert($data, $table);
	}

	/*
	 * Passed an array and a table name, it attempts to insert the data into the table.
	 * Check for boolean false to determine whether insert failed
	 * */
	function insert($data, $table) {
		// the following block swaps the parameters if they were given in the wrong order.
		// it allows the method to work for those that would rather it (or expect it to)
		// follow closer with SQL convention:
		// insert into the TABLE this DATA
		if(is_string($data) && is_array($table)) {
			$tmp = $data;
			$data = $table;
			$table = $tmp;
			//trigger_error('Database - Parameters passed to insert() were in reverse order, but it has been allowed', E_USER_NOTICE);
		}
		// appropriately quote input data
		// remove invalid fields
		$data = $this->filterFields($data, $table);

		// wrap quotes around values that need them
		// actually, shouldn't quote data yet, since PDO does it for us
		//$data = $this->quoteData($data);

		$sql = 'insert into ' . $table . ' (' . implode(',', array_keys($data)) . ') values(' . implode(',', $this->placeHolders($data)) . ')';

		$this->beginTransaction();	
		if($this->execute($sql, $data, false)) { // execute, ignore (don't use) cache
			$id = $this->_lastID($table);
			$this->commitTransaction();
			return $id;
		} else {
			$this->rollbackTransaction();
			return false;
		}
	}

	function build_query_conditions(&$args, &$parameters){
		$sql='';
		$count = 0;
		
		//where
		if(is_string($args->where)){
			$sql.=' where '.$args->where;
		} elseif(is_array($args->where)){
			for($i=1;isset($args->where[$i]);$i++){
				$args->where[0]=Text::str_replace_count('?',':c:'.$count,$args->where[0],1);
				$parameters['c:'.$count]=$args->where[$i];
				$count++;
			}
			$sql.=' where '.$args->where[0];
		}
		
		//group
		if(is_string($args->group)){
			$sql.=' group by '.$args->group;
		} elseif(is_array($args->group)){
			for($i=1;isset($args->group[$i]);$i++){
				$args->group[0]=Text::str_replace_count('?',':c:'.$count,$args->group[0],1);
				$parameters['c:'.$count]=$args->group[$i];
				$count++;
			}
			$sql.=' group by '.$args->group[0];
		}
		
		//order
		if(is_string($args->order)){
			$sql.=' order by '.$args->order;
		} elseif(is_array($args->order)){
			for($i=1;isset($args->order[$i]);$i++){
				$args->order[0]=Text::str_replace_count('?',':c:'.$count,$args->order[0],1);
				$parameters['c:'.$count]=$args->order[$i];
				$count++;
			}
			$sql.=' order by '.$args->order[0];
		}
		
		//limit
		if(is_string($args->limit)){
			$sql.=' limit '.$args->limit;
		} elseif(is_array($args->limit)){
			for($i=1;isset($args->limit[$i]);$i++){
				$args->limit[0]=Text::str_replace_count('?',':c:'.$count,$args->limit[0],1);
				$parameters['c:'.$count]=$args->limit[$i];
				$count++;
			}
			$sql.=' limit '.$args->limit[0];
		}
		
		return $sql;
	}
	
	function find($table){
		$args=Utils::combine_args(func_get_args(), 1, array('what'=>null, 'where'=>null, 'group'=>null, 'order'=>null, 'limit'=>null));
		
		$sql = 'select ';
		$parameters=array();
		$count=0;
		
		//what
		if(is_string($args->what)){
			$sql.=$args->what;
		} elseif(is_array($args->what)){
			for($i=1;isset($args->what[$i]);$i++){
				$args->what[0]=Text::str_replace_count('?',':w:'.$count,$args->what[0],1);
				$parameters['w:'.$count]=$args->what[$i];
				$count++;
			}
			$sql.=$args->what[0];
		} else {
			$sql.='*';
		}
		
		$sql .= ' from '.$table.' ' ;
		
		$sql .= self::build_query_conditions($args, $params);
		
		$parameters = array_merge($parameters, $params);
		
		return self::fetch($sql, $parameters);
	}
	
	/*
	 * Passed an array, table name, where clause, and placeholder parameters, it attempts to update a record.
	 * Returns the number of affected rows
	 * */
	function update($data, $table) {
		
		
		$args=Utils::combine_args(func_get_args(), 2, array('where'=>null, 'group'=>null, 'order'=>null, 'limit'=>null));
		
		// the following block swaps the parameters if they were given in the wrong order.
		// it allows the method to work for those that would rather it (or expect it to)
		// follow closer with SQL convention:
		// update the TABLE with this DATA
		if(is_string($data) && is_array($table)) {
			$tmp = $data;
			$data = $table;
			$table = $tmp;
			trigger_error('Database - The first two parameters passed to update() were in reverse order, but it has been allowed', E_USER_NOTICE);
		}
		// filter invalid fields
		$data = $this->filterFields($data, $table);
		
		
		// need field name and placeholder value
		// but how merge these field placeholders with actual $parameters array for the where clause
		$sql = 'update ' . $table . ' set ';
		foreach($data as $key => $value) {
			$sql .= $key . '=:' . $key . ',';
		}
		$sql = substr($sql, 0, -1); // strip off last comma
		
		$params=array();
		$sql .= self::build_query_conditions($args, $params);
		$data = array_merge($data, $params);

		$this->execute($sql, $data, false); // execute, ignore (don't use) cache
		
		return $this->_affectedRows();
	}

	function delete($table) {
		
		$args=Utils::combine_args(func_get_args(), 1, array('where'=>null, 'group'=>null, 'order'=>null, 'limit'=>null));
		
		$sql = 'delete from ' . $table;
		
		$sql .= self::build_query_conditions($args, $parameters);
		
		$this->execute($sql, $parameters, false); // execute, ignore (don't use) cache
		return $this->_affectedRows();
	}

	/*
	 * Fetches all of the rows (associatively) from the last performed query.
	 * Most other retrieval functions build off this
	 * */
	function fetchAll($sql, $parameters = array()) {
		//$sql = $this->transformPlaceholders(func_get_args());
		$this->execute($sql, $parameters, $this->cacheQueries);
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
	function fetch($sql, $parameters = array()) {
		$this->execute($sql, $parameters, $this->cacheQueries);
		return $this->_fetch();
	}

	/*
	 * Like fetch(), accepts any number of arguments
	 * The first argument is an sprintf-ready query stringTypes
	 * */
	function fetchRow($sql = null, $parameters = array()) {
		if($sql != null)
			$this->execute($sql, $parameters, $this->cacheQueries);
		if($this->result)
			return $this->_fetchRow();
		return null;
	}

	/*
	 * Fetches the first call from the first row returned by the query
	 * */
	function fetchCell($sql, $parameters = array()) {
		if($this->execute($sql, $parameters, $this->cacheQueries)) {
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
		if($this->execute($sql, $parameters, $this->cacheQueries)) {
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
		if($this->execute($sql, $parameters, $this->cacheQueries)) {
			$data = array();
			foreach($this->_fetchAll() as $row) {
				$key = array_shift($row);
				if(sizeof($row) == 1) { // if there were only 2 fields in the result
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

	/*
	 * Return query and other debugging data if error_reporting to right settings
	 * */
	private function debugging() {
		if(in_array(error_reporting(), array(E_ALL))) {
			return $this->query;
		}
	}

	/*
	 * This combines a query and parameter array into a final query string for execution
	 * PDO drivers don't need to use this
	 */
	protected function makeQuery($sql, $parameters) {
		$parts = explode('?', $sql);
		$query = array_shift($parts); // put on first part
	
		$parameters = $this->prepareData($parameters);
		$newParams = array();
		// replace question marks first
		foreach($parameters as $key => $value) {
			if(is_numeric($key)) {
				$query .= $value . array_shift($parts);
				//$newParams[ $key ] = $value;
			} else {
				$newParams[ ':' . $key ] = $value;
			}
		}
		// now replace name place-holders
		// replace place-holders with quoted, escaped values
		/*
		var_dump($query);
		var_dump($newParams);exit;
		*/

		// sort newParams in reverse to stop substring squashing
		krsort($newParams);
		$query = str_replace( array_keys($newParams), $newParams, $query);
		//die($query);
		return $query;
	}

	/*
	 * Used by insert() and update() to filter invalid fields from a data array
	 * */
	private function filterFields($data, $table) {
		$this->buildSchema(); // builds if not previously built
		$fields = $this->schema[ $table ]['fields'];
		foreach($data as $field => $value) {
			if(!array_key_exists($field, $fields))
				unset($data[ $field ]);
		}
		return $data;
	}
	
	/*
	 * This should be protected and overloadable by driver classes
	 */
	private function prepareData($data) {
		$values = array();

		foreach($data as $key=>$value) {
			$escape = true;
			// don't quote or esc
			if(substr($key,-1) == '=') {
				$escape = false;
				$key = substr($key, 0, strlen($key)-1);
			}
			//if(!in_array($key, $columns)) // skip invalid fields
			//	continue;
			if($escape){
				if(get_magic_quotes_gpc()){
					$value=stripslashes($value);
				}
				$values[$key] = "'" . $this->_escapeString($value) . "'";
			} else
				$values[$key] = $value;
		}
		return $values;
	}
	
	/*
	 * Given a data array, this returns an array of placeholders
	 * These may be question marks, or ":email" type
	 */
	private function placeHolders($values) {
		$data = array();
		foreach($values as $key => $value) {
			if(is_numeric($key))
				$data[] = '?';
			else
				$data[] = ':' . $key;
		}
		return $data;
	}
	
	// SCHEMA QUERYING METHODS

	function getTables() {
		$tables = array();
		foreach($this->_tables() as $row) {
			$tables[] = array_shift($row);
		}
		return $tables;
	}

	/*
	 * Returns an array, indexed by field name with values of true or false.
	 * True means the field should be quoted
	 * */
	private function getTableInfo($table) {
		$rows = $this->_schema($table);
		if($rows) {
			$fields = array();
			foreach($rows as $row) {
				$type = strtolower(preg_replace('/\(.*\)/', '', $row[ $this->schemaTypeField ])); // remove size specifier
				$name = $row[ $this->schemaNameField ];
				if($row[ $this->schemaPrimaryKeyField ]) {
					$this->primaryKeys[ $table ] = $name;
				}
				$fields[$name] = $type;
			}
			//$this->fieldsToQuote[$table] = $fieldsToQuote;
			$this->fieldNames[$table] = array_keys($fields);
			$this->fieldTypes[$table] = $fields;
		} else
			die('Database - Table "' . $table . '" does not exist');
	}
	
	/*
	 * Would really like to build the entire schema at once and cache it
	 * rather than doing table-by-table
	 */
	function buildSchema() {
		if($this->schema != null)
			return;
		$schema = $this->schema;
		foreach($this->_tables() as $row) {
			$schema[ array_shift($row) ] = array(
				'fields' => array(),
				'keys' => array(),
				'foreignKeys' => array(),
				'primaryKey' => null
			);
		}

		foreach($schema as $table => $other) {
			$fields = $this->_fields($table);
			$schema[ $table ]['fields'] = $fields;
			foreach($fields as $name => $field) {
				if($field['primaryKey'])
					$schema[ $table ]['primaryKey'] = $name;
			}
			$schema[ $table ]['foreignKeys'] = $this->_foreignKeys($table);
		}
		
		$this->schema = $schema;
	}
	
	function cacheSchemaToFile($file) {
		if($this->schema == null) {
 			if(file_exists($file)) {
 				require($file);
			} else {
				$this->buildSchema();

				$data = '<?php $this->schema = ' . var_export($this->schema, true) . '; ?>';
				file_put_contents($file, $data);
			}
		}
	}
}

/*
 * To create a new driver, implement the following:
 * protected _open(...)
 * protected _query($sql, $parameters)
 * protected _escapeString
 * protected _error
 * protected _affectedRows
 * protected _numberRows
 * protected _fetch
 * protected _fetchAll
 * protected _fetchRow
 * protected _lastID
 * protected _schema
 * public beginTransaction
 * public commitTransaction
 * public rollbackTransaction
 * public close
 * */

class Database_mssql extends Database {
	function beginTransaction() {
		//mssql_query('begin', $this->connection);
	}

	function commitTransaction() {
		//mssql_query('commit', $this->connection);
	}

	function close() {
		mssql_close($this->connection);
	}

	function rollbackTransaction() {
		//mssql_query('rollback', $this->connection);
	}

	protected function _affectedRows() {
		return mssql_rows_affected($this->connection);
	}

	protected function _error() {
		return mssql_get_last_message();
	}

	protected function _escapeString($string) {
		$s = stripslashes($string);
		$s = str_replace( array("'", "\0"), array("''", '[NULL]'), $s);
		return $s;
	}

	protected function _fetch() {
		// use mysql_data_seek to get to row index
		return $this->_fetchAll();
	}

	protected function _fetchAll() {
		$data = array();
		while($row = mssql_fetch_assoc($this->result)) {
			$data[] = $row;
		}
		//mssql_free_result($this->result);
		// rewind?
		return $data;
	}

	protected function _fetchRow() {
		return mssql_fetch_assoc($this->result);
	}

	protected function _fields($table) {
		$this->execute('select COLUMN_NAME,DATA_TYPE from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME=?', array($table), false);
		return $this->_fetchAll();
	}

	protected function _foreignKeys($table) {
	}

	protected function _lastID() {
		return $this->fetchCell('select scope_identity()');
	}

	protected function _open($database, $user, $password, $host) {
		$this->connection = mssql_connect($host, $user, $password);
		if($this->connection)
			mssql_select_db($database, $this->connection);
		//$this->buildSchema();
		return $this->connection;
	}

	protected function _numberRows() {
		return mssql_num_rows($this->result);
	}

	protected function _primaryKey($table) {
	}

	protected function _query($sql) {
		return mssql_query($sql, $this->connection);
	}

	protected function _rewind($result) {
	}
	
	protected function _tables() {
	}
} // mssql

class Database_mysql extends Database {
	private $database;

	function beginTransaction() {
		mysql_query('begin', $this->connection);
	}

	function close() {
		mysql_close($this->connection);
	}

	function commitTransaction() {
		mysql_query('commit', $this->connection);
	}

	function rollbackTransaction() {
		mysql_query('rollback', $this->connection);
	}

	protected function _affectedRows() {
		return mysql_affected_rows($this->connection);
	}

	protected function _error() {
		return mysql_error($this->connection);
	}

	protected function _escapeString($string) {
		return mysql_real_escape_string($string);
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

	protected function _fields($table) {
		$fields = array();
		$this->execute('describe ' . $table, array(), false);
		foreach($this->_fetchAll() as $row) {
			$type = strtolower(preg_replace('/\(.*\)/', '', $row['Type'])); // remove size specifier
			$name = $row['Field'];
			$fields[ $name ] = array('type' => $type, 'primaryKey' => ($row['Key'] == 'PRI'));
		}
		return $fields;
	}

	protected function _foreignKeys($table) {
		$version = mysql_get_server_info($this->connection);
		$parts = explode('-', $version); // strip off non-numeric portion
		$parts = explode('.', $parts[0]); // split numeric parts
		if(false && ($parts[0] == '5' && ($parts[1] > '1' || ($parts[1] == '1' && $parts[2] >= '10')))) { // we can only fetch foreign-key info in 5.1.10+
			$q = 'select CONSTRAINT_SCHEMA as foreignTable,CONSTRAINT_NAME as localField from INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS where TABLE_NAME=?';
			$this->_query($q, array($table));
			$rows = $this->_fetchKeyValue();
			return $rows;
		} else {
			return array();
		}
	}

	protected function _lastID() {
		return mysql_insert_id($this->connection);
	}

	protected function _numberRows() {
		return mysql_num_rows($this->result);
	}
	
	// user, password, database, host
	protected function _open($database, $user, $password, $host) {
		$this->database = $database;
		$this->connection = mysql_connect($host, $user, $password);
		if($this->connection)
			mysql_select_db($database, $this->connection);
		//$this->buildSchema();
		return $this->connection;
	}

	protected function _query($sql) {
		return mysql_query($sql, $this->connection);
	}

	protected function _rewind($result) {
	}

	protected function _tables() {
		// this should probably use 'show tables' if the mysql version is older and doesn't support the information_schema
		if(!$this->execute("select TABLE_NAME from information_schema.TABLES where TABLE_SCHEMA=? order by TABLE_NAME", array($this->database), false))
			die('Failed to get tables');
		return $this->_fetchAll();
	}
} // mysql

class Database_sqlite extends Database {
	function beginTransaction() {
		sqlite_query($this->connection, 'begin transaction');
	}

	function close() {
		sqlite_close($this->connection);
	}

	function commitTransaction() {
		sqlite_query($this->connection, 'commit transaction');
	}

	function rollbackTransaction() {
		sqlite_query($this->connection, 'rollback transaction');
	}

	protected function _affectedRows() {
		return sqlite_changes($this->connection);
	}

	protected function _error() {
		return sqlite_error_string(sqlite_last_error($this->connection));
	}

	protected function _escapeString($string) {
		return sqlite_escape_string($string);
	}

	protected function _fetch() {
		return new Database_sqlite_result($this->result);
	}

	protected function _fetchAll() {
		$rows = sqlite_fetch_all($this->result, SQLITE_ASSOC);
		// free result?
		// rewind?
		return $rows;
	}

	// when passed result
	// returns next row
	protected function _fetchRow() {
		return sqlite_fetch_array($this->result, SQLITE_ASSOC);
	}

	protected function _fields($table) {
		$fields = array();
		foreach($this->fetchAll('pragma table_info(' . $table. ')') as $row) {
			$type = strtolower(preg_replace('/\(.*\)/', '', $row['type'])); // remove size specifier
			$name = $row['name'];
			$fields[ $name ] = array('type' => $type, 'primaryKey' => ($row['pk'] == '1'));
		}
		return $fields;
	}

	protected function _foreignKeys($table) {
		$keys = array();
		$this->execute('pragma foreign_key_list(' . $table . ')', array(), false);
		foreach($this->_fetchAll() as $row) {
			$keys[ $row['from'] ] = array('table' => $row['table'], 'field' => $row['to']);
		}
		return $keys;
	}

	protected function _lastID() {
		return sqlite_last_insert_rowid($this->connection);
	}

	protected function _numberRows() {
		return sqlite_num_rows($this->result);
	}

	protected function _open($database) {
		$this->connection = sqlite_open($database);
		//$this->buildSchema();
		return $this->connection;
	}

	protected function _query($sql) {
		//var_dump($parameters);exit;
		/*
		$sql = $this->makeQuery($sql, $parameters);
		if(array_key_exists($sql, $this->cache))
			return $this->cache[ $sql ];
		*/
		return sqlite_query($this->connection, $sql);
	}

	protected function _rewind($result) {
		sqlite_rewind($result);
	}

	protected function _tables() {
		if(!$this->execute("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name", array(), false))
			die('Failed to get tables');
		return $this->_fetchAll();
	}
} // sqlite

?>
