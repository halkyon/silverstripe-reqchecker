# SilverStripe Requirements Checker

## Introduction

This is a script which will check your server environment to make sure it is configured correctly
for [SilverStripe CMS / Framework](http://silverstripe.org).

It doesn't require SilverStripe to be installed first, so it's a good way to check your environment
is up to scratch before you install.

## Requirements

 * Apache or IIS 7.x webserver (nginx and lighttpd may work, but not tested)
 * PHP 5.3.2+

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
 * Check upload_max_filesize and post_max_size are big enough to allow reasonable size file uploads (e.g. 32M minimum)
 * Provide more detailed explanations and suggestions where failures and warnings occur
 * Display value checks in tabular form, showing actual versus recommended values for PHP options
 * Database checks - by default, MySQL version
 * Environment specific (dev/prod) checks, e.g. production environment has 500 and 404 custom error pages setup correctly, and display_errors = Off in production
 * Display "Download and install SilverStripe" link at bottom of page if all checks pass (or only warnings shown)

## Contact

 * Sean Harvey (sean@silverstripe.com)
 * Twitter: [@halkyon](http://twitter.com/halkyon)
