# SilverStripe Requirements Checker

## Introduction

A script which will check your server environment to make sure it is configured correctly
for [SilverStripe CMS / Framework](http://silverstripe.org).

It has been tested on the following dedicated platforms:

 * Mac OS X 10.6.5 (MacPorts): Apache 2.2.17 (mod_php5), PHP 5.3.3
 * Windows 7 Professional x64: Apache 2.2.17 VC9 (mod_php5 SAPI), PHP 5.3.3 VC9 TS
 * Windows Server 2008 R2 Standard x64: IIS 7.5 (FastCGI SAPI), PHP 5.3.3 VC9 NTS
 * Debian GNU/Linux "lenny": Apache 2.2.9 (mod_php5 SAPI), PHP 5.2.6
 * Ubuntu Server 10.10: Apache 2.2.16 (mod_php5 SAPI), PHP 5.3.3

and on the following shared hosts:

 * Arvixe Red Hat Linux: Apache 2.2.16 (CGI SAPI), PHP 5.2.14

## Requirements

 * Apache or IIS 7.x webserver
 * PHP 5.2.0+

## Using

 1. Unpack the source files to somewhere in your server webroot, e.g. /var/www/ssreqcheck
 2. Open a browser and point to that file, e.g. http://localhost/ssreqcheck/index.php

## Future enhancements

 * Display value checks in tabular form, showing actual versus recommended values for PHP options
 * Database checks - by default, MySQL version
 * Environment specific (dev/prod) checks, e.g. production environment has 500 and 404 custom error pages setup correctly, and display_errors = Off in production
 * Display "Download and install SilverStripe" link at bottom of page if all checks pass (or only warnings shown)

## Known issues

 * URL rewrite check fails on Arvixe

## Contact

 * Sean Harvey (sean@silverstripe.com)
 * Twitter: [@halkyon](http://twitter.com/halkyon)