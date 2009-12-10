<?php
#/*
#* 9Tree Text Class - v0.1
#* Image control functionalities
#*
#* Styles specification:
#* 'scale'%				Height and width both scaled by specified percentage.
#* 'scale-x'x'scale-y'%	Height and width individually scaled by specified percentages.
#* 'width'				Width given, height automagically selected to preserve aspect ratio.
#* x'height'			Height given, width automagically selected to preserve aspect ratio.
#* 'width'x'height'		Maximum values of height and width given, aspect ratio preserved.
#* 'width'x'height'^	Minimum values of width and height given, aspect ratio preserved.
#* 'width'x'height'!	Width and height emphatically given, original aspect ratio ignored.
#* 'width'x'height'>	Change as per 'width'x'height' but only if an image dimension exceeds a specified dimension.
#* 'width'x'height'<	Same as 'width'x'height'^ but only if an image dimension is smaller than a specified dimension.
#* 'area'@				Resize image to have specified area in pixels. Aspect ratio is preserved. (not implemented)
#* 'width'x'height'#	Same as 'width'x'height'^ but centered and cropped to the 'not fit' dimension.
#*/

class Image
{		
	//image info
	private $info=array(
		'width'=>null, 
		'height'=>null, 
		'type'=>null);
		
	//location info
	private $options=array(
		'path'=>null,
		'folder'=>null, 
		'basename'=>null, 
		'transparentColorRed'=>null,
		'transparentColorGreen'=>null,
		'transparentColorBlue'=>null);
	
	//this is a constant
	private $save_defaults=array(
					'folder' => false,
					'basename' => false,
					'secure' => true);
						
	//main image identifier
	private $image=null;
	
	//children images (thumbs, resizes, etc)
	public $children=array();
	//error control variable - TO-DO
	//public $error=null;	//1 - file not found, 2 - file not readable, 3 - unrecognized file type
	
	
	//create instance function
	function __construct(&$image, $info=array()){
		
		//no GD installed!!
		if(!function_exists("imagecreatetruecolor")){
			trigger_error('<strong>Image</strong> :: GD library not installed!.', E_USER_ERROR);
			die();
		}
		
		
		//check is in internal resource format
		if(is_array($image)) {
			//image identifier
			$this->image=&$image['resource'];
			//image info
			$this->info['width']=$image['width'];
			$this->info['height']=$image['height'];
			$this->info['type']=$image['type'];
			//options
			$this->options['transparentColorRed']=$image['transparentColorRed'];
			$this->options['transparentColorGreen']=$image['transparentColorGreen'];
			$this->options['transparentColorGreen']=$image['transparentColorBlue'];
			$this->options['path']=$image['path'];
			$this->options['folder']=$image['folder'];
			$this->options['basename']=$image['basename'];
			
		} else {	//normal image instance
			//image identifier
			$this->image=&$image;
			//image info
			$this->info = array_merge($this->info, $info);
			//options
			$this->options = Utils::combine_args(func_get_args(), 2, $this->options);
		}

	}
	
	//STATIC FUNCTIONS
	
	//load image from path
	static function from_file($file){
		$info=array();
		$options=array();
		$image=null;
		
		//set location information
		$options['path']=$file;
		$path_parts=pathinfo($file);
		$options['folder']=$path_parts['dirname'];
		$options['basename']=$path_parts['basename'];

		
		//these variables might not be filled
		$options['transparentColorRed'] = null;
        $options['transparentColorGreen'] = null;
        $options['transparentColorBlue'] = null;
		
		// performs some error checking first
        // if source file does not exists
        if (!file_exists($options['path'])) {
            trigger_error('<strong>Image</strong> :: file not found "'.$options['path'].'"', E_USER_WARNING);
        // if source file is not readable
        } elseif (!is_readable($options['path'])) {
            // save the error level and stop the execution of the script
            trigger_error('<strong>Image</strong> :: file is not readable "'.$options['path'].'"', E_USER_WARNING);
        // get source file width, height and type
        // and if founds a not-supported file type
        } elseif (!list($info['width'], $info['height'], $info['type']) = @getimagesize($options['path'])) {
            // save the error level and stop the execution of the script
            trigger_error('<strong>Image</strong> :: unrecognized file type "'.$options['path'].'"', E_USER_NOTICE);
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
                    $fp = fopen($options['path'], "rb");
                    $result = fread($fp, 13);
                    $colorFlag = ord(substr($result,10,1)) >> 7;
                    $background = ord(substr($result,11));
                    if ($colorFlag) {
                        $tableSizeNeeded = ($background + 1) * 3;
                        $result = fread($fp, $tableSizeNeeded);
                        $options['transparentColorRed'] = ord(substr($result, $background * 3, 1));
                        $options['transparentColorGreen'] = ord(substr($result, $background * 3 + 1, 1));
                        $options['transparentColorBlue'] = ord(substr($result, $background * 3 + 2, 1));
                    }
                    fclose($fp);
                    // -- here ends the code related to transparency handling
                    // creates an image from file
                    $image = @imagecreatefromgif($options['path']);
                    break;
                // if jpg
                case 2:
                    // creates an image from file
                    $image = @imagecreatefromjpeg($options['path']);
                    break;
                // if png
                case 3:
                    // creates an image from file
                    $image = @imagecreatefrompng($options['path']);
                    break;
                default:
                    // if file has an unsupported extension
                    // note that we call this if the file is not gif, jpg or png even though the getimagesize function
                    // handles more image types
                    $this->error = 3;
                    trigger_error('<strong>Image</strong> :: unrecognized file type "'.$options['path'].'" (2)', E_USER_NOTICE);
            }
		}

		return new Image($image, $info, $options);
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
			isset($this->options['transparentColorRed']) && 
			isset($this->options['transparentColorGreen']) && 
			isset($this->options['transparentColorBlue'])) {
				$image['transparentColorRed']=$this->options['transparentColorRed'];
				$image['transparentColorGreen']=$this->options['transparentColorGreen'];
				$image['transparentColorBlue']=$this->options['transparentColorBlue'];
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
		//initializes image
		$image=null;
		$src_x=0;
		$src_y=0;
		$height=0;
		$width=0;
		$o_width=$this->info['width'];
		$o_height=$this->info['height'];
		
		switch($style['operation']){
			
			case "%":	//multiply both width and height
				//new image with final measures
				if(isset($style['percentage'])){	//simple percentage
					$ratio = $style['percentage']/100;
					$width=(int)($this->info['width']*$ratio);
					$height=(int)($this->info['height']*$ratio);
				} else {	//specific percentage
					$width=(int)($this->info['width']*$style['width']/100);
					$height=(int)($this->info['height']*$style['height']/100);
				}
			break;
			
			case "force_width":	//force this width
				$width=$style['width'];
				$height=(int)($this->info['height']*$style['width']/$this->info['width']);
			break;
			
			case "force_height":	//force this height
				$height=$style['height'];
				$width=(int)($this->info['width']*$style['height']/$this->info['height']);
			break;
			
			case ">":	//fit within
				if($this->info['height']<$style['height'] && $this->info['width']<$style['width']){
					$width=$style['width'];
					$height=$style['height'];
					break;
				}
			case "normal":
				if($this->info['height']/$style['height'] > $this->info['width']/$style['width']){
					$height=$style['height'];
					$width=(int)($this->info['width']*$style['height']/$this->info['height']);
				} else {
					$width=$style['width'];
					$height=(int)($this->info['height']*$style['width']/$this->info['width']);
				}
			break;
			
			case "<":	//fit outside
				if($this->info['height']>$style['height'] && $this->info['width']>$style['width']){
					$width=$style['width'];
					$height=$style['height'];
					break;
				}
			case "^":
				if($this->info['height']/$style['height'] > $this->info['width']/$style['width']){
					$width=$style['width'];
					$height=(int)($this->info['height']*$style['width']/$this->info['width']);
				} else {
					$height=$style['height'];
					$width=(int)($this->info['width']*$style['height']/$this->info['height']);
				}
			break;
			
			case "!":
				$width=$style['width'];
				$height=$style['height'];
			break;
			
			case "#":
				$width=$style['width'];
				$height=$style['width'];
				if($this->info['height']/$style['height'] > $this->info['width']/$style['width']){
					
					$o_height=(int)($this->info['width']*$style['height']/$style['width']);
					$src_y=(int)(($this->info['height']-$o_height)/2);
				} else {
					$o_width=(int)($this->info['width']*$style['height']/$style['width']);
					$src_x=(int)(($this->info['width']-$o_width)/2);
				}
			break;
		}
		
		
		//general resize
		$image=$this->new_resource($width, $height);
		imagecopyresampled($image['resource'], $this->image, 0, 0, $src_x, $src_y, $width, $height, $o_width, $o_height);
		
		//set new information
		$this->info['width']=$width;
		$this->info['height']=$height;
		//set new resource image
		$this->image=&$image['resource'];
	}
	
	//parse style
	function parse_style($style){
		$arr=array();

		if(preg_match("@^([0-9]+)x([0-9]+)(.*?)?$@", $style, $matches)){
			//specific operation
			$arr['operation']=$matches[3]?$matches[3]:"normal";
			$arr['width']=$matches[1];
			$arr['height']=$matches[2];
		} elseif(substr($style, -1, 1)=="%") {
			//percentage resize
			$arr['operation']="%";
			$arr['percentage']=substr($style, 0, -1);
		} elseif(substr($style, 0, 1)=="x"){
			//force height
			$arr['operation']="force_height";
			$arr['height']=substr($style, 1, strlen($style)-1);
		} else {
			$arr['operation']="force_width";
			$arr['width']=$style;
		}
		
		return $arr;
	}
	
	//makes babies (aka thumbnails, resizes, etc)
	function children($styles){
		$options=$this->options;
		foreach($styles as $folder=>$style){
			$options['folder'].="/".$folder;
			$new_id=count($this->children);
			$this->children[$new_id]['name']=$folder;
			$this->children[$new_id]['style']=$style;
			$this->children[$new_id]['instance']=new Image(&$this->image, $this->info, $options);	//creates instance
			$this->children[$new_id]['instance']->resize($style);	//resizes to style
		}
	}
	
	//FINAL FUNCTIONS
	
	//saves current image
	function save(){
		
		$args=Utils::combine_args(func_get_args(), 0, $this->save_defaults);
						
		if($args['folder']!==false) $this->options['folder']=$args['folder'];	//filter folder
		
		if(!Disk::make_dir($this->options['folder']) && is_writable($this->options['folder'])){
			trigger_error('<strong>Image</strong> :: Unable to create/use folder, permission denied or malformed path for "'.$this->options['folder'].'"', E_USER_WARNING);
		} else {
			
			if($args['basename']!==false) $this->options['basename']=$args['basename'];	//filter filename
			
			//unique/sanitize
			if($args['secure']) $this->options['basename']=Disk::unique_filename($this->options['folder'], $this->options['basename']);
			//set final path
			$this->options['path'] = $this->options['folder']."/".$this->options['basename'];
			//save the file
	        // image saving process goes according to required extension
	        switch ($this->info['type']) {
	            // if gif
	            case IMAGETYPE_GIF:
	                // if gd support for this file type is not available
	                if (!function_exists("imagegif")) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: GD does not support gif files.', E_USER_WARNING);
	                    return false;
	                // if, for some reason, file could not be created
	                } elseif (@!imagegif($this->image, $this->options['path'])) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: Unknown error, unable to save gif file.', E_USER_WARNING);
	                    return false;
	                }
	                break;
	            // if jpg
	            case IMAGETYPE_JPEG:
	                // if gd support for this file type is not available
	                if (!function_exists("imagejpeg")) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: GD does not support jpeg files.', E_USER_WARNING);
	                    return false;
	                // if, for some reason, file could not be created
	                } elseif (@!imagejpeg($this->image, $this->options['path'])) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: Unknown error, unable to save jpeg file.', E_USER_WARNING);
	                    return false;
	                }
	                break;
	            case IMAGETYPE_PNG:
	                // if gd support for this file type is not available
	                if (!function_exists("imagepng")) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: GD does not support png files.', E_USER_WARNING);
	                    return false;
	                // if, for some reason, file could not be created
	                } elseif (@!imagepng($this->image, $this->options['path'])) {
	                    // save the error level and stop the execution of the script
	                    trigger_error('<strong>Image</strong> :: Unknown error, unable to save png file.', E_USER_WARNING);
	                    return false;
	                }
					break;
	            // if not a supported file extension
	            default:
					trigger_error('<strong>Image</strong> :: Unrecognized file format "'.$this->info['type'].'"', E_USER_WARNING);
	                return false;
	        }
	        // if file was created successfully return filename
	        return $this->options['basename'];
		}
	}
	
	//saves all the dependant images
	function save_children(){
		
		$args=Utils::combine_args(func_get_args(), 0, $this->save_defaults);

		//saves all children
		$dest_folder=$args['folder'];	//keep val
		$count=count($this->children);
		$first = true;
		for($i=0;$i<$count;$i++){	//cycle children
			if($dest_folder!==false){
				$args['folder']=$dest_folder."/".$this->children[$i]['name'];	//change folder appropriately 
			}
			
			//force secure only on first item
			if($first){
				$args['basename'] = $this->children[$i]['instance']->save($args);	//save child
				if(!$args['basename']) return false;
				//remove secure option for next images
				$args['secure'] = false;
				$first=false;
			} else {
				$this->children[$i]['instance']->save($args);	//save child
			}
			
		}
		
		return $args['basename'];			
	}
	
	//saves current images and all children
	function save_all(){
		
		$args=Utils::combine_args(func_get_args(), 0, $this->save_defaults);
		//saves self
		if($args['basename']=$this->save($args)){
			//saves dependant
			$args['secure']=false;	//force children basename to be coherent with original
			$this->save_children($args);
			return $args['basename'];
		}
		return false;	//something went wrong!
	}

}

?>