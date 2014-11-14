<?php
/**
 * Default configure file
 *
 * For user customized configure, copy this file as 'config.php', and change
 * value in it, or remove unchanged value. These two files will be automatic
 * loaded.
 *
 * In this file, there can use $userConfig to reference user customized config
 * value, but remember to give default value if user config not set.
 *
 * @copyright   Copyright 2013-2014 Fwolf
 * @license     http://opensource.org/licenses/MIT MIT
 */


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
*/

// Mail account server setting
$config['mail.provider.gmail.imap.host'] = 'imap.gmail.com';
$config['mail.provider.gmail.imap.port'] = 993;
