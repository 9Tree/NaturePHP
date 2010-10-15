<?php
#/*
#* 9Tree Filesystem Class - v0.3.5
#* Files & Folders functionalities
#*/

class Disk{
	
	//make new directory
	static function make_dir($target) {
		// from php.net/mkdir user contributed notes
		if (file_exists($target)) {
			if (! @ is_dir($target))
				return false;
			else
				return true;
		}

		// Attempting to create the directory may clutter up our display.
		if (@ mkdir($target)) {
			$stat = @ stat(dirname($target));
			$dir_perms = $stat['mode'] & 0007777;  // Get the permission bits.
			@ chmod($target, $dir_perms);
			return true;
		} else {
			if ( is_dir(dirname($target)) )
				return false;
		}

		// If the above failed, attempt to create the parent node, then try again.
		if (self::make_dir(dirname($target)))
			return self::make_dir($target);

		return false;
	}
	
	//unique filename. Based on WordPress
	static function unique_filename( $dir, $filename, $unique_filename_callback = null ) {
		$filename = strtolower( $filename );
		// separate the filename into a name and extension
		$info = pathinfo($filename);
		$ext = !empty($info['extension']) ? $info['extension'] : '';
		$name = basename($filename, ".{$ext}");

		// edge case: if file is named '.ext', treat as an empty name
		if( $name === ".$ext" )
			$name = '';

		// Increment the file number until we have a unique file to save in $dir. Use $unique_filename_callback if supplied.
		if ( $unique_filename_callback && function_exists( $unique_filename_callback ) ) {
			$filename = $unique_filename_callback( $dir, $name );
		} else {
			$number = 0;

			if ( !empty( $ext ) )
				$ext = strtolower( ".$ext" );

			// Strip % so the server doesn't try to decode entities.
			$s_name = str_replace('%', '', self::sanitize_file_name( $name ));
			
			$filename = $s_name . $ext;

			while ( file_exists( $dir . "/$filename" ) ) {
				if ( ! $number )
					$filename = $s_name . ++$number . $ext;
				else
					$filename = str_replace( "$number$ext", ++$number . $ext, $filename );
			}
		}

		return $filename;
	}
	
	//make sure new filename is simple and has standard characters
	static function sanitize_file_name( $name ) {
		$name = strtolower( $name );
		$name = Text::normalize( $name );
		$name = preg_replace('/&.+?;/', '', $name); // kill entities
		$name = str_replace( '_', '-', $name );
		$name = preg_replace('/[^a-z0-9\s-]/', '', $name);
		$name = preg_replace('/\s+/', '-', $name);
		$name = preg_replace('|-+|', '-', $name);
		$name = trim($name, '-');
		return $name;
	}
}
?>