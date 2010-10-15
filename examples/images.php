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
	'thumb'			=>	"100x100#",
	'watermarked'	=>	"300x300"
	));

//set watermark
$image->set_watermark(Path::to("images/watermark_1.png", __FILE__), array('watermarked'));

//saves all children
$filename = $image->save_children(array('folder' => Path::to("uploads/images", __FILE__)));

?>
<img src="<?php Path::put('uploads/images/Samurai.jpg', __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/scale/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/specific_scale/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/fixed_width/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/fixed_height/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/fit_within/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/fit_outside/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/forced/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/thumb/'.$filename, __FILE__); ?>" />
<img src="<?php Path::put('uploads/images/watermarked/'.$filename, __FILE__); ?>" />