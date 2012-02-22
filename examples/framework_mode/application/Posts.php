<?php
class Posts
{
	
	function __construct()
	{
		?>
			<a href="<?php Routes::put('home', array('lang'=>null)); ?>">Home</a> | <a href="<?php Routes::put('contacts', array('lang'=>null)); ?>">Contacts</a>
			<br /><br />
		<?php
		print "Started Posts.<br /><br />";
	}
	
	function view($alias){
		print "Viewing post \"$alias\"<br /><br />";
	}
	
	function __destruct(){
		print "Ended Posts.";
	}
}

?>