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
 * @todo ini_set('memory_limit') will actually increase memory limit
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
	 * @param mixed $version Minimum version of PHP to assert
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertMinimumPhpVersion($version) {
		return version_compare($version, PHP_VERSION, '<=');
	}

	/**
	 * Check a loaded PHP extension is of at least the version specified.
	 * @param string $name Name of extension to check, e.g. "gd"
	 * @param mixed $version Minimum version of extension to assert
	 * @return boolean TRUE passed assertion | FALSE failed assertion
	 */
	public function assertMinimumPhpExtensionVersion($name, $version) {
		$info = $this->getPhpInfo();
		$foundvers = null;

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
				if(isset($matches[0])) $foundvers = $matches[0];
				elseif(is_numeric($info[$name][$found])) $foundvers = $info[$name][$found];
			}
		}

		return version_compare($version, $foundvers, '<=');
	}

}

class RequirementsFormatter {

	function isCli() {
		return !isset($_SERVER['HTTP_HOST']);
	}

	public function show($message) {
		if($this->isCli()) {
			return strip_tags($message) . PHP_EOL;
		} else {
			return $message . '<br>' . PHP_EOL;
		}
	}

	public function showAssertion($name, $message = '', $tag = 'span') {
		$result = '';
		if(is_bool($message) && $message == true) {
			$status = 'pass';
			$result = 'PASSED: ' . $name;
		} else {
			$status = 'fail';
			$result = 'FAILED: ' . $name . ($message ? sprintf(' (%s)', $message) : '');
		}
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
		if($this->isCli()) {
			return PHP_EOL;
		} else {
			return '<br>' . PHP_EOL;
		}
	}

}

$req = new RequirementsChecker();
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
echo $f->nl();

echo $f->heading('PHP configuration', 2);
echo $f->show(sprintf('Version: %s', PHP_VERSION));
echo $f->show(sprintf('php.ini path: %s', get_cfg_var('cfg_file_path')));
echo $f->nl();

echo $f->heading('PHP version', 3);
echo $f->showAssertion('PHP version at least 5.2.0', $req->assertMinimumPhpVersion('5.2.0'));
echo $f->show('');

echo $f->heading('PHP configuration options', 3);
echo $f->showAssertion('asp_tags option set to Off', $req->assertPhpIniOptionOff('asp_tags'));
echo $f->showAssertion('safe_mode set to Off', $req->assertPhpIniOptionOff('safe_mode'));
echo $f->showAssertion('allow_call_time_pass_reference option set to Off', $req->assertPhpIniOptionOff('allow_call_time_pass_reference'));
echo $f->showAssertion('short_open_tag option option set to Off', $req->assertPhpIniOptionOff('short_open_tag'));
echo $f->showAssertion('magic_quotes_gpc option set to Off', $req->assertPhpIniOptionOff('magic_quotes_gpc'));
echo $f->showAssertion('register_globals option set to Off', $req->assertPhpIniOptionOff('register_globals'));
echo $f->showAssertion('session.auto_start option set to Off', $req->assertPhpIniOptionOff('session.auto_start'));
echo $f->nl();

echo $f->heading('PHP extensions', 3);
echo $f->showAssertion('curl extension loaded', extension_loaded('curl'));
echo $f->showAssertion('dom extension loaded', extension_loaded('dom'));
echo $f->showAssertion('gd extension loaded', extension_loaded('gd'));
echo $f->showAssertion('gd extension is at least version 2.0', $req->assertMinimumPhpExtensionVersion('gd', '2.0'));
echo $f->showAssertion('iconv extension loaded', extension_loaded('iconv'));
echo $f->showAssertion('mbstring extension loaded', extension_loaded('mbstring'));
if(!preg_match('/WIN/', PHP_OS)) echo $f->showAssertion('posix extension loaded', extension_loaded('posix'));
echo $f->showAssertion('tidy extension loaded', extension_loaded('tidy'));
echo $f->showAssertion('xml extension loaded', extension_loaded('xml'));
echo $f->nl();

echo $f->heading('PHP core classes', 3);
echo $f->showAssertion('DOMDocument exists', class_exists('DOMDocument'));
echo $f->showAssertion('SimpleXMLElement exists', class_exists('SimpleXMLElement'));

if(isset($_SERVER['HTTP_HOST'])) {
	echo '</body>' . PHP_EOL;
	echo '</html>';
}
