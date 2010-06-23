<?php
// Application configuration / startup Nphp
require('../includes/init.php');

// Routes configuration
require('config/routes.php');


// Routes::init() uses Request_URI to setup environment variales, paths, etc
// Automatically transforms Path::setBase 
// - so all Path::put/url_to calls from here on will have their paths corrected according to the current Request_URI

Routes::init();
// note: all calls to Routes::static() and Routes::dynamic() will be ignored after this

//page contents
$PAGE['id']				= Routes::$current;
$PAGE['title'] 			= 'NaturePhp Example - Routes App';
$PAGE['active_menu'] 	= 'routes';

include("../includes/header.php");

# Routes put - example by name
?>
<a href="<?php Routes::put('home'); ?>">
	Go to home
</a> |
<a href="<?php Routes::put('About_the_Company', array('hi'=>1)); /*on a static page, the array is the $_GET*/ ?>">
	Company
</a> | 
<a href="<?php Routes::put('user_photos', array('username'=>'John')); /*on a dynamic page, the 1st array are the vars*/ ?>">
	John's photos
</a> |
<a href="<?php Routes::put('photo_page', array('username'=>'John', 'id'=>1), array('hi'=>1)); /*and the 2nd array is the $_GET*/  ?>">
	Photo #1
</a> |
<a href="<?php print Routes::url_to('user_page', array('username'=>'John')); /*same as Routes::put() but returns string instead of printing*/?>">
	John's info
</a> |
<a href="<?php print Routes::url_to('yearly_archive', array('year'=>2009)); ?>">
	Archive 2009
</a>
<?php

print "<h3>Page Name: ".Routes::$current."</h3>";


?>
<br /><br />

<?php

switch(Routes::$current){
	case "home":
	print "This is a Routes example.";
	break;
	case "About_the_Company":
	print "This is a static page example.";
	break;
	case "user_photos":
	print Routes::$_PATH['username']."'s photos";
	break;
	case "photo_page":
	print Routes::$_PATH['username']."'s photo #".Routes::$_PATH['id'];
	break;
	case "yearly_archive":
	print Routes::$_PATH['year']."'s archive";
	break;
}

?>
<br /><br />	
Routes::$_PATH contents:<br />
<?php print Text::to_html(Utils::s_var_dump(Routes::$_PATH)); ?>
<br /><br />	
$_GET contents:<br />
<?php print Text::to_html(Utils::s_var_dump($_GET)); ?>

<?php

include("../includes/footer.php");
?>