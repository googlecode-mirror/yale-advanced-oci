<?php
class Log {

	const FLAG_ECHO = 1;
	private $filename = '', $log_path = '';
	private $log_level = E_ALL;
	private static $level_labels = array(E_WARNING => 'Warning',
		E_ERROR => 'Error', E_NOTICE => 'Notice');
	


	public function setFile($file) {
		$this->filename = $file;
		return true;
	}
	
	public function setLogLevel($logLevel) {
		if (is_int($logLevel)) {
			$this->log_level = $logLevel;
			return true;
		}
		
		return false;
	}
		

	public function __construct($file, $log_path) {
		$this->log_path = $log_path;
		$this->filename = $file;
	}
	
	
	public function write($message, $level = E_NOTICE, $flags = self::FLAG_ECHO) {
		// Returns if level not valid
		if (empty(self::$level_labels[$level]))
			return false;
			
		$level_label = self::$level_labels[$level];
		
		$write = '[' . date('m/d H:i:s') . "] [{$level_label}] {$message}\n";
		
		$ret = true;
		if (!empty($this->filename) && ($level & $this->log_level)) {
			$ret = file_put_contents($this->log_path . '/' . $this->filename, $write,
				FILE_APPEND | FILE_TEXT);
		}
		
		if ($flags & self::FLAG_ECHO) {
			echo $write;
		}
		
		return $ret;
	}

}