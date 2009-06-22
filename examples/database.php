<?php
// Application configuration / startup Nphp
require('includes/init.php');

//layout variables (it's just the way i usually do it...)
$PAGE['id']				= 'database';
$PAGE['title'] 			= 'NaturePhp Example - Database';
$PAGE['active_menu'] 	= 'database';

//application header
include('includes/header.php');



//this page content
Log::add('Index content starting.');
?>
<div id="content">
	<h2>NaturePhp v0.3.5 Database example</h2>
	<br /><br />
	<h4>Listing countries:</h4>
	<br /><br />
	<p style="font-size:0.8em;padding:20px;border:1px #999 dashed;">
		<?php
		//by default it automatically uses the default connection 
		Database::test();
		?>
	</p>
</div>
<?php
Log::add('Index content ended.');


//the footer
include('includes/footer.php');
?>