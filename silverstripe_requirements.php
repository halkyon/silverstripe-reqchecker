<?php
/**
 * Checks that the server environment meets minimum requirements for running
 * SilverStripe (http://silverstripe.org).
 * 
 * So far, this only checks PHP configuration, but it could be extended into
 * checking other things such as Apache (e.g. "AllowOverride All" has been set),
 * and MySQL database configuration, as well as file permissions.
 * 
 * @todo Check set_include_path() can be used (some hosts disable this)
 * @todo Check memory limit is at least 64M
 * @todo Check ini_set('memory_limit') will actually increase memory limit
 * 
 * Has been tested on the following environments:
 * - Windows 7 x64: Apache 2.2.17 (mod_php5), PHP 5.3.4 RC1
 * - Mac OS X 10.6.5: Apache 2.2.17 (mod_php5), PHP 5.3.3
 * 
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
	protected function getPhpInfo() {
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
	 * Check that a php.ini option is set to "Off"
	 * ini_get() returns as Off settings as an empty string.
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

}

class RequirementsFormatter {

	function isCli() {
		return !isset($_SERVER['HTTP_HOST']);
	}

	public function show($message) {
		return ($this->isCli()) ? strip_tags($message) . PHP_EOL : $message . '<br>' . PHP_EOL;
	}

	public function showAssertion($name, $result, $message = '', $tag = 'span') {
		$status = ($result == true) ? 'passed' : 'failed';
		$result = strtoupper($status) . ': ' . $name . ($message ? sprintf(' (%s)', $message) : '');
		return $this->show(($tag ? sprintf('<%s class="%s">', $tag, $status) : '') . $result . ($tag ? sprintf('</%s>', $tag) : ''));
	}

	public function heading($text, $level = 1) {
		if($this->isCli()) {
			return '** ' . $text . ' **' . PHP_EOL;
		} else {
			return sprintf('<h%d>', $level) . $text . sprintf('</h%d>', $level) . PHP_EOL;
		}
	}

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

echo $f->heading('PHP configuration', 2);

echo $f->heading('PHP version', 3);
echo $f->showAssertion('PHP version at least 5.2.0', $r->assertMinimumPhpVersion('5.2.0'), PHP_VERSION);
echo $f->nl();

echo $f->heading('PHP configuration options', 3);
echo $f->showAssertion('asp_tags option set to Off', $r->assertPhpIniOptionOff('asp_tags'));
echo $f->showAssertion('safe_mode set to Off', $r->assertPhpIniOptionOff('safe_mode'));
echo $f->showAssertion('allow_call_time_pass_reference option set to Off', $r->assertPhpIniOptionOff('allow_call_time_pass_reference'));
echo $f->showAssertion('short_open_tag option option set to Off', $r->assertPhpIniOptionOff('short_open_tag'));
echo $f->showAssertion('magic_quotes_gpc option set to Off', $r->assertPhpIniOptionOff('magic_quotes_gpc'));
echo $f->showAssertion('register_globals option set to Off', $r->assertPhpIniOptionOff('register_globals'));
echo $f->showAssertion('session.auto_start option set to Off', $r->assertPhpIniOptionOff('session.auto_start'));
echo $f->nl();

echo $f->heading('PHP extensions', 3);
echo $f->showAssertion('curl extension loaded', $r->assertPhpExtensionLoaded('curl'));
echo $f->showAssertion('dom extension loaded', $r->assertPhpExtensionLoaded('dom'));
echo $f->showAssertion('gd extension loaded', $r->assertPhpExtensionLoaded('gd'));
echo $f->showAssertion('gd extension is at least version 2.0', $r->assertMinimumPhpExtensionVersion('gd', '2.0'), $r->getPhpExtensionVersion('gd'));
echo $f->showAssertion('iconv extension loaded', $r->assertPhpExtensionLoaded('iconv'));
echo $f->showAssertion('mbstring extension loaded', $r->assertPhpExtensionLoaded('mbstring'));
if(!preg_match('/WIN/', PHP_OS)) echo $f->showAssertion('posix extension loaded', $r->assertPhpExtensionLoaded('posix'));
echo $f->showAssertion('tidy extension loaded', $r->assertPhpExtensionLoaded('tidy'));
echo $f->showAssertion('xml extension loaded', $r->assertPhpExtensionLoaded('xml'));
echo $f->nl();

echo $f->heading('PHP core classes', 3);
echo $f->showAssertion('DOMDocument exists', $r->assertPhpClassExists('DOMDocument'));
echo $f->showAssertion('SimpleXMLElement exists', $r->assertPhpClassExists('SimpleXMLElement'));

if(isset($_SERVER['HTTP_HOST'])) {
	echo '</body>' . PHP_EOL;
	echo '</html>';
}
