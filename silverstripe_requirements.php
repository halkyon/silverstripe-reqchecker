<?php
class RequirementsChecker {

	/**
	 * Check that a php.ini option is set to "Off"
	 * ini_get() returns as Off settings as an empty string.
	 * 
	 * @param string $name Name of configuration setting
	 * @return boolean TRUE passed assertion, FALSE failed assertion
	 */
	public function assertPhpIniOptionOff($option) {
		return ini_get($option) == false;
	}

	public function assertMinimumPhpVersion($version) {
		return version_compare($version, PHP_VERSION, '<=');
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
			return '<br>';
		}
	}

}

$req = new RequirementsChecker();
$f = new RequirementsFormatter();

if(isset($_SERVER['HTTP_HOST'])) {
	echo '<html>';
	echo '<head>';
	echo '<title>SilverStripe Requirements</title>';
	echo '<style>';
	echo 'span.pass { color: green; } span.fail { color: red; }';
	echo '</style>';
	echo '</head>';
	echo '<body>';
}

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
echo $f->showAssertion('gd2 extension loaded', extension_loaded('gd'));
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
	echo '</body>';
	echo '</html>';
}

// check can set include path and memory limit higher