<?php
//"MSSQL to MySQL" and "MySQL to MSSQL" SQLDump
//useful to export data back and forth between different sql servers
//v0.1 - by NaturePhp.org team - MIT License
//
//TO-DOS:
//Other formats - PostgreSQL, SQLlite, ?, etc
//Migrations system?


//uncomment the following line to make it work
//die();	//This file should be protected by default for security reasons

include('includes/nphp/init.php');

Log::init(true);


//recolhe/organiza dados
$D=array_merge(array(
	'db_type' => 'mssql', 
	'host' => 'localhost',
	'database' => '',
	'username' => '',
	'password' => '',
	'format' => 'mysql',
	'extra' => 'truncate',
	'mode' => 'html',
	'submit' => false
	), $_POST);


if($D['submit']):

	//data source
	switch($D['db_type']){
		case 'mssql':
			//src database connection
			$DB_OPTIONS=array(
				'type' => 'odbc',
			    'dsn'=>'DRIVER={SQL Native Client};Server='.$D['host'].'; Database='.$D['database'].'; Uid='.$D['username'].'; Pwd='.$D['password'],
			    'user' => $D['username'],
			    'password' => $D['password']
				);
			$S['I_RS']=']';	//right special char
			$S['I_LS']='[';	//left special char
			//show tables
			$S['sel_tables']="SELECT name from [".$D['database']."]..sysobjects where xtype = 'U'";
			//describe table
			$S['desc_table']="SELECT
			    column_name as 'Field',
				data_type +
			    COALESCE(
			       '(' + CAST(character_maximum_length AS VARCHAR) + ')',
			    	'(' + CAST(numeric_precision AS VARCHAR) + ')',
			    	''
			    ) as 'Type',
				is_nullable as 'Null',
				column_default as 'Default'
			FROM
			    information_schema.columns
			WHERE
			    table_name = '{table_name}';";
			//get primary key
			$S['sel_primary']="SELECT k.column_name
			FROM information_schema.table_constraints t
			JOIN information_schema.key_column_usage k
			on t.constraint_name=k.constraint_name and t.table_catalog=k.table_catalog and t.table_name=k.table_name
			WHERE t.constraint_type =  'PRIMARY KEY'
			AND t.table_catalog =  '".$D['database']."'
			AND t.table_name =  '{table_name}';";
			//get unique keys
			$S['sel_uniques']="SELECT k.column_name
			FROM information_schema.table_constraints t
			JOIN information_schema.key_column_usage k
			on t.constraint_name=k.constraint_name and t.table_catalog=k.table_catalog and t.table_name=k.table_name
			WHERE t.constraint_type =  'UNIQUE'
			AND t.table_catalog =  '".$D['database']."'
			AND t.table_name =  '{table_name}';";
			//check auto_increment
			$S['check_identity'] = "SELECT 
										CASE 
										WHEN 
											columnproperty(object_id('{table_name}'), '{column_name}','IsIdentity') = 1 
											THEN 1
										ELSE 0
										END";
		break;
		case 'mysql':
			//src database connection
			$DB_OPTIONS=array('database' => $D['database'], 'user' => $D['username'], 'password' => $D['password'], 'host'=>$D['host']);
			$S['I_RS']='`';	//right special char
			$S['I_LS']='`';	//left special char
			//show tables
			$S['sel_tables']="SHOW TABLES";
			//describe table
			$S['desc_table']="DESCRIBE `{table_name}`";
			//get primary key
			$S['sel_primary']="SELECT k.column_name
			FROM information_schema.table_constraints t
			JOIN information_schema.key_column_usage k
			USING ( constraint_name, table_schema, table_name ) 
			WHERE t.constraint_type =  'PRIMARY KEY'
			AND t.table_schema =  '".$D['database']."'
			AND t.table_name =  '{table_name}';";
			//get unique keys
			$S['sel_uniques']="SELECT k.column_name
			FROM information_schema.table_constraints t
			JOIN information_schema.key_column_usage k
			USING ( constraint_name, table_schema, table_name ) 
			WHERE t.constraint_type =  'UNIQUE'
			AND t.table_schema =  '".$D['database']."'
			AND t.table_name =  '{table_name}';";
			//check auto_increment
			$S['check_identity'] = "SELECT AUTO_INCREMENT 
			FROM information_schema.tables
			WHERE table_name =  '{table_name}';";
		break;
	}

	//data output
	switch($D['format']){
		case 'mssql':
			$S['C']='--';	//t-sql comments
			$S['O_RS']=']';	//right special char
			$S['O_LS']='[';	//left special char
			//line endings
			$S['EOL']=';	
';
			$S['QR']="''";	//escape quotes
		break;
		case 'mysql':
			$S['C']='--';	//t-sql comments
			$S['O_RS']='`';	//right special char
			$S['O_LS']='`';	//left special char
			//line endings
			$S['EOL']=';
';
			$S['QR']="\\'";	//escape quotes
		break;
	}
	
	
	
	//database connection
	$DB=Database::open($DB_OPTIONS);
	
	//file download mode
	if($D['mode']=="file"){
		//TO-DO: file download mode
		header("Content-Type: text/x-sql; charset=utf-8");
		header('Content-Disposition: attachment; filename="'.$D['database'].'_'.$D['format'].'.sql"');
		header("Content-Length: " . filesize($file) ."; ");
		sql_dump();
		die();
	}
	
endif;


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<title>dump MSSQL to MySQL and back</title>
		<style type="text/css" media="screen">
			/* for Telmo to pimp */
		</style>
	</head>
	<body>
		<form action="" method="post" accept-charset="utf-8">
			Read from:
			<label for="mssql">MSSQL</label> <input type="radio" name="db_type" <?php print $D['db_type']=="mssql"?'checked="checked"':''; ?> value="mssql" /> &nbsp; | &nbsp; 
			<label for="mysql">MySQL</label> <input type="radio" name="db_type" <?php print $D['db_type']=="mysql"?'checked="checked"':''; ?> value="mysql" /><br />
			
			<label for="host">Host</label><input type="text" name="host" value="<?php print $D['host']; ?>" /><br />
			<label for="database">Database</label><input type="text" name="database" value="<?php print $D['database']; ?>" /><br />
			<label for="username">Username</label><input type="text" name="username" value="<?php print $D['username']; ?>" /><br />
			<label for="password">Password</label><input type="text" name="password" value="<?php print $D['password']; ?>" />
			<br /><br />
			
			
			Export to:
			<label for="mssql">MSSQL</label><input type="radio" name="format" <?php print $D['format']=="mssql"?'checked="checked"':''; ?> value="mssql" /> &nbsp; | &nbsp; 
			<label for="mysql">MySQL</label><input type="radio" name="format" <?php print $D['format']=="mysql"?'checked="checked"':''; ?> value="mysql" />
			<br /><br />
			
			
			<!--destination data control-->
			<input type="radio" name="extra" <?php print $D['extra']=="truncate"?'checked="checked"':''; ?> value="truncate" /> 
			<label for="truncate">Truncate destination data</label><br />
			
			<input type="radio" name="extra" <?php print $D['extra']=="schema"?'checked="checked"':''; ?> value="schema" /> 
			<label for="schema">Replace schema (drop tables)</label><br />
			
			<input type="radio" name="extra" <?php print $D['extra']=="nothing"?'checked="checked"':''; ?> value="nothing" /> 
			<label for="nothing">Data only</label><br />
			<br /><br />
			
			<input type="radio" name="mode" <?php print $D['mode']=="html"?'checked="checked"':''; ?> value="html" /> 
			<label for="html">HTML</label><br />
			
			<input type="radio" name="mode" <?php print $D['mode']=="file"?'checked="checked"':''; ?> value="file" /> 
			<label for="file">.sql file</label><br />
			
			<p><input type="submit" name="submit" value="Continue &rarr;"></p>
		</form>
		<br /><br />
		<?php
		if($D['submit']):
			?>
			<h2>SQL Dump</h2>
			<pre>
<?php sql_dump(); ?>
			</pre>
			<?php
		endif;
		?>
	</body>
</html>
<?php

//sqldump
function sql_dump(){
	global $S, $D, $DB;
	?>
	
<?php print $S['C']; ?>Generated by NaturePhp SQLDumper v0.1
<?php print $S['C']; ?>options: <?php print $D['db_type']; ?> to <?php print $D['format']; ?>: <?php

			switch($D['extra']){
				case "truncate":
					print "Truncate destination";
				break;
				case "schema":
					print "Replace Schema";
				break;
				case "nothing":
					print "Data only";
				break;
			}

			?>

<?php

			//get all tables
			$tables = $DB->fetch("show tables");
			//mssql: select name from <database name>..sysobjects where xtype = 'U'
			//cycle them
			foreach($tables as $table){
				//strip to the actual table name
				$table=array_pop($table);
				//top comment
				?>
<?php print $S['C'].'Table "'.$table.'"'; ?>	
<?php 
				//truncate
				if($D['extra']=="truncate"){
					print 'truncate table '.ef($table).$S['EOL']; 
				} elseif($D['extra']=="schema"){
					//drop old table
					print 'drop table '.ef($table).$S['EOL'];
					
					//describe table
					$DESCRIBED=$DB->fetch(str_replace("{table_name}", ef($table), $S['desc_table']));
					//get primary keys
					$PRIMARY_KEY=$DB->fetchCell(str_replace("{table_name}", ef($table), $S['sel_primary']));
					//check if primary key is auto_increment
					$HAS_IDENTITY=$DB->fetchCell(str_replace("{table_name}", ef($table), str_replace("{column_name}", $PRIMARY_KEY, $S['check_identity'])));
					//get unique keys
					$UNIQUE_KEYS=$DB->fetchCell(str_replace("{table_name}", ef($table), $S['sel_uniques']));
					
					//generate new table schema
					print "CREATE TABLE ".ef($table)."(
";
					$first=true;
					foreach($DESCRIBED as $col){
						if(!$first) print ",
";
						print "	".ef($col['Field'])." ".$col['Type'];
						//set primary key on mssql
						if($col['Field']==$PRIMARY_KEY && $D['format']=="mssql" && $HAS_IDENTITY){
							print " identity(1,1)";
						}
					}
						
					print "
)".$S['EOL'];
					
					//set identity insert for mssql
					if($D['format']=="mssql" && $HAS_IDENTITY) print "IDENTITY_INSERT ON".$S['EOL'];
				}

				//get table contents
				$Contents = $DB->fetch("SELECT * from ".ef($table));
				
				//cycle them
				foreach($Contents as $Content){
					?>
<?php 
					//print intro
					print 'INSERT INTO '.ef($table).' (';
					//print fields, keep values
					$first=true;
					$values="";
					foreach($Content as $key=>$value){
						if(!$first) {
							print ", ";
							$values.=", ";
						} else $first=false;
						print ef($key);	//print o campo
						$values.=ev($value);
					}
					//print values
					print ') VALUES('.$values.')'.$S['EOL']; 
				}
				
				
				//create constraints (primary + unique keys)
				if($D['extra']=="schema"){
					
					//no more insertions on mssql
					if($D['format']=="mssql" && $HAS_IDENTITY){
						print "IDENTITY_INSERT OFF".$S['EOL'];
					}
					
					//add primary key
					print "ALTER TABLE ".ef($table)." ADD PRIMARY KEY (".ef($PRIMARY_KEY).")".$S['EOL'];
					//add auto_increment on mysql
					if($D['format']=="mysql" && $HAS_IDENTITY){
						print "ALTER TABLE ".ef($table)." MODIFY COLUMN ".ef($PRIMARY_KEY)." INT NOT NULL AUTO_INCREMENT".$S['EOL'];
					}
					//add unique
					print "ALTER TABLE ".ef($table)." ADD UNIQUE (".ef($PRIMARY_KEY).")".$S['EOL'];
				}
				?>



<?php
			}
}
//auto escape sql field
function ev($v){
	global $DB;
	return $DB->_escapeField($v);
}
//auto escape sql data
function ev($v){
	global $DB;
	return $DB->escapeValue($v);
}
?>