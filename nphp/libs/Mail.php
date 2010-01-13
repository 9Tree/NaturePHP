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
						'attachments' => array()
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
		# reply-to
		if($args['reply-to']) 	$headers .= "Reply-To: ".$args['reply-to']."\r\n";
		# cc
		if($args['cc']) 		$headers .= "cc: ".$args['cc']."\r\n";
		# bcc
		if($args['bcc']) 		$headers .= "bcc: ".$args['bcc']."\r\n";
		
		$message = "";
		
		# add boundary string and mime type specification
		if($args['attachments']){
			$headers .= "Content-Type: multipart/mixed; boundary=\"PHP-mixed-$random_hash\"";
			$message .= "--PHP-mixed-$random_hash\r\n";
		}
		
		# html mode - write plain text only as alternative
		if($args['html'] || $args['attachments']){
			if(!$args['attachments']){
				$headers .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-$random_hash\"";
			} else {
				$message .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-$random_hash\"\r\n\r\n";
			}
			$message .= "--PHP-alt-$random_hash\r\n";		
		
			$message .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
			$message .= "Content-Transfer-Encoding: 7bit\r\n";
		
			$message .= "\r\n".$body."\r\n\r\n";
			
			if($args['html']){
				$message .= "--PHP-alt-$random_hash\r\n";
				$message .= "Content-Type: text/html; charset=\"utf-8\"\r\n";
				$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
				$message .= "\r\n".$args['html']."\r\n\r\n";
			}
			
			$message .= "--PHP-alt-$random_hash--\r\n\r\n";
		} else {
			$message = $body;
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
					$message .= "\r\n";
				} else $first = false;
				
				//read the atachment file contents into a string,
				//encode it with MIME base64,
				//and split it into smaller chunks
				$filename=basename($file);
				
				if($finfo_avail) $ftype = finfo_file($finfo, $filename);
				else $ftype = "unknown";
				
				$attachment = chunk_split(base64_encode(file_get_contents($file)));
				
				
				$message .= "--PHP-mixed-$random_hash\r\n"; 
				$message .= "Content-Type: $ftype; name=\"$filename\"\r\n";
				$message .= "Content-Transfer-Encoding: base64\r\n"; 
				$message .= "Content-Disposition: attachment\r\n";

				$message .= "\r\n".$attachment."\r\n\r\n"; 
				
			}
			
			if(!$first){
				//close the last one
				$message .= "--PHP-mixed-$random_hash--\r\n\r\n";
			}
		}
		
		
		//try sending the composed email
		if(!mail( $to, $subject, $message, $headers )){
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
		self::send($to, $subject, Text::to_plain_simple($html_body), $args);
	}
}
?>