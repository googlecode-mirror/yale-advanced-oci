<?php
/**
 * Abstract class providing interface to access a MySQL table
 * 
 * @author User
 *
 */
abstract class MysqlTableBase
{
	/**
	 * Variables that are read-only.
	 * @var array
	 */
	private $readOnlyVars = array('error', 'errno', 'cookieFile', 'info', 'resultsExist', 'insertId', 'lastQuery');
	protected $error = '';
	protected $errno = 0;
	
	
	private $mysqli;
	private $info = array();
	private $conds = array();
	private $limit = array();
	private $orderBy = array();
	private $groupBy = array();
	private $selectColumns = array();
	private $result = false;
	private $resultsExist = false;
	private $insertId = -1;
	private $lastQuery = '';
	
	/**
	 * Name of the table; must be set in child class.
	 * @var string
	 */
	protected $tableName;
	
	
	/**
	 * Attaches MySQLi object to the wrapper.
	 * @param mysqli $mysqli
	 */
	public function __construct($mysqli) {
		$this->mysqli = $mysqli;
	}
	
	/**
	 * Clears the already-set info array.
	 */
	public function clearInfo() {
		$this->info = array();
		return true;
	}
	
	
	/**
	 * Adds a conditional to the SELECT parameters;
	 * All subgroups above 0 are elements of the 0th (top) subgroup and are
	 * combined with ORs, while all elements in each subgroup are combined
	 * with ANDs.
	 * @param string $column
	 * @param string $value
	 * @param int $subgroup optional; defaults to 0 (top group); must be > 0 if called from outside
	 * @param string $operator optional; defaults to '='
	 */
	public function addCond($column, $value, $subgroup = 0, $operator = '=') {
		$this->conds[$subgroup][] = array($column, $value, $operator);
		return true;
	}
	
	/**
	 * Sets limit for SELECT query.
	 * @param int $start
	 * @param int $limit optional; defaults to false (none)
	 */
	public function setLimit($start, $limit = false) {
		$this->limit['start'] = $start;
		$this->limit['limit'] = $limit;
		return true;
	}
	
	/**
	 * Adds an ORDER BY clause for the SELECT query.
	 * @param string $column
	 * @param string $direction optional; defaults to 'ASC'; can be 'ASC' or 'DESC'
	 */
	public function addOrderBy($column, $direction = 'ASC') {
		$this->orderBy[] = array($column, $direction);
		return true;
	}
	
	/**
	 * Adds a GROUP BY clase for the SELECT query.
	 * @param string $column
	 */
	public function addGroupBy($column) {
		$this->groupBy[] = $column;
		return true;
	}
	
	/**
	 * Sets columns to retrieve.
	 * @param mixed $columns can be string '*' as well as array of columns
	 */
	public function setColumns($columns) {
		if (is_array($columns) && !in_array('id', $columns)) {
			$columns[] = 'id';
		}
		$this->selectColumns = $columns;
		return true;
	}
	
	/**
	 * Clears the select conditions and results.
	 */
	public function clearSelect() {
		$this->conds = array();
		$this->limit = array();
		$this->orderBy = array();
		$this->groupBy = array();
		$this->selectColumns = '*';
		$this->result = null;
		$this->resultsExist = false;
		
		return true;
	}
	
	/**
	 * Constructs the WHERE clause string for the SELECT query.
	 */
	private function constructCondString() {
		$groupStrings = array();
		
		foreach ($this->conds as $group => $conditions) {
			foreach ($conditions as &$condition) {
				$condition = "`{$condition[0]}` {$condition[2]} '" . $this->mysqli->escape_string($condition[1]) . "'";
			}
			
			if ($group === 0) {
				$groupStrings[$group] = implode(' AND ', $conditions);
			} else {
				$groupStrings[$group] = implode(' OR ', $conditions);
			}
			
			if (count($conditions) > 1 && $group !== 0) {
				$groupStrings[$group] = "({$groupStrings[$group]})";
			}
		}
		
		return implode(' AND ', $groupStrings);
	}
	
	/**
	 * Constructs the LIMIT clause string for the SELECT query.
	 */
	private function constructLimitString() {
		$string = $this->limit['start'];
		if ($this->limit['limit'] !== false) {
			$string .= ", {$this->limit['limit']}";
		}
		return $string;
	}
	
	/**
	 * Constructs the ORDER BY clause string for the SELECT query.
	 */
	private function constructOrderByString() {
		$orderByStrings = array();
		foreach ($this->orderBy as &$clause) {
			$orderByStrings[] = "`{$clause[0]}` {$clause[1]}";
		}
		
		return implode(', ', $orderByStrings);
	}
	
	/**
	 * Constructs the GROUP BY clause string for the SELECT query.
	 */
	private function constructGroupByString() {
		return '`' . implode('`, `', $this->groupBy) . '`';
	}
	
	private function constructSelectColumnsString() {
		if (is_string($this->selectColumns)) {
			return $this->selectColumns;
		}
		
		return '`' . implode('`, `', $this->selectColumns) . '`';
	}
	
	public function constructSelectQuery() {
		if (empty($this->selectColumns)) {
			$this->setError(10001, 'constructSelectQuery() requires columns to retrieve to be specified');
			return false;
		}
		
		$selectColumnsString = $this->constructSelectColumnsString();
		$condString = '';
		$limitString = '';
		$orderByString = '';
		$groupByString = '';
		if (!empty($this->conds)) $condString = ' WHERE ' . $this->constructCondString();
		if (!empty($this->limit)) $limitString = ' LIMIT ' . $this->constructLimitString();
		if (!empty($this->orderBy)) $orderByString = ' ORDER BY ' . $this->constructOrderByString();
		if (!empty($this->groupBy)) $groupByString = ' GROUP BY ' . $this->constructGroupByString();
		
		return "SELECT {$selectColumnsString} FROM {$this->tableName}{$condString}{$orderByString}{$groupByString}{$limitString}";
	}
	
	public function executeSelectQuery() {
		$query = $this->constructSelectQuery();
		if ($query === false) {
			return false;
		}
		
		$this->result = $this->query($query);
		return ($this->result !== false);
	}
	
	public function nextItem() {
		$info = $this->result->fetch_assoc();
		if ($info === false) {
			$this->info = array();
			$this->resultsExist = false;
			return false;
		}
		
		$this->resultsExist = true;
		$this->info = $info;
		return true;
	}
	
	private function constructDeleteQuery() {
		if (empty($this->conds)) {
			$this->setError(10001, 'constructDeleteQuery() requires conditions');
			return false;
		}
		
		$condString = '';
		$limitString = '';
		if (!empty($this->conds)) $condString = ' WHERE ' . $this->constructCondString();
		if (!empty($this->limit)) $limitString = ' LIMIT ' . $this->constructLimitString();
		
		return "DELETE FROM {$this->tableName}{$condString}{$limitString}";
	}
	
	public function executeDeleteQuery() {
		$query = $this->constructDeleteQuery();
		if ($query === false) {
			return false;
		}
		
		$this->result = $this->query($query);
		return ($this->result !== false);
	}
	
	private $lowestCondGroupUsed = 0;
	
	private function addCondsForRetrieve($condColumns, $condValues) {
		// Single column, single value
		if (!is_array($condColumns) && !is_array($condValues)) {
			$this->addCond($condColumns, $condValues);
		
		// Single column, OR'd value
		} elseif (!is_array($condColumns) && is_array($condValues)) {
			--$this->lowestCondGroupUsed;
			foreach ($condValues as &$value) {
				if (is_array($value)) {
					$this->setError(10004, 'retrieve() cannot have more than 1 level of nested arrays in $condValues');
				}
				$this->addCond($condColumns, $value, $this->lowestCondGroupUsed);
			}
			
		// Multiple columns; recursive call
		} elseif (is_array($condColumns) && is_array($condValues)) {
			if (count($condColumns) !== count($condValues)) {
				$this->setError(10002, 'retrieve() with an array as $condColumn requires an equal-sized array as $condValues');
				return false;
			}
			
			reset($condColumns);
			reset($condValues);
			$column = each($condColumns);
			$column = $column['value'];
			$value = each($condValues);
			$value = $value['value'];
			
			while (!is_null($column)) {
				if (is_array($column)) {
					$this->setError(10003, 'retrieve() cannot have nested arrays in the $condColumns argument');
					return false;
				}
				$this->addCondsForRetrieve($column, $value);
				
				$column = each($condColumns);
				$column = $column['value'];
				$value = each($condValues);
				$value = $value['value'];
			}
			
		// Multiple columns, single value? Or neither. Weird.
		} else {
			$this->setError(10002, 'retrieve() with an array as $condColumn requires an equal-sized array as $condValues');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Retrieves rows fitting arguments; this is a shorthand to calling addCond(),
	 * setLimit(), and setColumns()
	 * @param string|array $condColumns No nested arrays allowed
	 * @param string|array $condValues
	 * @param array $columnsToRetrieve
	 * @param int $limit
	 */
	public function retrieve($condColumns, $condValues, $columnsToRetrieve = array('id'), $limit = 1) {
		$this->clearSelect();
		$this->lowestCondGroupUsed = 0;
		
		$success = $this->addCondsForRetrieve($condColumns, $condValues)
			&& $this->setColumns($columnsToRetrieve)
			&& $this->setLimit(0, $limit)
			&& $this->executeSelectQuery()
			&& $this->nextItem();
		
		return $success;
	}
	
	
	/**
	 * Given an array of column => value pairs, set the info, without removing
	 * already-set info.
	 * @param array $infoArray
	 */
	public function setInfoArray($infoArray) {
		$this->info = array_merge($this->info, $infoArray);
		return true;
	}
	
	/**
	 * Sets the info for given column.
	 * @param string $column
	 * @param string $value
	 */
	public function setInfo($column, $value) {
		$this->info[$column] = $value;
		return true;
	}
	
	/**
	 * Constructs the query string for the commit.
	 */
	public function constructCommitQuery() {
		$columnsString = '';
		$valuesString = '';
		$updateString = '';
		foreach ($this->info as $column => $value) {
			$value = $this->mysqli->escape_string($value);
			$columnsString .= "`{$column}`, ";
			$valuesString .= "'{$value}', ";
			$updateString .= "`{$column}` = '{$value}', ";
		}
		
		$columnsString = substr($columnsString, 0, -2);
		$valuesString = substr($valuesString, 0, -2);
		$updateString = substr($updateString, 0, -2);
		
		return "INSERT INTO `{$this->tableName}` ({$columnsString}) VALUES ({$valuesString}) ON DUPLICATE KEY UPDATE {$updateString}";
	}
	
	/**
	 * Commits the changed/new info-array as a row to the database.
	 */
	public function commit() {
		if (empty($this->info)) {
			return true;
		}
		$success = $this->query($this->constructCommitQuery());
		if ($success === true) {
			$this->insertId = $this->mysqli->insert_id;
		}
		return $success;
	}
	
	private function query($query) {
		$success = $this->mysqli->query($query);
		if ($success === false) {
			$this->setError($this->mysqli->error, $this->mysqli->errno);
		}
		$this->lastQuery = $query;
		return $success;
	}
	
	
	/*
	 * Standard base class functions.
	 */
	public function __get($name) {
		if (in_array($name, (array) $this->readOnlyVars)) {
			return $this->$name;
		}
		return null;
	}
	
	/**
	 * Sets internal error variables.
	 * @param int $errno
	 * @param string $error
	 */
	protected function setError($errno, $error) {
		$this->errno = $errno;
		$this->error = $error;
	}
}