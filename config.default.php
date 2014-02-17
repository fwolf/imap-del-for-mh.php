<?php
/**
 * Default configure file
 *
 * @copyright   Copyright 2013-2014 Fwolf
 * @author      Fwolf <fwolf.aide+imap-del-for-mh.php@gmail.com>
 * @license     http://opensource.org/licenses/mit-license MIT
 * @since       2014-01-22
 */

use Fwlib\Base\ClassLoader;
use Fwlib\Config\GlobalConfig;

if ('config.default.php' == basename(__FILE__)) {
    // Record running start time, usefull for count total process time cost,
    // as of PHP 5.4.0, $_SERVER['REQUEST_TIME_FLOAT'] is build-in.
    if (0 > version_compare(PHP_VERSION, '5.4.0')) {
        list($msec, $sec) = explode(' ', microtime(false));
        $_SERVER['REQUEST_TIME_FLOAT'] = $sec . substr($msec, 1);
    }


    // Init config data array
    $config = array();


    // Load user config if exists
    // If use as git submodule, commonly this is put in vendor/ directory,
    // will try to load config of parent repository.
    if (file_exists(__DIR__ . '/../../config.php')) {
        require __DIR__ . '/../../config.php';
    } elseif (file_exists(__DIR__ . '/config.php')) {
        require __DIR__ . '/config.php';
    }
    $userConfig = $config;
}


/***********************************************************
 * Config define area
 *
 * Use $configUser to compute value if needed.
 *
 * In config.php, code outside this area can be removed.
 **********************************************************/


$config['lib.path.fwlib'] = 'fwlib/';


// Max files for one-run
$config['imap-del-for-mh.batchsize'] = 100;
// Original mh file dir
$config['imap-del-for-mh.dir.mh'] = '';
// Dir to store mh file after treatment
$config['imap-del-for-mh.dir.done'] = '';
// Dir to store mh file not found on server
$config['imap-del-for-mh.dir.error'] = '';
// Ignore these file, array or string split by ' ' or ','
$config['imap-del-for-mh.file.ignore'] = '';
/*
// Mail account directory setting
$config['imap-del-for-mh.mail'] = array(
    'account name'  => array(
        'mailbox'   => 'mailbox name',  // eg: [Gmail]/All Mail
        'trash'     => 'trash name',    // eg: [Gmail]/Trash
    ),
);

// Mail account authentication setting
$config['mail.account.user@domain_tld.provider'] = 'gmail';
$config['mail.account.user@domain_tld.name'] = 'user@domain.tld';
$config['mail.account.user@domain_tld.user'] = 'user or user@domain.tld';
$config['mail.account.user@domain_tld.pass'] = 'pass';

// Mail account server setting
$config['mail.provider.gmail.imap.host'] = 'imap.gmail.com';
$config['mail.provider.gmail.imap.port'] = 993;
*/


/***********************************************************
 * Config define area end
 **********************************************************/


if ('config.default.php' == basename(__FILE__)) {
    // Overwrite default config with user config
    $config = array_merge($config, $userConfig);

    // Include autoloader of Fwlib, need before other library
    require $config['lib.path.fwlib'] . 'autoload.php';

    // Deal with config, store in GlobalConfig instance
    GlobalConfig::getInstance()->load($config);


    // Register autoload of other external library, $classLoader is declared
    // in autoload.php of Fwlib, can use below.
    $classLoader->addPrefix(
        'Fwolf\Bin\ImapDeleteForMh',
        __DIR__ .  '/ImapDeleteForMh/'
    );

    require __DIR__ . '/vendor/autoload.php';
}
