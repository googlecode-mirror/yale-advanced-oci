<?php
require_once 'classes/Curl.php';
require_once 'classes/StringUtil.php';

class YaleCurl extends Curl {
	private $readOnlyVars = array('autoLoginUsername', 'autoLoginPassword');
	/*
	 * Read-only variables code and declaration
	 */
	public function __get($name) {
		if (in_array($name, (array) $this->readOnlyVars)) {
			return $this->$name;
		}
		return parent::__get($name);
	}
	
	
	const LOGIN_PAGE = 'https://secure.its.yale.edu/cas/login';
	const LOGIN_PAGE_SUBSTRING = 'You may establish Yale authentication now in order to access';
	private $autoLoginUsername = '';
	private $autoLoginPassword = '';
	
	
	/**
	 * Checks if the page is a login prompt.
	 */
	private static function isPageLoginPrompt($page) {
		return (strpos($page, self::LOGIN_PAGE_SUBSTRING) !== false);	
	}
	
	/**
	 * Gets the value of the lt form field on login prompt pages.
	 * Enter description here ...
	 * @param unknown_type $site
	 */
	private function getLtValue($site) {
		$page = $this->fetchPage($this->makeLoginUrl($site), null, true);
		if (strpos($page, 'Login Successful') !== false ||
				strpos($page, '302') !== false) {
			return false;
		}
		return StringUtil::getBetween('name="lt" value="', '"', $page);
	}
	
	private function makeLoginUrl($site) {
		if (!empty($site) && is_string($site)) {
			return self::LOGIN_PAGE . '?service=' . htmlspecialchars($site);
		}
		return self::LOGIN_PAGE;
	}
	
	/**
	 * Checks if logged into Yale interface already.
	 */
	public function isLoggedIn($site) {
		if ($this->getLtValue($site) === false) {
			return true;
		}
		return false;
	}
	
	/**
	 * Attempts a login through Yale's CAS.
	 * @param string $username
	 * @param string $password
	 * @param string $site URL to log into
	 * @return bool
	 */
	public function attemptLogin($username, $password, $site) {
		if (empty($this->cookieFile)) {
			echo $this->cookieFile . "\n";
			$this->setError(0, 'A cookie file must be set to log in.');
			return false;
		}
		
		$ltValue = $this->getLtValue($site);
		// Already logged in
		if ($ltValue === false) {
			return true;
		}
		
		$page = $this->fetchPage($this->makeLoginUrl($site),
			array('_eventId' => 'submit', 'lt' => $ltValue,
			'username' => $username, 'password' => $password), false);
		
		if (stripos($page, 'Login required') !== false) {
			$this->setError(0, 'The login credentials did not work.');
			return false;
		}
		
		return true;
	}
	

	/**
	 * Fetch page given URL.
	 * @param string $url
	 * @param array $postFields		Leave null if request is GET.
	 * @param bool $withHeaders
	 * @return mixed $page
	 */
	public function fetchPageAndLogin($url, $postFields, $withHeaders, $autoLogin) {
		$page = $this->fetchPage($url, $postFields, $withHeaders);
		
		// Don't do anything if we aren't using autologin or don't need to login
		if (!empty($this->autoLoginUsername) && self::isPageLoginPrompt($page)) {
			$success = $this->attemptLogin($this->autoLoginUsername, $this->autoLoginPassword, $url);
			if (!$success) {
				return false;
			}
			$page = $this->fetchPage($url, $postFields, $withHeaders);
		}
		
		return $page;
	}
	
	/**
	 * Sets login parameters to automatically log into Yale CAS when necessary.
	 * @param string $username
	 * @param string $password
	 */
	public function setAutoLoginParameters($username, $password) {
		$this->autoLoginUsername = $username;
		$this->autoLoginPassword = $password;
		return true;
	}
}