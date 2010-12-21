# SilverStripe Requirements Checker

## Introduction

This is a script which will check your server environment to make sure it is configured correctly
for [SilverStripe CMS / Framework](http://silverstripe.org).

It has been tested on the following dedicated platforms:

 * Mac OS X 10.6.5 (MacPorts): Apache 2.2.17 (mod_php5 SAPI), PHP 5.3.3
 * Windows 7 Professional x64: Apache 2.2.17 VC9 (mod_php5 SAPI), PHP 5.3.3 VC9 TS
 * Windows 7 Professional x64: Apache 2.2.17 VC9 (FastCGI SAPI), PHP 5.3.4 VC9 NTS
 * Windows Server 2008 R2 Standard x64: IIS 7.5 (FastCGI SAPI), PHP 5.3.3 VC9 NTS
 * Debian GNU/Linux "lenny": Apache 2.2.9 (mod_php5 SAPI), PHP 5.2.6
 * Ubuntu Server 10.10: Apache 2.2.16 (mod_php5 SAPI), PHP 5.3.3

It has also been tested on the following shared web hosts:

 * Arvixe Red Hat Linux: Apache 2.2.16 (CGI SAPI), PHP 5.2.14
 * 1&1 Internet AG: Apache (CGI SAPI), PHP 5.2.14

## Requirements

 * Apache or IIS 7.x webserver (nginx and lighttpd may work, but not tested)
 * PHP 5.2.0+ (PHP 5.1.6 may work, but not tested)

## Using

 1. Unpack the source files to somewhere in your server webroot, e.g. /var/www/ssreqcheck
 2. Open a browser and point to that file, e.g. http://localhost/ssreqcheck/index.php
 3. If using Apache, make sure the *.htaccess* file inside the *rewritetest* directory is owned by the webserver user so that PHP can write to it. This is used for testing URL rewriting is working by writing a correct *RewriteBase* directive

To change permissions, do something like this:

	chown www-data /var/www/ssreqcheck/rewritetest/.htaccess

You can also run the checker on the command line. For example:

	php /var/www/ssreqcheck/index.php

*Caution: Webserver checks, including URL rewrite tests are not performed when running from the command line.*

## Future enhancements

 * Check file uploads by PHP moved into a publically viewable directory with rewriting turned on is accessible in the URL.
   Windows, for example, uses C:\Windows\Temp for system temp. This path doesn't have IIS_IUSR permissions by default, which will cause issues with IIS URL Rewrite module tries to access any files created in this temp path, but moved into the SilverStripe assets directory
 * Provide more detailed explanations and suggestions where failures and warnings occur
 * Display value checks in tabular form, showing actual versus recommended values for PHP options
 * Database checks - by default, MySQL version
 * Environment specific (dev/prod) checks, e.g. production environment has 500 and 404 custom error pages setup correctly, and display_errors = Off in production
 * Display "Download and install SilverStripe" link at bottom of page if all checks pass (or only warnings shown)

## Contact

 * Sean Harvey (sean@silverstripe.com)
 * Twitter: [@halkyon](http://twitter.com/halkyon)