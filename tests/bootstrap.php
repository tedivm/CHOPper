<?php

/*
 * This file is part of the CHOPper package.
 *
 * (c) Robert Hafner <tedivm@tedivm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('TESTING', true);
error_reporting(-1);
ini_set("auto_detect_line_endings", true);

$filename = __DIR__ .'/../vendor/autoload.php';

if (!file_exists($filename)) {
    throw new Exception("You need to execute `composer install` before running the tests. (vendors are required for test execution)");
}

require_once $filename;
