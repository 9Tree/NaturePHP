<?php

// Application configuration / startup Nphp
require('includes/init.php');

//layout variables (it's just the way i usually do it...)
$PAGE['id']				= 'home';
$PAGE['title'] 			= 'NaturePhp Example - Home';
$PAGE['active_menu'] 	= 'home';


//static functionalities
if(isset($_POST['send_email'])){
	
	$to = $_POST['email'];
	$options = array();
	$options['from'] = $_POST['from_email'];
	//$options['cc'] = "carlos.ouro@9tree.net";
	
	$subject ="Test email from NaturePHP";
	
	$body = "Hello,
i'm only an email example sent through NaturePHP's Mail class.


Thanks.";
	
	if(isset($_POST['attachment'])){
		$options['attachments'] = array("readme.txt", "images/Samurai.jpg");
	}
	
	
	
	if(isset($_POST['html'])){
		$body = Text::to_html($body);	// Text::to_html converts the plain text string to html format
										// not necessary if $body was already some html code
		Mail::send_html($to, $subject, $body, $options);
	} else {
		Mail::send($to, $subject, $body, $options);
	}
}


//application header
include('includes/header.php');

//this page content
Log::add('Content starting.');
?>
<div id="content">
	<h2>NaturePhp v0.3.5 Mail example</h2>
	<br /><br />
	<form action="" method="post" accept-charset="utf-8">
		<label for="from_email">From:</label><input type="text" name="from_email" value="" id="from_email" />
		<label for="email">To:</label><input type="text" name="email" value="" id="email" />
		<br /><br />
		<label for="html">HTML:</label><input type="checkbox" name="html" id="html" />
		<br />
		<label for="attachment">Attachment:</label><input type="checkbox" name="attachment" id="attachment" />
		

		<p><input type="submit" name="send_email" value="Send test email &rarr;"></p>
	</form>
</div>


<?php
Log::add('Content ended.');

//the footer
include('includes/footer.php');
?>