<?php
#/*
#* 9Tree Text Class - v0.3.5
#* String control functionalities
#*/

class Text
{
	private static $current_html_path;
	
	//str_replace n items
	static function str_replace_count($search,$replace,$subject,$times) {
	    $subject_original=$subject;
	    $len=strlen($search);    
	    $pos=0;
	    for ($i=1;$i<=$times;$i++) {
	        $pos=strpos($subject,$search,$pos);
	        if($pos!==false) {                
	            $subject=substr($subject_original,0,$pos);
	            $subject.=$replace;
	            $subject.=substr($subject_original,$pos+$len);
	            $subject_original=$subject;
	        } else {
	            break;
	        }
	    }
	    return($subject);
	}
	
	//returns specific number of words from a text (strips html)
	static function filter_nr_words($str, $word_limit, &$has_more){
		$has_more=false;
	    $str=self::to_plain($str);
		$words = explode(' ', $str, ($word_limit + 1));
		if(count($words) > $word_limit) {
			array_pop($words);
			$has_more=true;
		}
		return implode(' ', $words);
	}
	
	//convert a string to a safe javascript string
	static function to_javascript($str, $separator='"'){
		return str_replace(array("\r\n", "\n", $separator), array("\\r\\n", "\\n", "\\".$separator), $str);
	}
	
	//convert an html string to plain text
	static function to_plain($str){
		return preg_replace(array("/<br((>)|( .*?>))/i", "/(<.*?>)/"), array("\n", " "), str_replace(array("\r\n", "\n"), array("\n", " "), $str));
	}
	
	//convert a plain text string to html
	static function to_html($str){
		return str_replace(array("\t", "  ", "\r\n", "\n"), array("    ", " &nbsp;", "\n", "<br />"), $str);
	}
	
	//remove extras spaces, tabs, etc.
	static function simple_spaces($str){
		return str_replace("\n ", "\n", preg_replace("/\s+/mi", " ", str_replace("\t", " ", $str)));
	}
	
	//plain simple - to_plain followed by simple_spaces
	static function to_plain_simple($str){
		return self::simple_spaces(self::to_plain($str));
	}
	
	//filters all src, bgimage, href and css's url() in html string through Path::url_to
	static function correct_html_urls(&$string, $file_url){
		
		//get path to file (in case it's an url)
		$file_url=preg_replace('#^(.*?)\?.*?$#', '\\1', $file_url);
		
		//extract folder from path
		if(substr($file_url, strlen($file_url)-1)!='/'){
			self::$current_html_path=preg_replace('#^(.*?\/)(?!.*?\/).*?$#', '\\1', $file_url);
			
		} else {
			self::$current_html_path=$file_url;
		}
		
		//file in same folder - no correction necessary
		if(self::$current_html_path=='') return false; 
		
		//clear newlines
		$string  = str_replace(array("\r\n", "\n"), array("\n", '&-:newLine:-;'), $string);
		
		//each html tag...
		$string=preg_replace_callback('#(<(?![?/!]|[aA] ))(.*?[^?])(>)#', 'Text::correct_html_urls_callback1', $string);
		
		//put newlines back
		$string = str_replace('&-:newLine:-;', "\n", $string);
	}
	static function correct_html_urls_callback1(&$matches){
		//echo $matches[2]."<br>";
		
		//each url in tag
		$inside=preg_replace_callback('#(src|background|bgimage|href|style)=("|\')(.*?)\2#', 'Text::correct_html_urls_callback2', $matches[2]);
        
		return $matches[1].$inside.$matches[3];
	}
	static function correct_html_urls_callback2(){
		$start=&$matches2[1];

		if($matches2[1]=="style"){ 
			preg_match_all("#^(.*?)(url\()(.*?)(\).*?)$#", $matches2[3], $regs);
			if (@$regs[2][0]!="url("){
				return $matches2[0];
			}
			$start.="=\"".$regs[1][0].$regs[2][0];
			$url=&$regs[3][0];
			$end=&$regs[4][0];
		} else {
		  	$start.="=\"";
		  	$url=&$matches2[3];
		  	$end="";
		}
		$end.="\"";
		if (!preg_match("#^\/|((?i:http|ftp|gopher|file|wais|javascript):\/\/)#", $url)){
			$url=Path::combine($url, self::$current_html_path);
		}
		return $start.$url.$end;
	}
	static function normalize ($string) {
	    $table = array(	'Š'=>'S','š'=>'s','Đ'=>'Dj','đ'=>'dj','Ž'=>'Z','ž'=>'z','Č'=>'C','č'=>'c','Ć'=>'C','ć'=>'c',
	        			'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Æ'=>'A','Ç'=>'C','È'=>'E','É'=>'E',
				        'Ê'=>'E','Ë'=>'E','Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O','Ó'=>'O','Ô'=>'O',
				        'Õ'=>'O','Ö'=>'O','Ø'=>'O','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','Þ'=>'B','ß'=>'Ss',
				        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'a','ç'=>'c','è'=>'e','é'=>'e',
				        'ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ð'=>'o','ñ'=>'n','ò'=>'o','ó'=>'o',
				        'ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ý'=>'y','ý'=>'y','þ'=>'b',
				        'ÿ'=>'y','Ŕ'=>'R','ŕ'=>'r'
					);

	    return strtr($string, $table);
	}
}

?>