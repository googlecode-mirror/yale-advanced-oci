<?php
class ProjectFunctions {
	public static function assertAndPrint($result, $print) {
		if ($result === false) {
			print_r($print);
			print_r(debug_backtrace());
			exit;
		}
	}
	
	public static function createMysqli() {
		return new mysqli(MYSQL_HOST, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DATABASE);
	}
}
