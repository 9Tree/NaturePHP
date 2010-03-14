<?php
#/*
#* 9Tree Http Class - v0.3.5
#* Http communications functionalities
#*/

class Http{
	
	//post contents to a file
	public static function file_post_contents($url, $post_data='', $conn_to=30, $stream_to=30) {
	    if(!$url = parse_url($url)){
			trigger_error("file_post_contents : invalid url");
			return false;
		}

	    if (!isset($url['port'])) {
	      if ($url['scheme'] == 'http') { $url['port']=80; }
	      elseif ($url['scheme'] == 'https') { $url['port']=443; }
	    }
	    $url['query']=isset($url['query'])?$url['query']:'';

	    $url['protocol']=$url['scheme'].'://';
		$eol="\r\n";
		
		//converts post data
		$post_data=Utils::build_querystring($post_data);

	    $headers =  "POST ".$url['protocol'].$url['host'].$url['path']."?".$url['query']." HTTP/1.0".$eol. 
	                "Host: ".$url['host'].$eol. 
	                "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol. 
	                "Content-Type: application/x-www-form-urlencoded".$eol. 
	                "Content-Length: ".strlen($post_data).$eol.
	                $eol.$post_data;
	    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, $conn_to); 
	    if($fp) {
			fputs($fp, $headers);
	      	$result = '';
	
			stream_set_blocking($fp, TRUE); 
			stream_set_timeout($fp,$stream_to); 
			$info = stream_get_meta_data($fp); 

			while ((!feof($fp)) && (!$info['timed_out'])) { 
				$result .= fgets($fp, 4096); 
			    $info = stream_get_meta_data($fp); 
			}
	
		  	if ($info['timed_out']) {
		      	trigger_error('file_post_contents : stream connection to '.$url['protocol'].$url['host'].$url['path']."?".$url['query']." timed out ($stream_to)!", E_USER_WARNING);
			  	return false;
		  	}

			

	      	//removes headers
			$pos = strpos($result, "\r\n\r\n")+4;
	      	$result=substr($result, $pos, strlen($result)-$pos);
			
	      	return $result;
	    } else {
			trigger_error("file_post_contents : unable to reach file");
			return false;
		}
	}
}
?>