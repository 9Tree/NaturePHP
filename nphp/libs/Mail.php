<?php
#/*
#* 9Tree Mail Class - v0.1
#* Mail funcionalities
#*/

class Mail{
	
	//default send mail
	static function send($to, $subject, $body){
		
		# vars
		$args=Utils::combine_args(func_get_args(), 3, array(
						'cc' => null,
						'bcc' => null,
						'from' => null,
						'reply-to' => null,
						'html' => null,
						'attachments' => array(),
						'use_smtp' => false
						));
		
		//debug notice information
		$notice_info = $to.' (text'.($args['html']?'/html':'').($args['attachments']?'/attachments':'').')';
		
		
		//start composing email
		# create a boundary string. It must be unique
		# so we use the MD5 algorithm to generate a random hash
		$random_hash = md5(date('r', time()));
		
		$headers = "";
		
		# from
		if($args['from']) 		$headers .= "From: ".$args['from']."\r\n";
		# to
		$headers .= "To: ".$to."\r\n";
		# reply-to
		if($args['reply-to']) 	$headers .= "Reply-To: ".$args['reply-to']."\r\n";
		# cc
		if($args['cc']) 		$headers .= "cc: ".$args['cc']."\r\n";
		# bcc
		if($args['bcc'] && !$args['use_smtp']) 		$headers .= "bcc: ".$args['bcc']."\r\n";
		# subject
		$headers .= "Subject: ".$subject."\r\n";
		
		# add boundary string and mime type specification
		if($args['attachments']){
			$headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-$random_hash\"\r\n\r\n";
			$headers .= "--PHP-mixed-$random_hash\r\n";
		}
		
		# html mode - write plain text only as alternative
		if($args['html'] || $args['attachments']){
			//composed email header
			$headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-$random_hash\"\r\n\r\n";
			
			//plain text version
			$headers .= "--PHP-alt-$random_hash\r\n";		
			$headers .= "Content-Type: text/plain; charset = \"utf-8\"\r\n";
			$headers .= "Content-Transfer-Encoding: 8bit\r\n";
			$headers .= "\r\n".str_replace("\n", "\r\n", str_replace("\r\n", "\n", $body))."\r\n\r\n\r\n";
			
			//html content
			if($args['html']){
				$headers .= "--PHP-alt-$random_hash\r\n";
				$headers .= "Content-Type: text/html; charset = \"utf-8\"\r\n";
				$headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
				$headers .= "\r\n".$args['html']."\r\n\r\n";
			}
			
			$headers .= "--PHP-alt-$random_hash--\r\n\r\n";
			
			
		} else {
			$headers .= "Content-Type: text/plain; charset = \"utf-8\"\r\n";
			$headers .= "Content-Transfer-Encoding: 8bit\r\n";
			$headers .= "\r\n".$body."\r\n\r\n\r\n";
		}

		#attachments
		if($args['attachments']){
			
			$finfo_avail = function_exists('finfo_open');
			if($finfo_avail) $finfo = finfo_open(FILEINFO_MIME_TYPE);
			$first = true;
			foreach($args['attachments'] as $file){
				
				//file not found protection
				if(!is_readable($file)){
					trigger_error('<strong>Mail</strong> :: Attachment not found "'.$file.'"', E_USER_WARNING);
					trigger_error("<strong>Mail</strong> :: Unable to send email to $notice_info", E_USER_WARNING);
					return false;	//email cannot be properly sent - better not to send at all
				}
					
				if(!$first){
					//close previous one
					$headers .= "\r\n";
				} else $first = false;
				
				//read the atachment file contents into a string,
				//encode it with MIME base64,
				//and split it into smaller chunks
				$filename=basename($file);
				
				if($finfo_avail) $ftype = finfo_file($finfo, $file);
				else $ftype = "unknown";
				
				$attachment = chunk_split(base64_encode(file_get_contents($file)));
				
				
				$headers .= "--PHP-mixed-$random_hash\r\n"; 
				$headers .= "Content-Type: $ftype; name=\"$filename\"\r\n";
				$headers .= "Content-Transfer-Encoding: base64\r\n"; 
				$headers .= "Content-Disposition: attachment\r\n";

				$headers .= "\r\n".$attachment."\r\n\r\n"; 
				
			}
			
			if(!$first){
				//close the last one
				$headers .= "--PHP-mixed-$random_hash--\r\n\r\n";
			}
		}
		
		
		//try sending the composed email
		$check = null;
		if($args['use_smtp']) {
			$to_array = explode(";", str_replace(",", ";", $to));
			$cc_array = explode(";", str_replace(",", ";", $args['cc']));
			$bcc_array = explode(";", str_replace(",", ";", $args['bcc']));
			$to_array = array_merge($to_array, $cc_array, $bcc_array);
			
			$check = self::smtp_send($to_array, $headers, $args);
		} else {
			$check = mail( $to, $subject, "", $headers );
		}
		
		
		if(!$check){
			trigger_error("<strong>Mail</strong> :: Unable to send email to $notice_info", E_USER_WARNING);
			return false;
		} else {
			trigger_error("<strong>Mail</strong> :: Email sent to $notice_info", E_USER_NOTICE);
			return true;
		}
		
	}
	
	
	//send html mail
	static function send_html($to, $subject, $html_body){
		# vars
		$args=Utils::combine_args(func_get_args(), 3);
		
		#html body
		$args['html'] = $html_body;
		
		#send
		return self::send($to, $subject, Text::to_plain_simple($html_body), $args);
	}
	
	static function smtp_send($to_array, $headers){
		
		$args=Utils::combine_args(func_get_args(), 2, array(
						'smtp_server' => NULL,
						'smtp_ssl' => false,
						'smtp_port' => 25,
						'smtp_timeout' => 30,
						'smtp_username' => NULL,
						'smtp_password' => NULL,
						'smtp_localhost' => 'locahost',
						'smtp_newline' => "\r\n"
						));
		
		//Connect to the host on the specified port
		$smtpConnect = fsockopen($args['smtp_server'], $args['smtp_port'], $errno, $errstr, $args['smtp_timeout']);
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: Unable to connect to SMTP $smtpResponse", E_USER_WARNING);
			return false;
		}
		
		//Say Hello to SMTP
		fputs($smtpConnect, "HELO " .$args['smtp_localhost']. "\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: HELO failed. $smtpResponse", E_USER_WARNING);
			return false;
		}
		
		//Request Auth Login
		fputs($smtpConnect,"AUTH LOGIN\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: Auth Request failed. $smtpResponse", E_USER_WARNING);
			return false;
		}

		//Send username
		fputs($smtpConnect, base64_encode($args['smtp_username']) . "\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: Username failed. $smtpResponse", E_USER_WARNING);
			return false;
		}

		//Send password
		fputs($smtpConnect, base64_encode($args['smtp_password']) . "\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: Password failed. $smtpResponse", E_USER_WARNING);
			return false;
		}


		//Email From
		fputs($smtpConnect, "MAIL FROM: " .$args['from']. "\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: FROM failed. $smtpResponse", E_USER_WARNING);
			return false;
		}

		//Email To
		foreach($to_array as $to){
			$to = trim($to);
			if($to){
				fputs($smtpConnect, "RCPT TO: " .$to. "\r\n");
				$smtpResponse = fgets($smtpConnect, 515);
				if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
					trigger_error("<strong>Mail</strong> :: SMTP: RCPT failed. $smtpResponse", E_USER_WARNING);
					return false;
				}
			}
		}
		

		//The Email
		fputs($smtpConnect, "DATA\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: DATA header failed. $smtpResponse", E_USER_WARNING);
			return false;
		}


		fputs($smtpConnect, $headers."\r\n.\r\n");
		$smtpResponse = fgets($smtpConnect, 515);
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: Sending data failed. $smtpResponse", E_USER_WARNING);
			return false;
		}	
		
		// Say Bye to SMTP
		fputs($smtpConnect,"QUIT\r\n"); 
		$smtpResponse = fgets($smtpConnect, 515); 
		if(empty($smtpConnect) || substr($smtpResponse, 0, 3)>=400) {
			trigger_error("<strong>Mail</strong> :: SMTP: QUIT failed. $smtpResponse", E_USER_WARNING);
			return false;
		}
		
		
		return true;	
	}
	
}
?>