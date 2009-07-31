<?php
#/*
#* 9Tree Http Class - v0.3.5
#* Http communications functionalities
#*/

class Http{
	
	//post contents to a file
	public static function file_post_contents($url, $post_data='') {
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
	    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30); 
	    if($fp) {
	      fputs($fp, $headers);
	      $result = '';
	      while(!feof($fp)) { $result .= fgets($fp, 128); }
	      fclose($fp);

	      //removes headers
	      $pattern="/^.*\r\n\r\n/s";
	      $result=preg_replace($pattern,'',$result);
	
	      return $result;
	    } else {
			trigger_error("file_post_contents : unable to reach file");
			return false;
		}
	}
}
?>