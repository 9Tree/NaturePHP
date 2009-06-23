<?php
#/*
#* 9Tree Measures Class - v0.3.5
#* Measures, conversions, number formats, etc.
#*/

class Format{
	
	//auto-format byte size
	static function byte_size($bytes, $decimals = 0) {
		// technically the correct unit names for powers of 1024 are KiB, MiB etc
		// see http://en.wikipedia.org/wiki/Byte
		$quant = array(
			'TB' => pow(1024, 4),
			'GB' => pow(1024, 3),
			'MB' => pow(1024, 2),
			'kB' => pow(1024, 1),
			'B'  => pow(1024, 0)
		);

		foreach ($quant as $unit => $mag)
			if ( intval($bytes) >= $mag )
				return self::number_i18n($bytes / $mag, $decimals) . ' ' . $unit;
	}
	
	//number format in i18n (eg. 10.000,05)
	static function number_i18n($number, $decimals = 0) {
		$decimal_point=",";
		$thousands_sep=".";
		return number_format($number, $decimals, $decimal_point, $thousands_sep);
	}
}
?>