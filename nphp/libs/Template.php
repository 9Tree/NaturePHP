<?php
#/*
#* 9Tree Templates Class - v0.3.5
#* Templates class
#*/

class Template{
	private $str=false;
	function __construct(){
		$args=Utils::combine_args(func_get_args(), 0, array('file' => false, 'string' => false, 'part'=>false, 'mode'=>'auto', 'cache'=>true, 'content'=>false, 'correct_paths'=>true));
		
		//get source
		if($args['string']!==false){
			if(!is_string($args['string'])){
				$this->str='';
				Log::add('Template', 'String option is not string in construct.');
			} else {
				$this->str=$args['string'];
			}
			//transform all keys to {#key/} format
			$this->defaultKeys($args['mode']);
			
		} elseif($args['file']!==false){
			//try using cache
			$cache_id=$args['file'].'::'.$args['mode'].'::'.($args['correct_paths']?'corrected_paths':'raw_paths');
			if($args['cache']!==false && Mem::is_set($cache_id, 'files_cache')){
				$this->str=Mem::get($cache_id, 'files_cache');
			//check 4 file
			} elseif(!is_file($args['file'])){
				$this->str='';
				$str_len=strlen($args['file']);
				Log::add('Template', 'File "'.($str_len>30?substr($args['file'], $str_len-30, $str_len):$args['file']).'" not found.');
			//load file contents
			} else {
				$this->str=file_get_contents($args['file']);
				if($this->str===false){
					$this->str='';
					Log::add('Template', 'File "'.($str_len>30?substr($args['file'], $str_len-30, $str_len):$args['file']).'" - unknown error loading file.');
				} else {
					//transform all keys to {#key/} format
					$this->defaultKeys($args['mode']);
					
					//correct paths
					if($args['correct_paths']){
						//auto-detect mode
						if($args['mode']=='auto'){
							if(preg_match('!\.((html)|(htm)|(xhtml)|(css))$!i', $args['file'])){
								$args['mode']='html';
							} elseif(preg_match('!\.((php)|(js))$!i', $args['file'])){
								$args['mode']='code';
							}
						}
						
						//correct paths
						if($args['mode']=='html'){
							$this->str = Text::correct_html_urls($this->str, Path::relative($args['file'], Path::myBase()));
						} elseif($args['mode']=='code'){
							//to-do!!!
						}
					}
				}
				//set cache
				if($args['cache']!==false) Mem::set($cache_id, $this->str, 'files_cache');
			}
		} else {
			$this->str='';
			Log::add('Template', 'No source file or string defined in construct.');
		}
		
		
		//strip to part
		if($args['part']!==false){
			$pattern='/.*?{#('.Utils::sanitize_regex_pattern($args['part']).')}(.*?){#\/\\1}.*?/';
			$this->str=preg_replace($pattern, '\\2', $this->str);
		}
		
		//collapse all keys (clear parts content)
		$pattern='/{#([a-zA-Z0-9._-]*)}(.*?){#/\\1}\/';
		$this->str=preg_replace($pattern, '{#\\1/}', $this->str);
		
		//apply content
		if($args['content']!==false){
			$args['content']=Utils::mixed_to_array($args['content']);
			foreach($args['content'] as $part=>$content){
				$this->addContent($part, $content);
			}
		}
	}
	private function defaultKeys($mode){
		//define mode
		switch($args['mode']){
			case 'php':
			case 'javascript':
				$mode_escape_l="/*";
				$mode_escape_r="*/";
			case 'html':
			default:
				$mode_escape_l="<!--";
				$mode_escape_r="-->";
			break;
		}
		
		//transform all keys to {#key/} format
		$pattern='/'.Utils::sanitize_regex_pattern($mode_escape_l.'#');
		$pattern.='(.*?)'.Utils::sanitize_regex_pattern($mode_escape_r).'/';
		$this->str=preg_replace($pattern, '{#\\1}', $this->str);
	}
	function addContent($part, &$content){
		$this->str=str_replace("{#$part/}", $content, $this->str);
	}
	function render(){
		//clear all keys
		return preg_replace('/{#[a-zA-Z0-9._-]*\/}/', '', $this->str);
	}
}
?>