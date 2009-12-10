<?php
// Application configuration / startup Nphp
require('includes/init.php');

//creates image instance from file
$image=Image::from_file(Path::to("images/Samurai.jpg", __FILE__));

//creates children images
$image->children(array(
	'scale'			=>	'50%', 
	'specific_scale'=>	'75x50%', 
	'fixed_width'	=>	"100", 
	'fixed_height'	=>	"x100", 
	"fit_within"	=>	"100x200>",
	"fit_outside"	=>	"100x200^",
	"forced"		=>	"200x200!",
	'thumb'			=>	"100x100#"));

//saves all children
$filename = $image->save_all(array('folder' => Path::to("uploads/images", __FILE__)));

var_dump($filename);
?>