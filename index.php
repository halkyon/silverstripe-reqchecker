<?php
error_reporting(E_ALL);

/**
 * Checks that the server environment meets minimum requirements for running
 * SilverStripe (http://silverstripe.org).
 * 
 * @todo Check set_include_path() can be used (some hosts disable this)
 * @todo Check ini_set('memory_limit') will actually increase memory limit
 * @todo Provide recommendations for assertions that fail
 * 
 * Has been tested on the following environments:
 * - Windows 7 x64: Apache 2.2.17 (mod_php5), PHP 5.3.4 RC1
 * - Mac OS X 10.6.5: Apache 2.2.17 (mod_php5), PHP 5.3.3
 * - Debian 5 "lenny": Apache 2.2.9 (mod_php5), PHP 5.2.6
 * - Ubuntu Server 10.10: Apache 2.2.16 (mod_php5), PHP 5.3.3
 * 
 * @package ssreqcheck
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class RequirementsChecker {

	/**
	 * Cached PHP information, populated on instantiation of this class.
	 * @var array
	 */
	protected $phpInfo;

	public function __construct() {
		$this->getPhpInfo();
	}

	/**
	 * Retrieves PHP information from phpinfo() into an array.
	 * @todo This doesn't work when PHP is running in CLI.
	 * 
	 * @author code at adspeed dot com 09-Dec-2005 11:31
	 * @see http://php.net/manual/en/function.phpinfo.php
	 * @return array
	 */
	public function getPhpInfo() {
		if($this->phpInfo) return $this->phpInfo;

		ob_start();
		phpinfo();
		$s = ob_get_contents();
		ob_end_clean();
		$s = strip_tags($s, '<h2><th><td>');
		$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $s);
		$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $s);
		$vTmp = preg_split('/(<h2>[^<]+<\/h2>)/',$s, -1, PREG_SPLIT_DELIM_CAPTURE);
		$data = array();

		for($i = 1; $i < count($vTmp); $i++) {
			if(preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) {
				$vName = trim($vMat[1]);
				$vTmp2 = explode("\n", $vTmp[$i+1]);
				foreach($vTmp2 as $vOne) {
					$vPat = '<info>([^<]+)<\/info>';
					$vPat3 = "/$vPat\s*$vPat\s*$vPat/";
					$vPat2 = "/$vPat\s*$vPat/";
					if(preg_match($vPat3, $vOne, $vMat)) { // 3cols
						$data[$vName][trim($vMat[1])] = array(trim($vMat[2]), trim($vMat[3]));
					} elseif(preg_match($vPat2, $vOne, $vMat)) { // 2cols
						$data[$vName][trim($vMat[1])] = trim($vMat[2]);
					}
				}
			}
		}

		$this->phpInfo = $data;

		return $this->phpInfo;
	}

	/**
	 * Check that a php.ini option is set to "<strong>Off</strong>"
	 * ini_get() returns as <strong>Off</strong> settings as an empty string.
	 * 
	 * @param string $name Name of configuration setting
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertPhpIniOptionOff($option) {
		return ini_get($option) == false;
	}

	/**
	 * Check that the current PHP version is of at least the version specified.
	 * 
	 * @param mixed $version Minimum version of PHP to assert
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertMinimumPhpVersion($version) {
		return version_compare($version, PHP_VERSION, '<=');
	}

	/**
	 * Check that a PHP extension has been loaded.
	 * 
	 * @param string $name Name of extension, e.g. "gd"
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertPhpExtensionLoaded($name) {
		return extension_loaded($name);
	}

	/**
	 * Check that a PHP class exists.
	 * 
	 * @param string $name Name of class, e.g. "DOMDocument"
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertPhpClassExists($name) {
		return class_exists($name);
	}
	
	/**
	 * Try to find the version of the given PHP extension.
	 * 
	 * @param string $name Name of extension, e.g. "gd"
	 * @return string|false String of version, boolean FALSE on failure
	 */
	public function getPhpExtensionVersion($name) {
		$info = $this->getPhpInfo();
		$version = false;

		if(isset($info[$name])) {
			// find a key with "version" text and get that value
			$found = '';
			foreach($info[$name] as $key => $value) {
				if(preg_match('/version/i', $key)) {
					$found = $key;
					break;
				}
			}

			if(isset($info[$name][$found])) {
				preg_match('/\d+\.\d+(?:\.\d+)?/', $info[$name][$found], $matches);
				if(isset($matches[0])) $version = $matches[0];
				elseif(is_numeric($info[$name][$found])) $version = $info[$name][$found];
			}
		}

		return $version;
	}

	/**
	 * Check a loaded PHP extension is of at least the version specified.
	 * @param string $name Name of extension to check, e.g. "gd"
	 * @param mixed $version Minimum version of extension to assert
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertMinimumPhpExtensionVersion($name, $version) {
		return version_compare($version, $this->getPhpExtensionVersion($name, $version), '<=');
	}

	/**
	 * Convert given memory limit into bytes.
	 * 
	 * @param string $mem Existing memory limit e.g. "64M" to convert to bytes
	 * @return int Memory limit in bytes
	 */
	public function convertPhpMemoryBytes($mem) {
		switch(strtolower(substr($mem, -1))) {
			case 'k':
				return round(substr($mem, 0, -1) * 1024);
				break;
			case 'm':
				return round(substr($mem, 0, -1) * 1024 * 1024);
				break;
			case 'g':
				return round(substr($mem, 0, -1) * 1024 * 1024 * 1024);
				break;
			default:
				return round($mem);
		}
	}

	/**
	 * Check that PHP is currently given a memory limit of at least the specified amount.
	 * @param string $minimum Minimum limit to check, e.g. "64M"
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertMinimumPhpMemory($minimum) {
		return ($this->convertPhpMemoryBytes(ini_get('memory_limit')) >= $this->convertPhpMemoryBytes($minimum)) ? true : false;
	}

	/**
	 * Check that the date.timezone PHP configuration option has been set
	 * to a valid timezone identifier.
	 * 
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertPhpDateTimezoneSetAndValid() {
		return ini_get('date.timezone') && in_array(ini_get('date.timezone'), timezone_identifiers_list());
	}

	/**
	 * Determine if URL rewriting support is working on the webserver.
	 * 
	 * Rather than doing specific checks for Apache or IIS, this method will
	 * check the response of a specific test URL in order to make a determination
	 * of URL rewriting is working or not.
	 * 
	 * @todo This doesn't work when PHP is running in CLI.
	 * 
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertWebserverUrlRewritingSupport() {
		if(function_exists('curl_init')) {
			$ch = curl_init();
			$url = sprintf('http://127.0.0.1/%s/rewritetest/test-url?testquery=testvalue', dirname($_SERVER['SCRIPT_NAME']));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);

			return preg_match('/test.php queryval: testvalue/', $response);
		} else {
			return false;
		}
	}

}
/**
 * Simple abstraction class which formats text based on whether PHP is running
 * under the command line or from a web browser.
 * 
 * @package ssreqcheck
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class RequirementsFormatter {

	/**
	 * Is PHP currently running as CLI?
	 * @return boolean TRUE yes | FALSE no
	 */
	protected function isCli() {
		return !isset($_SERVER['HTTP_HOST']);
	}

	/**
	 * Return a message, along with system EOL character.
	 * If in CLI, strip all HTML tags that may be present in message.
	 * 
	 * @param string $message Message to show
	 * @return processed message to show
	 */
	public function show($message) {
		return ($this->isCli()) ? strip_tags($message) . PHP_EOL : $message . '<br>' . PHP_EOL;
	}

	/**
	 * Return a message from an assertion suitable for someone to read.
	 * 
	 * @param string $name Name of the assertion made, e.g. "PHP memory at least 64M"
	 * @param boolean $result Boolean result of assertion, either TRUE passed or FALSE failed
	 * @param string $message Optional: Show summary of the assertion next to name
	 * @param boolean $fatal Optional: Show assertion failure as fatal. Set to false for warning
	 * @param string $tag Optional: HTML tag to use. By default, it's <span>
	 * @return string Assertion encoded in an HTML string (or without HTML if in CLI mode)
	 */
	public function showAssertion($name, $result, $message = '', $fatal = true, $tag = 'span') {
		$status = ($result == true) ? 'passed' : ($fatal ? 'failed' : 'warning');
		$result = strtoupper($status) . ': ' . $name . ($message ? sprintf(' (%s)', $message) : '');
		return $this->show(($tag ? sprintf('<%s class="%s">', $tag, $status) : '') . $result . ($tag ? sprintf('</%s>', $tag) : ''));
	}

	/**
	 * Return a text shown as a header.
	 * 
	 * @param string $text Text to show for header
	 * @param int $level Level of header as an integer
	 * @return processed message to show
	 */
	public function heading($text, $level = 1) {
		if($this->isCli()) {
			return '** ' . $text . ' **' . PHP_EOL;
		} else {
			return sprintf('<h%d>', $level) . $text . sprintf('</h%d>', $level) . PHP_EOL;
		}
	}

	/**
	 * Show a newline character with a <br> HTML element.
	 * If in CLI, just show a newline character.
	 * 
	 * @return processed message to show
	 */
	public function nl() {
		return ($this->isCli()) ? PHP_EOL : '<br>' . PHP_EOL;
	}

}

$r = new RequirementsChecker();
$f = new RequirementsFormatter();

if(isset($_SERVER['HTTP_HOST'])) {
	echo '<html>' . PHP_EOL;
	echo '<head>' . PHP_EOL;
	echo '<title>SilverStripe Requirements</title>' . PHP_EOL;
	echo '<style type="text/css">' . PHP_EOL;
	echo '@import url("styles.css");' . PHP_EOL;
	echo '</style>' . PHP_EOL;
	echo '</head>' . PHP_EOL;
	echo '<body>' . PHP_EOL;
}

echo $f->heading('SilverStripe Requirements Checker', 1);

echo $f->heading('System information', 2);
echo $f->show(sprintf('System: %s', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'));
echo $f->show(sprintf('Server Software: %s', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'));
echo $f->show(sprintf('Server API: %s', php_sapi_name()));
echo $f->show(sprintf('PHP Version: %s', PHP_VERSION));
echo $f->show(sprintf('PHP configuration file path: %s', get_cfg_var('cfg_file_path')));
echo $f->nl();

echo $f->heading('Webserver configuration', 2);
echo $f->showAssertion('URL rewriting enabled', $r->assertWebserverUrlRewritingSupport());
echo $f->nl();

echo $f->heading('PHP configuration', 2);
echo $f->showAssertion('PHP version at least <strong>5.2.0</strong>', $r->assertMinimumPhpVersion('5.2.0'), PHP_VERSION);
echo $f->nl();
echo $f->showAssertion('memory_limit at least <strong>64M</strong>', $r->assertMinimumPhpMemory('64M'), ini_get('memory_limit'));
echo $f->showAssertion('date.timezone option set and valid', $r->assertPhpDateTimezoneSetAndValid(), ini_get('date.timezone'));
echo $f->showAssertion('asp_tags option set to <strong>Off</strong>', $r->assertPhpIniOptionOff('asp_tags'), ini_get('asp_tags') ? 'On' : '');
echo $f->showAssertion('safe_mode set to <strong>Off</strong>', $r->assertPhpIniOptionOff('safe_mode'), ini_get('safe_mode') ? 'On' : '');
echo $f->showAssertion('allow_call_time_pass_reference option set to <strong>Off</strong>', $r->assertPhpIniOptionOff('allow_call_time_pass_reference'), ini_get('allow_call_time_pass_reference') ? 'On' : '');
echo $f->showAssertion('short_open_tag option option set to <strong>Off</strong>', $r->assertPhpIniOptionOff('short_open_tag'), ini_get('short_open_tag') ? 'On' : '', false);
echo $f->showAssertion('magic_quotes_gpc option set to <strong>Off</strong>', $r->assertPhpIniOptionOff('magic_quotes_gpc'), ini_get('magic_quotes_gpc') ? 'On' : '');
echo $f->showAssertion('register_globals option set to <strong>Off</strong>', $r->assertPhpIniOptionOff('register_globals'), ini_get('register_globals') ? 'On' : '');
echo $f->showAssertion('session.auto_start option set to <strong><strong>Off</strong></strong>', $r->assertPhpIniOptionOff('session.auto_start'), ini_get('session.auto_start') ? 'On' : '');
echo $f->nl();
echo $f->showAssertion('curl extension loaded', $r->assertPhpExtensionLoaded('curl'));
echo $f->showAssertion('dom extension loaded', $r->assertPhpExtensionLoaded('dom'));
echo $f->showAssertion('gd extension loaded', $r->assertPhpExtensionLoaded('gd'));
echo $f->showAssertion('gd extension version at least <strong>2.0</strong>', $r->assertMinimumPhpExtensionVersion('gd', '2.0'), $r->getPhpExtensionVersion('gd'));
echo $f->showAssertion('hash extension loaded', $r->assertPhpExtensionLoaded('hash'));
echo $f->showAssertion('iconv extension loaded', $r->assertPhpExtensionLoaded('iconv'));
echo $f->showAssertion('mbstring extension loaded', $r->assertPhpExtensionLoaded('mbstring'));
if(!preg_match('/WIN/', PHP_OS)) echo $f->showAssertion('posix extension loaded', $r->assertPhpExtensionLoaded('posix'));
echo $f->showAssertion('session extension loaded', $r->assertPhpExtensionLoaded('session'));
echo $f->showAssertion('tokenizer extension loaded', $r->assertPhpExtensionLoaded('tokenizer'));
echo $f->showAssertion('tidy extension loaded', $r->assertPhpExtensionLoaded('tidy'));
echo $f->showAssertion('xml extension loaded', $r->assertPhpExtensionLoaded('xml'));
echo $f->nl();
echo $f->showAssertion('DOMDocument class exists', $r->assertPhpClassExists('DOMDocument'));
echo $f->showAssertion('SimpleXMLElement class exists', $r->assertPhpClassExists('SimpleXMLElement'));

if(isset($_SERVER['HTTP_HOST'])) {
	echo '</body>' . PHP_EOL;
	echo '</html>';
}
