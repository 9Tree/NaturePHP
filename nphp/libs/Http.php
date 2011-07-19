<?php
#/*
#* 9Tree Http Class - v0.3.5
#* Http communications functionalities
#*/

class Http extends Nphp_static{
	
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
	
	public static function multi_getcontents($files, $callback=null){

		$master = curl_multi_init();
		$total = count($files);
		
		//initialize all
		foreach($files as $file)
		{
			$handle = curl_init($file);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($master, $handle);
		}
		
		//start all the fetching in the background
		do {
		    $mrc = curl_multi_exec($master, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		
		//take the parallel processing time to create the tables
		$done=0;
		while ($done<$total) {
			
			
			curl_multi_select($master);	//let's wait for activity or timeout
			curl_multi_exec($master, $active);
			
			$i=0;
			$dels=array();
			//process the ones done
			while($info=curl_multi_info_read($master)){
				$url = curl_getinfo($info['handle'], CURLINFO_EFFECTIVE_URL);
				if($callback!=null) $callback($url, ($info['result']==CURLE_OK?curl_multi_getcontent($info['handle']):false) );
				curl_close($info['handle']);
				curl_multi_remove_handle($master, $info['handle']); 
				$dels[] = $i;
				$done++;
			}
			
		}
		curl_multi_close($master);
	}
}
?>