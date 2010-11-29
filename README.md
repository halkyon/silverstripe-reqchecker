# SilverStripe Requirements Checker

## Introduction

A script which will check your server environment to make sure it is configured correctly
for [SilverStripe](http://silverstripe.org).

## Requirements

 * Existing PHP 5 environment with webserver setup

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