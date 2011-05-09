**
* NaturePhp v0.4.1 example application (and tests playground)
*
* 	This is a simple application example to show some of the possibilities of NaturePhp
*
* 	Please keep in mind NaturePhp is just an autoloaded php library system - you don't really 
* 	have to use most of these examples/functionalities as they are library specific
* 
* 	This is just for the sake of exemplifying how the system works as well as some included libraries
*
* 	Please note the application structure and variables is just what i regularly use
* 	you don't have to follow any of these standards - you can just use your own.
*
**

Just copy the examples/ and nphp/ folder to any folder on your server and the basic should be working


To get uploads working:

1. set read/write permissions on exmples/uploads/ (recursively)


To put the application actually working with the database:

1.  Create a new database named "nphp_example" at you mysql host

2.  Import resources/sql/nphp_example.sql into the new database

3.  Edit the includes/init.php file and setup the connection details at Database::open(...)

4.  Edit the Mem::set('use_db', false, 'example'); to Mem::set('use_db', true, 'example');