<?php
// Application configuration / startup Nphp
require('includes/init.php');

$image=&Image::from_file(Path::to("images/Samurai.jpg", __FILE__));

$image->children(array('thumb'=>"100x100#", "preview"=>"300x300|"));

$image->save_all(array('folder' => Path::to("files/images", __FILE__)));
?>