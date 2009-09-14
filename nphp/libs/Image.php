<?php
#/*
#* 9Tree Text Class - v0.1
#* Image control functionalities
#*/

class Image
{		
	//image info
	private $info=array(
		'width'=>null, 
		'height'=>null, 
		'type'=>null, 
		'transparentColorRed'=>null,
		'transparentColorGreen'=>null,
		'transparentColorBlue'=>null);
		
	//location info
	private $location=array(
		'path'=>null,
		'folder'=>null, 
		'basename'=>null);
		
	//main image identifier
	private $image=null;
	
	//children images (thumbs, resizes, etc)
	public $children=array();
	//error control variable - TO-DO
	//public $error=null;	//1 - file not found, 2 - file not readable, 3 - unrecognized file type
	
	
	//create instance function
	function __construct(&$image, $info, $location){
		
		//no GD installed!!
		if(!function_exists("imagecreatetruecolor")){
			trigger_error('<strong>Image</strong> :: GD library not installed!.', E_USER_ERROR);
			die();
		}
		
		if(is_array($image)) {
			//image identifier
			$this->image=&$image['resource'];
			//image info
			$this->info['width']=$image['width'];
			$this->info['height']=$image['height'];
			$this->info['type']=$image['type'];
			$this->info['transparentColorRed']=$image['transparentColorRed'];
			$this->info['transparentColorGreen']=$image['transparentColorGreen'];
			$this->info['transparentColorGreen']=$image['transparentColorBlue'];
			//location info
			$this->location['path']=$image['path'];
			$this->location['folder']=$image['folder'];
			$this->location['basename']=$image['basename'];
		} else {
			trigger_error('<strong>Image</strong> :: unknown image/path in construct.', E_USER_WARNING);
		}
	}
	
	//STATIC FUNCTIONS
	
	//load image from path
	static function &from_file($file){
		$info=array();
		$location=array();
		$image=null;
		
		//set location information
		$location['path']=$file;
		$path_parts=pathinfo($file);
		$location['folder']=$path_parts['dirname'];
		$location['basename']=$path_parts['basename'];

		
		//these variables might not be filled
		$info['transparentColorRed'] = null;
        $info['transparentColorGreen'] = null;
        $info['transparentColorBlue'] = null;
		
		// performs some error checking first
        // if source file does not exists
        if (!file_exists($location['path'])) {
            trigger_error('<strong>Image</strong> :: file not found "'.$location['path'].'"', E_USER_WARNING);
        // if source file is not readable
        } elseif (!is_readable($image['path'])) {
            // save the error level and stop the execution of the script
            trigger_error('<strong>Image</strong> :: file is not readable "'.$location['path'].'"', E_USER_WARNING);
        // get source file width, height and type
        // and if founds a not-supported file type
        } elseif (!list($info['width'], $info['height'], $info['type']) = @getimagesize($location['path'])) {
            // save the error level and stop the execution of the script
            trigger_error('<strong>Image</strong> :: unrecognized file type "'.$location['path'].'"', E_USER_NOTICE);
        // if no errors so far
        } else {

            // creates an image from file using extension dependant function
            // checks for file extension
            switch ($info['type']) {
                // if gif
                case 1:	//to-do
                    // the following part gets the transparency color for a gif file
                    // this code is from the PHP manual and is written by
                    // fred at webblake dot net and webmaster at webnetwizard dotco dotuk, thanks!
                    $fp = fopen($location['path'], "rb");
                    $result = fread($fp, 13);
                    $colorFlag = ord(substr($result,10,1)) >> 7;
                    $background = ord(substr($result,11));
                    if ($colorFlag) {
                        $tableSizeNeeded = ($background + 1) * 3;
                        $result = fread($fp, $tableSizeNeeded);
                        $info['transparentColorRed'] = ord(substr($result, $background * 3, 1));
                        $info['transparentColorGreen'] = ord(substr($result, $background * 3 + 1, 1));
                        $info['transparentColorBlue'] = ord(substr($result, $background * 3 + 2, 1));
                    }
                    fclose($fp);
                    // -- here ends the code related to transparency handling
                    // creates an image from file
                    $image = @imagecreatefromgif($location['path']);
					return true;
                    break;
                // if jpg
                case 2:
                    // creates an image from file
                    $image = @imagecreatefromjpeg($location['path']);
					return true;
                    break;
                // if png
                case 3:
                    // creates an image from file
                    $image = @imagecreatefrompng($location['path']);
					return true;
                    break;
                default:
                    // if file has an unsupported extension
                    // note that we call this if the file is not gif, jpg or png even though the getimagesize function
                    // handles more image types
                    $this->error = 3;
                    trigger_error('<strong>Image</strong> :: unrecognized file type "'.$location['path'].'" (2)', E_USER_NOTICE);
            }
		}
		return new Image($image, $info, $location);
	}
	
	
	//INSTANCE FUNCTIONS
	
	//creates a new empty image
	public function new_resource($with, $height){
		
		$image=array();
		$image['width']=$with;
		$image['height']=$height;
		// creates a blank image
        $image['resource'] = imagecreatetruecolor($image['width'], $image['height']);
        // if we have transparency in the image (gif)
        if (
			isset($this->info['transparentColorRed']) && 
			isset($this->info['transparentColorGreen']) && 
			isset($this->info['transparentColorBlue'])) {
				$image['transparentColorRed']=$this->info['transparentColorRed'];
				$image['transparentColorGreen']=$this->info['transparentColorGreen'];
				$image['transparentColorBlue']=$this->info['transparentColorBlue'];
	            $transparent = imagecolorallocate(
								$image['resource'], 
								$image['transparentColorRed'], 
								$image['transparentColorGreen'], 
								$image['transparentColorBlue']);
	            imagefilledrectangle($image['resource'], 0, 0, $image['width'], $image['height'], $transparent);
	            imagecolortransparent($image['resource'], $transparent);
        }
        // return new image resource
        return $image;
	}
	
	//returns a copy of the the current instance
	function copy(){
		return $this;
	}
	
	//resizes current image (multiply(*), fit within(|), fit fill(#), force width(>), force height(>))
	function resize($style){
		//checks operation/values
		$style=$this->parse_style($style);
		switch($style['operation']){
			case "*":	//multiply both width and height
				//new image with final measures
				$style['width']=$this->info['width']*$style['multiplier'];
				$style['height']=$this->info['height']*$style['multiplier'];
				$image=$this->new_resource($style['width'], $style['height']);
				imagecopyresampled($image['resource'], $this->image, 0, 0, 0, 0, $style['width'], $style['height'], $this->info['width'], $this->info['height']);
			break;
			case ">":	//force this width
				$this->resize_force_width($image, $style);
			break;
			case "<":	//force this height
				$this->resize_force_height($image, $style);
			break;
			case "#":	//auto: fit fill
				if($this->info['height']/$style['height'] > $this->info['width']/$style['width']){
					$this->resize_force_width($image, $style);	
				} else {
					$this->resize_force_height($image, $style);
				}
			break;
			case "|":	//auto: fit within
				if($this->info['height']/$style['height'] > $this->info['width']/$style['width']){
					$this->resize_force_height($image, $style);	
				} else {
					$this->resize_force_width($image, $style);
				}
			break;
			default:	//simple resize
				//new image with final measures
				$image=$this->new_resource($style['width'], $style['height']);
				imagecopyresampled($image['resource'], $this->image, 0, 0, 0, 0, $style['width'], $style['height'], $this->info['width'], $this->info['height']);
			break;
		}
		//set new information
		$this->info['width']=$style['width'];
		$this->info['height']=$style['height'];
		//set new resource image
		$this->image=&$image['resource'];
	}
	
	//math for resize
	function resize_force_width(&$image, &$style){
		$ratio=$this->info['width']/$style['width'];	//ratio
		$dest_h=(int)($this->info['height']/$ratio);	//dest_h
		$style['height']=$dest_h>$style['height']?$style['height']:$dest_h;	//limit dest_h	
		$src_y=(int)($this->info['height']-$style['height']/2);	//calculate where to start y crop
		//new image with final measures
		$image=$this->new_resource($style['width'], $style['height']);
		imagecopyresampled($image['resource'], $this->image, 0, 0, 0, $src_y, $style['width'], $style['height'], $this->info['width'], $this->info['height']);
	}
	function resize_force_height(&$image, &$style){
		$ratio=$this->info['height']/$style['height'];	//ratio
		$dest_w=(int)($this->info['width']/$ratio);	//dest_w
		$style['width']=$dest_w>$style['width']?$style['width']:$dest_w;	//limit dest_h	
		$src_x=(int)($this->info['width']-$style['width']/2);	//calculate where to start x crop
		//new image with final measures
		$image=$this->new_resource($style['width'], $style['height']);
		imagecopyresampled($image['resource'], $this->image, 0, 0, $src_x, 0, $style['width'], $style['height'], $this->info['width'], $this->info['height']);
	}
	
	//parse style
	function parse_style($style){
		$arr=array();
		if(strpos($style, '*')===0){
			//simple resize
			$arr['operation']='*';
			$arr['multiplier']=substr($style, 1, 0);
		} else {
			if(strpos($style, '>')!==false){
				//force width
				$arr['operation']='>';
			} elseif(strpos($style, '<')!==false){
				//force height
				$arr['operation']='<';
			} elseif(strpos($style, '#')!==false){
				//fit fill
				$arr['operation']='#';
			} elseif(strpos($style, '|')!==false){
				//fit within
				$arr['operation']='|';
			} else {
				//simple resize
				$arr['operation']='=';
			}
			preg_match("@([0-9]+)x([0-9]+).*?@", $style, $matches);
			$arr['width']=$matches[1];
			$arr['height']=$matches[2];
		}
		return $arr;
	}
	
	//makes babies (aka thumbnails, resizes, etc)
	function children($styles){
		$location=$this->location;
		foreach($styles as $folder=>$style){
			$location['folder'].="/".$folder;
			$new_id=count($children);
			$this->children[$new_id]['name']=$folder;
			$this->children[$new_id]['style']=$style;
			$this->children[$new_id]['instance']=new Image(&$this->image, $this->info, $location);	//creates instance
			$this->children[$new_id]['instance']->resize($style);	//resizes to style
		}
	}
	
	//FINAL FUNCTIONS
	
	//saves current image
	function save(){
		
		$args=Utils::combine_args(func_get_args(), 0, array(
						'folder' => false,
						'filename' => false,
						'secure' => true
						));
						
		if($args['folder']!==false) $this->location['folder']=$args['folder'];	//filter folder
		
		if(!Disk::make_dir($this->location['folder']) && is_writable($this->location['folder'])){
			trigger_error('<strong>Image</strong> :: Unable to create/use folder, permission denied or malformed path for "'.$location['folder'].'"', E_USER_WARNING);
		} else {
			
			if($args['filename']!==false) $this->location['filename']=$args['filename'];	//filter filename
			
			//unique/sanitize
			if($args['secure']) $this->location['filename']=Disk::unique_filename($this->location['folder'], $this->location['filename']);
			//set final path
			$this->location['path'] = $this->location['folder']."/".$this->location['filename'];
			//save the file
	        // image saving process goes according to required extension
	        switch ($this->info['type']) {
	            // if gif
	            case "gif":
	                // if gd support for this file type is not available
	                if (!function_exists("imagegif")) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: GD does not support gif files.', E_USER_WARNING);
	                    return false;
	                // if, for some reason, file could not be created
	                } elseif (@!imagegif($this->image, $this->location['path'])) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: Unknown error, unable to save gif file.', E_USER_WARNING);
	                    return false;
	                }
	                break;
	            // if jpg
	            case "jpg":
	            case "jpeg":
	                // if gd support for this file type is not available
	                if (!function_exists("imagejpeg")) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: GD does not support jpeg files.', E_USER_WARNING);
	                    return false;
	                // if, for some reason, file could not be created
	                } elseif (@!imagejpeg($this->image, $this->location['path'])) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: Unknown error, unable to save jpeg file.', E_USER_WARNING);
	                    return false;
	                }
	                break;
	            case "png":
	                // if gd support for this file type is not available
	                if (!function_exists("imagepng")) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: GD does not support png files.', E_USER_WARNING);
	                    return false;
	                // if, for some reason, file could not be created
	                } elseif (@!imagepng($this->image, $this->location['path'])) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: Unknown error, unable to save png file.', E_USER_WARNING);
	                    return false;
	                }
					break;
	            // if not a supported file extension
	            default:
	                return false;
	        }
	        // if file was created successfully return filename
	        return $this->location['filename'];
		}
	}
	
	//saves current images and all children
	function save_all(){
		
		$args=Utils::combine_args(func_get_args(), 0, array(
						'save_original' => true,
						'folder' => false,
						'filename' => false,
						'secure' => true
						));
		//saves self
		if($args['save_original']) {
			$args['filename']=$this->save($args);
			if($args['filename']===false) return false;	//something went wrong!
		}
		
		//saves all children
		$dest_folder=$args['folder'];	//keep val
		$count=count($this->children);
		for($i=0;$i<$count;$i++){	//cycle children
			if($dest_folder!==false){
				$args['folder']=$dest_folder."/".$this->$children[$i]['name'];	//change folder appropriately 
			}
			$this->$children[$i]['instance']->save($args);	//save child
		}
		
	}

}

?>