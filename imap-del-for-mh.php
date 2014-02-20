#! /usr/bin/php
<?php
/**
 * imap-del-for-mh.php
 *
 * Copyright 2013-2014, Fwolf <fwolf.aide+imap-del-for-mh@gmail.com>
 * All rights reserved.
 *
 * Distributed under the MIT License.
 * http://opensource.org/licenses/mit-license
 *
 * Scan mail in MH folder, find them in imap server by message_id,
 * delete, then archive to another MH directory.
 *
 * @copyright   Copyright 2013-2014, Fwolf
 * @author      Fwolf <fwolf.aide+imap-del-for-mh.php@gmail.com>
 * @license     http://opensource.org/licenses/mit-license MIT
 * @since       2013-05-10
 * @version     1.0
 */

use Fwlib\Util\UtilContainer;
use Fwolf\Bin\ImapDeleteForMh\Config;
use Fwolf\Bin\ImapDeleteForMh\FileLister;
use Fwolf\Bin\ImapDeleteForMh\File;
use Fwolf\Bin\ImapDeleteForMh\Imap;

require __DIR__ . '/config.default.php';


$utilContainer = UtilContainer::getInstance();
$envUtil = $utilContainer->get('Env');

$config = Config::getInstance();


// Find files to deal with
$mhDir = $config->get('dir.mh');
$fileLister = new FileLister($mhDir);
$fileLister->ignore($config->get('file.ignore'));
$fileList = $fileLister->get();
if (0 == count($fileList)) {
    exit;
}


// Connect to imap server
$imapList = array();
$trashDirectory = array();
foreach ($config->getMailAccountList() as $accountName) {
    $provider = $config->getMailProviderByAccount($accountName);
    $accountInfo = $config->getMailAccount($accountName);
    $accountDirectory = $config->getMailAccountDirectory($accountName);

    $imapList[$accountName] = new Imap(
        $provider['host'],
        $provider['port'],
        $accountInfo['user'],
        $accountInfo['pass'],
        $accountDirectory['mailbox']
    );

    $trashDirectory[$accountName] = $accountDirectory['trash'];
}


$counter = 0;
$counterMax = $config->get('batchsize');
$doneDir = $config->get('dir.done');
$errorDir = $config->get('dir.error');

foreach ($fileList as $fileName) {
    $counter ++;
    if ($counter > $counterMax) {
        break;
    }

    $envUtil->ecl("\n[" . date('Y-m-d H:i:s') . ']');

    $file = new File($mhDir, $fileName);
    $date = $file->getDate();
    $from = $file->getFrom();
    $received = $file->getReceived();
    $messageId = $file->getMessageId();
    $subject = $file->getSubject();

    $envUtil->ecl("From: $from");
    $envUtil->ecl("Date: $date");
    $envUtil->ecl("Subject: $subject");
    $envUtil->ecl("Message-ID: $messageId");

    $foundInImap = true;
    foreach ($imapList as $accountName => $imap) {
        $uid = $imap->search(
            array(
                'date'  => $date,
                'from'  => $from,
                'received'  => $received,
                'messageId' => $messageId,
            )
        );

        if (-1 == $uid) {
            $foundInImap = false;

        } else {
            $envUtil->ecl("    Account: $accountName, UID: $uid");
            $imap->delete($uid, $trashDirectory[$accountName]);
            $envUtil->ecl("    Deleted");
        }
    }

    if ($foundInImap) {
        $file->move($doneDir);
    } else {
        $file->move($errorDir);
    }
}
