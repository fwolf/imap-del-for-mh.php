<?php
/**
 * Bootstrap
 *
 *  - Record running start time
 *  - Register ClassLoader
 *  - Load default and user config
 *
 *  When use as a submodule, autoload.php and config.php need to search first
 *  on upper directory.
 *
 * @copyright   Copyright 2014 Fwolf
 * @license     http://opensource.org/licenses/MIT MIT
 */

use Fwlib\Base\ClassLoader;
use Fwlib\Config\GlobalConfig;

// Record running start time, usefull for count total process time cost, as of
// PHP 5.4.0, $_SERVER['REQUEST_TIME_FLOAT'] is build-in.
if (0 > version_compare(PHP_VERSION, '5.4.0')) {
    list($msec, $sec) = explode(' ', microtime(false));
    $_SERVER['REQUEST_TIME_FLOAT'] = $sec . substr($msec, 1);
}


// Include autoloader
if (is_readable(__DIR__ . '/../../vendor/autoload.php')) {
    $classLoader = require __DIR__ . '/../../vendor/autoload.php';
} elseif (is_readable(__DIR__ . '/vendor/autoload.php')) {
    $classLoader = require __DIR__ . '/vendor/autoload.php';
}


// Init config data array
$config = array();

// Load user config if exists
if (file_exists(__DIR__ . '/../../config.php')) {
    require __DIR__ . '/../../config.php';
} elseif (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
}
$userConfig = $config;

// Load default config
require 'config.default.php';

// Merge user and default config
$config = array_merge($config, $userConfig);

// Store config in GlobalConfig instance
GlobalConfig::getInstance()->load($config);
