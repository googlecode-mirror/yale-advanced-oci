<?php
/**
 * Wraps CURL session into a class
 * 
 * @author User
 */
class Curl
{
	/**
	 * Variables that are read-only.
	 * @var array
	 */
	private $readOnlyVars = array('error', 'errno', 'cookieFile', 'storeReferer', 'debug');
	protected $error = '';
	protected $errno = 0;
	
	
	const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-GB; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
	const MAX_RETRIES = 3;
	const TIMEOUT = 15;
	private $debug = false;
	private $storeReferer = false;
	private $curlHandle;
	protected $cookieFile = '';
	
	
	public function __construct() {
		$this->curlHandle = curl_init();
		$this->setCurlOpt(CURLOPT_USERAGENT, self::DEFAULT_USER_AGENT);
		$this->setCurlOpt(CURLOPT_SSL_VERIFYHOST, false);
		$this->setCurlOpt(CURLOPT_SSL_VERIFYPEER, false);
		$this->setCurlOpt(CURLOPT_RETURNTRANSFER, true);
		$this->setCurlOpt(CURLOPT_FOLLOWLOCATION, true);
		$this->setCurlOpt(CURLOPT_TIMEOUT, self::TIMEOUT);
		$this->setCurlOpt(CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
	}
	
	
	/**
	 * Set whether to enable debug mode for cURL.
	 * @param bool $setting
	 */
	public function setDebug($setting) {
		$setting = (bool) $setting;
		$success = $this->setcurlOpt(CURLOPT_VERBOSE, $setting);
		if ($success === true) {
			$this->debug = $setting;
		}
		return $success;
	}
	
	/**
	 * Set whether to store the Referer from past requests.
	 * @param bool $setting
	 * @return bool $success
	 */
	public function setStoreReferer($setting) {
		$this->storeReferer = (bool) $setting;
		return true;
	}
	
	/**
	 * Set the Referer: field of HTTP requests.
	 * @param string $referer
	 */
	public function setReferer($referer) {
		return $this->setCurlOpt(CURLOPT_REFERER, $referer);
	}
	
	/**
	 * Sets the file the cookies are placed and taken from.
	 * @param string $filePath
	 * @return bool $success
	 */
	public function setCookieFile($filePath) {
		$success = $this->setCurlOpt(CURLOPT_COOKIEJAR, $filePath) &&
			$this->setCurlOpt(CURLOPT_COOKIEFILE, $filePath);
		
		if ($success === true) {
			$this->cookieFile = $filePath;
		}
		return $success;
	}
	
	/**
	 * Get the CURLOPTS used for the last fetch.
	 */
	public function getLastFetchCurlOpts() {
		return curl_getinfo($this->curlHandle);
	}
	
	/**
	 * Fetch page given URL.
	 * @param string $url
	 * @param array $postFields		Leave null if request is GET.
	 * @param bool $withHeaders
	 * @return mixed $page
	 */
	public function fetchPage($url, $postFields, $withHeaders) {
		$this->setCurlOpt(CURLOPT_URL, $url);
		if (!empty($postFields)) {
			$this->setCurlOpt(CURLOPT_POST, true);
			$this->setCurlOpt(CURLOPT_POSTFIELDS, $this->refinePostFields($postFields));
		} else {
			$this->setCurlOpt(CURLOPT_HTTPGET, true);
		}
		
		if ($withHeaders === true) {
			$this->setCurlOpt(CURLOPT_HEADER, true);
		} else {
			$this->setCurlOpt(CURLOPT_HEADER, false);
		}

		$try = 0;
		$output = $this->fetchExecuteOutput();
		while ($output === false && $try <= self::MAX_RETRIES) {
			++$try;
			$output = $this->fetchExecuteOutput();
		}
		
		if ($output === false) {
			$this->updateErrorVars();
		}
		
		if ($output !== false && $this->storeReferer === true) {
			$this->setReferer($url);
		}
		return $output;
	}
	
	/**
	 * Set the specified CURL option with curl_setopt.
	 * @param int $option
	 * @param mixed $value
	 * @return bool $success
	 */
	private function setCurlOpt($option, $value) {
		$success = curl_setopt($this->curlHandle, $option, $value);
		if ($success === false) {
			$this->updateErrorVars();
		}
		return $success;
	}
	
	/**
	 * Updates the class's error variables from the CURL handle.
	 */
	private function updateErrorVars() {
		$this->error = curl_error($this->curlHandle);
		$this->errno = curl_errno($this->curlHandle);
	}
	
	private function fetchExecuteOutput() {
		return curl_exec($this->curlHandle);
	}
	
	/**
	 * Refines the post-fields arra or string into a post-string so that
	 * the MimeType is stated to be x-www-form-urlencoded when the string is
	 * used as the value of CURLOPT_POSTFIELDS
	 * 
	 * @param mixed $postFields
	 */
	private function refinePostFields($postFields) {
		if (is_array($postFields)) {
			$urlFields = array();
			foreach ($postFields as $key => &$value) {
				$urlValue = htmlspecialchars($value);
				$urlFields[] = "{$key}={$urlValue}";
			}
		
			return implode('&', $urlFields);
		}
		
		return $postFields;
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