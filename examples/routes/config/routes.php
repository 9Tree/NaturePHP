<?php
// Routes configuration
// Static rules
// Routes::static($path, $page_name);
// note that you can use Path::url_to to specify the url if you are in an include located on a folder

#home page	( http://domain.com/ )
Routes::simple(Path::url_to('../', __FILE__), 'home');	
#Company info page		( http://domain.com/Company/About )
Routes::simple(Path::url_to('../Company/About', __FILE__), 'About_the_Company');	

// Dynamic rules
// Routes::dynamic($path, $page_name);

#users page
Routes::dynamic(Path::url_to('../:username', __FILE__), 'user_page');	
#users photos main page
Routes::dynamic(Path::url_to('../:username/photos', __FILE__), 'user_photos', true);
#a photo	
Routes::dynamic(Path::url_to('../:username/photos/:id', __FILE__), 'photo_page');
#the archive by year	
Routes::dynamic(Path::url_to('../archive/:year', __FILE__), 'yearly_archive');
#users photos main page
Routes::dynamic(Path::url_to('../archive/:username/photos', __FILE__), 'user_archive', true);
?>