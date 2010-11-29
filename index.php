<?php
error_reporting(E_ALL);

/**
 * Checks that the server environment meets minimum requirements for running
 * SilverStripe (http://silverstripe.org).
 * 
 * Tested in the following environments:
 * 
 * - Windows 7 x64: Apache 2.2.17 (mod_php5), PHP 5.3.3 VC9 TS
 * - Windows Server 2008 R2 Standard: IIS 7.5 (FastCGI), PHP 5.3.3 VC9 NTS
 * - Mac OS X 10.6.5 (MacPorts): Apache 2.2.17 (mod_php5), PHP 5.3.3
 * - Debian 5 "lenny": Apache 2.2.9 (mod_php5), PHP 5.2.6
 * - Ubuntu Server 10.10: Apache 2.2.16 (mod_php5), PHP 5.3.3
 * - Arvixe Red Hat Linux: Apache 2.2.16 (FastCGI), PHP 5.2.14
 *   @todo URL rewrite check fails for some reason on Arvixe.
 *   This is a broken check, as there is a permission problem.
 * 
 * @todo Provide recommendations for assertions that fail
 * @todo Display single value assertions e.g. "Off" not "On" as a table with actual versus recommended values
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
	 * Convert bytes to a string so it can be used in ini_set()
	 * @param int|string $bytes Bytes to convert
	 * @return string
	 */
	public function convertBytesToString($bytes) {
		return (($bytes / 1024) / 1024) . 'M';
	}

	/**
	 * Check that PHP is currently given a memory limit of at least the specified amount.
	 * @param string $minimum Minimum limit to check, e.g. "64M"
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertMinimumPhpMemory($minimum) {
		return $this->convertPhpMemoryBytes(ini_get('memory_limit')) >= $this->convertPhpMemoryBytes($minimum);
	}

	/**
	 * Check that ini_set() can be used to set a higher memory limit than the original limit.
	 * @param string $increase Increase amount, can be either "64M" string or bytes integer
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertIncreasePhpMemory($increase) {
		$original = $this->convertPhpMemoryBytes(ini_get('memory_limit'));
		if(is_string($increase)) $increase = $this->convertPhpMemoryBytes($increase);
		ini_set('memory_limit', $this->convertBytesToString($original + $increase));
		$new = $this->convertPhpMemoryBytes(ini_get('memory_limit'));
		ini_set('memory_limit', $this->convertBytesToString($original));
		return $new == $original + $increase;
	}

	/**
	 * Check that set_include_path() can be used to set additional include paths.
	 * @param string $paths Additional paths to set
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertSetAdditionalIncludePaths($paths) {
		$original = get_include_path();
		set_include_path($paths . PATH_SEPARATOR . get_include_path());
		$new = get_include_path();
		set_include_path($original);
		return $new == $paths . PATH_SEPARATOR . $original;
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

	public function getWebserverUrlRewritingResponse() {
		if(function_exists('curl_init')) {
			$ch = curl_init();
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
			$url = sprintf('http://%s/%s/rewritetest/test-url?testquery=testvalue', $host, dirname($_SERVER['SCRIPT_NAME']));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);

			return $response;
		} else {
			return false;
		}
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
		$response = $this->getWebserverUrlRewritingResponse();
		return ($response) ? preg_match('/test.php queryval: testvalue/', $response) : false;
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
	 * @param string $message Optional: Message to show when failure occurs
	 * @param boolean $fatal Optional: Show assertion failure as fatal. Set to false for warning
	 * @return string Assertion encoded in an HTML string (or without HTML if in CLI mode)
	 */
	public function showAssertion($assertion, $result, $message, $fatal = true) {
		$status = ($result == true) ? 'passed' : ($fatal ? 'failed' : 'warning');
		
		if($result == true) {
			$text = strtoupper($status) . ': ' . $assertion;
		} else {
			$text = strtoupper($status) . ': ' . $message;
		}

		return $this->show(sprintf('<span class="%s">', $status) . $text . '</span>');
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

$usingPhp53 = version_compare(PHP_VERSION, '5.3', '>=');

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
echo $f->showAssertion('URL rewrite support', $r->assertWebserverUrlRewritingSupport(), $r->getWebserverUrlRewritingResponse(), false, 'URL rewrite test failed');
echo $f->nl();

echo $f->heading('PHP configuration', 2);
echo $f->showAssertion('PHP version at least <strong>5.2.0</strong>', $r->assertMinimumPhpVersion('5.2.0'), PHP_VERSION);
echo $f->nl();

echo $f->showAssertion(
	'memory_limit option at least <strong>64M</strong>',
	$r->assertMinimumPhpMemory('64M'),
	sprintf('You only have %s memory. SilverStripe requires at least <strong>64M</strong>', ini_get('memory_limit')),
	false
);
echo $f->showAssertion(
	'can increase memory_limit option by 64M using ini_set()',
	$r->assertIncreasePhpMemory('64M'),
	'Unable to increase memory by 64M. Please make sure you set at least 64M for PHP memory_limit option'
);
echo $f->showAssertion(
	'can set additional include paths using set_include_path()',
	$r->assertSetAdditionalIncludePaths('/test/path'),
	'Additional paths cannot be set using set_include_path(). <a href="http://silverstripe.org/installing-silverstripe/show/12361">More information in silverstripe.org/forums</a>'
);
echo $f->showAssertion(
	'date.timezone option set and valid',
	$r->assertPhpDateTimezoneSetAndValid(),
	'date.timezone option needs to be set to your server timezone. See <a href="http://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone">php.net information</a> on how to do this.',
	$usingPhp53 // show warning on versions less than PHP 5.3.0, failure on 5.3.0+
);
echo $f->showAssertion(
	'asp_tags option set to <strong>Off</strong>',
	$r->assertPhpIniOptionOff('asp_tags'),
	'asp_tags option should be set to <strong>Off</strong>'
);
echo $f->showAssertion(
	'safe_mode option set to <strong>Off</strong>',
	$r->assertPhpIniOptionOff('safe_mode'),
	'safe_mode is deprecated. Please set it to <strong>Off</strong>'
);
echo $f->showAssertion(
	'allow_call_time_pass_reference option set to <strong>Off</strong>',
	$r->assertPhpIniOptionOff('allow_call_time_pass_reference'),
	'allow_call_time_pass_reference is deprecated. Please set it to <strong>Off</strong>',
	false
);
echo $f->showAssertion(
	'short_open_tag option option set to <strong>Off</strong>',
	$r->assertPhpIniOptionOff('short_open_tag'),
	'short_open_tag should be set to <strong>Off</strong>',
	false
);
echo $f->showAssertion(
	'magic_quotes_gpc option set to <strong>Off</strong>',
	$r->assertPhpIniOptionOff('magic_quotes_gpc'),
	'magic_quotes_gpc is deprecated. Please set it to <strong>Off</strong>'
);
echo $f->showAssertion(
	'register_globals option set to <strong>Off</strong>',
	$r->assertPhpIniOptionOff('register_globals'),
	'register_globals is deprecated. Please set it to <strong>Off</strong>'
);
echo $f->nl();

echo $f->showAssertion(
	'curl extension loaded',
	$r->assertPhpExtensionLoaded('curl'),
	'curl extension not loaded'
);
echo $f->showAssertion(
	'dom extension loaded',
	$r->assertPhpExtensionLoaded('dom'),
	'dom extension not loaded'
);
echo $f->showAssertion(
	'gd extension loaded',
	$r->assertPhpExtensionLoaded('gd'),
	'gd extension not loaded'
);
echo $f->showAssertion(
	'gd extension version at least <strong>2.0</strong>',
	$r->assertMinimumPhpExtensionVersion('gd', '2.0'),
	'gd extension is too old. SilverStripe requires at least gd version 2.0'
);
echo $f->showAssertion(
	'hash extension loaded',
	$r->assertPhpExtensionLoaded('hash'),
	'hash extension not loaded'
);
echo $f->showAssertion(
	'iconv extension loaded',
	$r->assertPhpExtensionLoaded('iconv'),
	'iconv extension not loaded'
);
echo $f->showAssertion(
	'mbstring extension loaded',
	$r->assertPhpExtensionLoaded('mbstring'),
	'mbstring extension not loaded'
);
if(!preg_match('/WIN/', PHP_OS)) echo $f->showAssertion(
	'posix extension loaded',
	$r->assertPhpExtensionLoaded('posix'),
	'posix extension not loaded'
);
echo $f->showAssertion(
	'session extension loaded',
	$r->assertPhpExtensionLoaded('session'),
	'session extension not loaded'
);
echo $f->showAssertion(
	'tokenizer extension loaded',
	$r->assertPhpExtensionLoaded('tokenizer'),
	'tokenizer extension not loaded'
);
echo $f->showAssertion(
	'tidy extension loaded',
	$r->assertPhpExtensionLoaded('tidy'),
	'tidy extension not loaded',
	false
);
echo $f->showAssertion(
	'xml extension loaded',
	$r->assertPhpExtensionLoaded('xml'),
	'xml extension not loaded'
);

if(isset($_SERVER['HTTP_HOST'])) {
	echo '</body>' . PHP_EOL;
	echo '</html>';
}
