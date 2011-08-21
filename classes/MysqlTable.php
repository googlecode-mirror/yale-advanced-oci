<?php
require_once 'classes/MysqlTableBase.php';

class MysqlTable extends MysqlTableBase
{
	public function __construct($mysqli, $tableName) {
		$this->tableName = $tableName;
		parent::__construct($mysqli);
	}
}