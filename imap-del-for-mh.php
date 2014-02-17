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

// Include
if (!defined('P2R')) define('P2R', __DIR__ . '/');
require_once(__DIR__ . '/config.default.php');
require_once FWOLFLIB . '/func/ecl.php';
require_once FWOLFLIB . '/func/filesystem.php';


// Main body

// Init
$ar_file = GetFileMh();
$ar_file_ignore = GetFileIgnore();
$i_done = 0;
$i_done_max = GetCfg('imap-del-for-mh.batchsize');
$ar_mail = array();
$ar_mbox = array();

// Retrieve files
if (!empty($ar_file))
    ImapConnect();

    foreach ($ar_file as $s_file) {
        if ($i_done == $i_done_max)
            break;

        if (in_array($s_file['name'], $ar_file_ignore))
            continue;

        $ar_uid = array();
        $i_done += ImapDel($s_file['name']);
    }


// Functions define


/**
 * Get files need ignore
 *
 * @return  array
 */
function GetFileIgnore () {
    $ar = GetCfg('imap-del-for-mh.file.ignore');
    if (empty($ar))
        return array();

    if (is_string($ar)) {
        // Split by ' ' or ','
        $ar = preg_replace('/[, ]+/', ',', $ar);
        $ar = explode(',', $ar);
    }

    return $ar;
} // end of func GetFileIgnore


/*
 * Get MH files
 *
 * @return  array
 */
function GetFileMh () {
    $s_path = GetCfg('imap-del-for-mh.dir.mh');
    if (empty($s_path) || !is_readable($s_path))
        return array();

    return ListDir($s_path);
} // end of func GetFileMh


/**
 * Connect to imap server
 */
function ImapConnect () {
    global $ar_mail, $ar_mbox;

    // Connect to imap
    $ar_mail = GetCfg('imap-del-for-mh.mail');
    if (empty($ar_mail))
        return 0;
    if (!is_array($ar_mail))
        $ar_mail = array($ar_mail);

    foreach ($ar_mail as $account => $mail) {
        $s_host = '{' . GetCfg('mail.server.'
                . GetCfg('mail.account.' . $account . '.server')
                . '.imap.host')
            . ':' . GetCfg('mail.server.'
                . GetCfg('mail.account.' . $account . '.server')
                . '.imap.port')
            . '/imap/ssl/novalidate-cert}' . $mail['mailbox'];
        $ar_mbox[$account] = @imap_open($s_host
            , GetCfg('mail.account.' . $account . '.user')
            , GetCfg('mail.account.' . $account . '.pass')
        );
        // Check error
        $rs = imap_last_error();
        if (!(false === $rs)) {
            Ecl('Can\'t connect to ' . $account);
            exit(-1);
        }
    }
} // end of ImapConnect


/**
 * Do imap del
 *
 * @param   string  $s_file
 * @return  int     0=fail, 1=success
 */
function ImapDel ($s_file) {
    global $ar_mbox, $ar_uid;

    Ecl("\n[" . date('Y-m-d H:i:s') . ']');
    $s_file = GetCfg('imap-del-for-mh.dir.mh') . $s_file;
    ImapSearch($s_file);

    if (empty($ar_uid)) {
        // Nothing found, move to error
        MailFileMove($s_file, GetCfg('imap-del-for-mh.dir.error'));
        return 0;
    }

    $ar_done = array();
    foreach ($ar_uid as $account => $i_uid) {
        Ecl("\t" . 'Account: ' . $account . ', UID: ' . $i_uid);
        $b1 = imap_mail_move($ar_mbox[$account], $i_uid
            , GetCfg('imap-del-for-mh.mail.' . $account . '.trash')
            , CP_UID);
        $b2 = imap_delete($ar_mbox[$account], $i_uid, FT_UID);
        if ($b1 && $b2) {
            $ar_done[] = $account;
            imap_expunge($ar_mbox[$account]);
        }
    }

    // Result
    if (!empty($ar_done)) {
        Ecl("\t" . 'Deleted from: ' . implode(', ', $ar_done));
        // Archive file
        MailFileMove($s_file, GetCfg('imap-del-for-mh.dir.done'));

        return 1;
    }
    else
        return 0;
} // end of func ImapDel


/**
 * Search for imap uid
 *
 * @param   string  $s_file
 * @return  int
 */
function ImapSearch ($s_file) {
    global $ar_mbox, $ar_uid;

    Ecl('File: ' . $s_file);

    if (!is_readable($s_file))
        return;

    $s = file_get_contents($s_file);
    // Grap From, Date, Message-ID
    $s_date = '';
    $s_received = '';
    $s_from = '';
    $s_messageid = '';
    $s_subject = '';
    $ar = array();

    $i = preg_match('/\nDate:(.+?)\n/i', $s, $ar);
    if (1 === $i) {
        $s_date_original = trim($ar[1]);
        $s_date = date('d-M-Y', strtotime($ar[1]));
        $s_date_since = date('d-M-Y', strtotime($s_date . ' -1 day'));
        $s_date_before = date('d-M-Y', strtotime($s_date . ' +1 day'));
    }
    else {
        Ecl('Date: empty.');
        return;
    }

    $i = preg_match('/\nReceived:.*;\s+(.+?)\n/im', $s, $ar);
    if (1 === $i) {
        $s_received_original = trim($ar[1]);
        $s_received = date('d-M-Y', strtotime($ar[1]));
        $s_received_since = date('d-M-Y', strtotime($s_received . ' -1 day'));
        $s_received_before = date('d-M-Y', strtotime($s_received . ' +1 day'));
    }
    else {
        Ecl('Received: empty.');
    }

    // From: need decode
    $i = preg_match('/\nFrom:(.+?)\n/i', $s, $ar);
    if (1 === $i) {
        $ar = imap_mime_header_decode(trim($ar[1]));
        foreach ((array)$ar as $elm)
            $s_from .= $elm->text;
        $s_from_original = $s_from;
        // Grap <mail@domain.tld> for search condition
        $i = preg_match('/<(.+)>/', $s_from, $ar);
        if (1 === $i)
            $s_from = $ar[1];
        else
            $s_from = '';
    }
    else {
        Ecl('From: empty.');
        return;
    }

    $i = preg_match('/\nMessage-ID:(.+?)\n/i', $s, $ar);
    if (1 === $i) {
        $s_messageid = trim($ar[1]);
    }
    else {
        Ecl('Message-ID: empty.');
        return;
    }

    // This regex support multi-line Subject:
    $i = preg_match('/\nSubject:([\s\S]*?)\n\w/im', $s, $ar);
    if (1 === $i) {
        $ar = imap_mime_header_decode(trim($ar[1]));
        foreach ((array)$ar as $elm)
            $s_subject .= $elm->text;
    }
    else {
        Ecl('Subject: empty.');
    }

    Ecl('From: ' . $s_from_original);
    Ecl('Date: ' . $s_date_original);
    Ecl('Subject: ' . $s_subject);
    Ecl('Message-ID: ' . $s_messageid);

    // Do search
    $s_search = '';
    if (! (false === strpos($s_from, '@'))) {
        $s_search .= ' FROM "' . addslashes($s_from) . '"';
    }
    // Subject search various by content, may not work
/*
    else {
        // If no From:, try Subject:
        if (!empty($s_subject) && (false === strpos($s_subject, '=?'))) {
            $s_search .= ' SUBJECT "' . addslashes($s_subject) . '"';
        }
    }
*/
    $s_search .= ' SINCE "' . $s_date_since . '"'
        . ' BEFORE "' . $s_date_before . '"'
    ;
    // Use received time as 2nd search condition, use only time
    $s_search2 = ' SINCE "' . $s_received_since . '"'
        . ' BEFORE "' . $s_received_before . '"'
    ;

    foreach ($ar_mbox as $account => $o_mbox) {
        $ar = imap_search($o_mbox, $s_search, SE_UID);
        if (empty($ar)) {
            $ar = imap_search($o_mbox, $s_search2, SE_UID);
        }
        if (empty($ar))
            continue;

        // Found, fetch and compare with message-id
        $ar = imap_fetch_overview($o_mbox, implode(',', $ar), FT_UID);
        if (empty($ar))
            continue;
        $i_uid = -1;

        // If only 1 search result, assign directly
        if (1 == count($ar))
            $i_uid = $ar[0]->uid;
        else {
            foreach ($ar as $mail)
                if ($mail->message_id == $s_messageid) {
                    $i_uid = $mail->uid;
                    break;
                }
        }

        // Write to result array
        if (-1 != $i_uid)
            $ar_uid[$account] = $i_uid;
    }

    if (empty($ar_uid)) {
//      Ecl("\t" . $s_search);
        Ecl("\t" . 'Mail not found on all server !');
    }

    return;
} // end of func ImapSearch


/**
 * Move mail file to another dir (MH)
 *
 * @param   string  $s_srce
 * @param   string  $s_dir
 */
function MailFileMove ($s_srce, $s_dir) {
    // Check existing files in $s_dir, determine dest filename
    $ar = scandir($s_dir);
    // Manual sort because $ar include '.' '..' and other non-mail files
    $i = 0;
    foreach ((array)$ar as $f) {
        if (is_numeric($f) && is_file($s_dir . $f) && $f > $i)
            $i = intval($f);
    }
    if (0 == $i)
        // No exists mail, use original filename
        rename($s_srce, $s_dir . basename($s_srce));
    else
        // Use new filename
        rename($s_srce, $s_dir . strval(++$i));
} // end of func MailFileMove


/*
 * ChangeLog
 *
 * v 0.03 / 2013-07-29
 *  - Fit new config.default.php style
 *
 * V 0.02 / 2013-05-13 /
 *  - Move file with rename, will not replace existing file anymore.
 *
 * V 0.01 / 2013-05-10 / 434423e082
 *  - New.
 */
?>
