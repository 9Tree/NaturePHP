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
	//$options['use_smtp'] = true;
	//$options['smtp_server'] = 'smtp.mydomain.com';
	//$options['smtp_username'] = 'info@mydomain.com';
	//$options['smtp_password'] = 'mypassword';
	
	$subject ="Test email from NaturePHP";
	
	if(!isset($_POST['html'])):
		$body = "Hello,    
i'm only an email example sent through NaturePHP's Mail class.


Thanks.";
	
	else:
	ob_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>[NaturePHP] Test html email</title>
	
</head>

<body style="background:#f7f7f7; padding:20px;">

	<div style="font-size:16px; padding:15px; background:white; font-family: Helvetica, 'Lucida Grande', Tahoma, Verdana, Arial;">
		
		<span>NaturePHP</span>
		
		<br /><br />
		
		<span style="font-size:26px; color:#333; line-height:1.4em; font-weight:bold;">This is only an email example sent through NaturePHP's Mail class.</span>
		
		<br /><br />
		
		Hi there,
		
		<br /><br />
		
		apparently it works.
		
		<div style="padding:20px; background:#ecf3fe; line-height:1.4em; margin:25px 0; border:1px #91c7e1 solid;">
			
			<span style="font-size:18px; font-weight:bold;">A nice box</span>
			
			<br />
			
			With some example content.
			
			<br /><br />
			
			<a href="http://naturephp.org" style="color:#479bce;">http://naturephp.org</a>
			
		</div>
		
		<div style="font-size:14px;">
			<strong>Have a question?</strong> - Contact us at <a href="mailto:someone@9tree.net">someone@9tree.net</a>
		</div>
		
	</div>

</body>
</html>	
<?php
	$body = ob_get_clean();
	endif;
	
	if(isset($_POST['attachment'])){
		$options['attachments'] = array("readme.txt", "images/Samurai.jpg");
	}
	
	
	
	if(isset($_POST['html'])){
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