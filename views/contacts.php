<a href="<?php Routes::put('home', array('lang'=>null)); ?>">Home</a> | <a href="<?php Routes::put('view-post', array('lang'=>null, 'alias'=>1)); ?>">View post 1</a>
<br /><br />
<?php

var_dump(Aura::get('SMTP_OPTIONS'));
print "<br /><br />";

$res=TestDb::fetchRow("SELECT * from test where id=?", array(1));

var_dump($res);
?>