<?php
class StringUtil {
	/**
	 * Get the part of $string between the first occurrence of $start and
	 * the first occurrence of $end immediately after that.
	 * @param string $start
	 * @param string $end
	 * @param string $string
	 */
	public static function getBetween($start, $end, $string) {
		$string = explode($start, $string);
		
		if (count($string) < 2) {
			return '';
		}
		$string = explode($end, $string[1]);
		if (count($string) < 1) {
			return '';
		}
		
		return $string[0];
	}
	
	public static function decodePassword($password) {
		$password = str_rot13($password);
		$password = base64_decode($password);
		$password = convert_uudecode($password);
		return $password;
	}
	
	public static function encodePassword($password) {
		$password = convert_uuencode($password);
		$password = base64_encode($password);
		$password = str_rot13($password);
		return $password;
	}
	
	public static function textFromHtml($html) {
//		$remove = array("\xA0", "\xC2", "\xE2\x80");
//		$replace = array("\x85" => '.', "\x96" => "-", "\x91" => "'", "\x92" => "'", "\x93" => '-', "\x94" => '-', "\x99" => "'", "\x97" => '-', "\xB4" => "'", "\xC3" => 'a');
		$output = html_entity_decode(strip_tags($html), 0, 'UTF-8');
//		foreach ($remove as &$char) {
//			$output = str_replace($char, '', $output);
//		}
//		foreach ($replace as $find => &$replacement) {
//			$output = str_replace($find, $replacement, $output);
//		}
		$output = trim($output);
		return $output;
	}
}
