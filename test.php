<?php

//if you use init.php instead of routed-init.php
//you will still have all the functionality and application environment available
//but routing will not be "activated" thus no routing rule will try to be matched
include "nphp/init.php";
?>
<a href="<?php Routes::put('home', array('lang'=>null)); ?>">Home</a> | <a href="<?php Routes::put('view-post', array('lang'=>null, 'alias'=>1)); ?>">View post 1</a> | <a href="<?php Routes::put('contacts', array('lang'=>null)); ?>">Contacts</a>