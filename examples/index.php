<?php
/**
 * NaturePhp v0.3.4 example application
 * This is a simple example to show some of the possibilities of NaturePhp
 *
 * please remember NaturePhp is just an autoloaded php library system - you don't really 
 * have to use most of these examples/functionalities as they are library specific
 * 
 * This is just for the sake of exemplifying how the system works as well as some included libraries
 */


// Application configuration / startup Nphp
require('includes/init.php');

//layout variables (it's just the way i usually do it...)
$PAGE['id']				= 'home';
$PAGE['title'] 			= 'NaturePhp Example - Home';
$PAGE['active_menu'] 	= 'home';

//application header
include('includes/header.php');




//this page content
Log::add('Index content starting.');
?>
<div id="content">
	<h2>NaturePhp v0.3.4 example application</h2>
	<br /><br />
	<h4>Readme File:</h4>
	<br /><br />
	<p style="font-size:0.8em;padding:20px;border:1px #999 dashed;">
		<?php 
		//file location
		$readme_file = Path::to('readme.txt', __FILE__);
		
		//gets content
		Log::add('Getting readme file contents.');
		$readme_content = file_get_contents($readme_file);
		
		//prints formated to html
		print Text::to_html($readme_content); 
		?>
	</p>
</div>
<?php
Log::add('Index content ended.');


//the footer
include('includes/footer.php');
?>