<?php
class MysqliUtil
{
	/**
	 * Sets the charset for MySQLi.
	 * @param mysqli $mysqli
	 */
	public static function prepareMysqli(&$mysqli) {
		$mysqli->set_charset('utf8');
		$mysqli->query("SET NAMES 'utf8'");
	}
}