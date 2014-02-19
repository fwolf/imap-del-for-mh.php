<?php
namespace Fwolf\Bin\ImapDeleteForMh\Test;

use Fwlib\Bridge\PHPUnitTestCase;
use Fwolf\Bin\ImapDeleteForMh\Imap;

/**
 * @copyright   Copyright 2014 Fwolf
 * @author      Fwolf <fwolf.aide+imap-del-for-mh.php@gmail.com>
 * @license     http://opensource.org/licenses/mit-license MIT
 * @since       2014-02-19
 */
class ImapTest extends PHPUnitTestCase
{
    public static $dummyMessageId = 'dummy message id';
    protected $dummyResource = 'dummy imap connection';
    public static $imap_delete = null;
    public static $imap_fetch_overview = array();
    public static $imap_open = null;
    public static $imap_search = array();
    public static $imap_search_condition = '';


    protected function buildMock()
    {
        $imap = $this->getMock(
            '\Fwolf\Bin\ImapDeleteForMh\Imap',
            null,
            array('host', '993', 'user', 'pass', '[Gmail]/All Mail')
        );

        return $imap;
    }


    public function testConnect()
    {
        self::$imap_open = $this->dummyResource;
        $imap = $this->buildmock();

        $this->assertequals(
            $this->dummyResource,
            $this->reflectionget($imap, 'connection')
        );
    }


    /**
     * @expectedException Exception
     * @expectedExceptionMessage connect error
     */
    public function testConnectWithException()
    {
        self::$imap_open = null;
        $imap = $this->buildmock();
    }


    public function testDelete()
    {
        self::$imap_open = $this->dummyResource;
        self::$imap_delete = 'successful';
        $imap = $this->buildmock();

        $this->assertTrue($imap->delete(42, 'trash/'));
    }


    /**
     * @expectedException Exception
     * @expectedExceptionMessage delete error
     */
    public function testDeleteWithException()
    {
        self::$imap_open = $this->dummyResource;
        self::$imap_delete = null;
        $imap = $this->buildmock();

        $this->assertTrue($imap->delete(42, 'trash/'));
    }


    public function testSearch()
    {
        self::$imap_open = $this->dummyResource;
        $imap = $this->buildmock();

        // Search by date and from, with empty result
        self::$imap_search = array();
        $config = array(
            'date'  => '2014-02-19',
            'from'  => '发信人 <sender@domain.tld>',
            'messageId' => 'dummy message id',
        );

        $this->assertEquals(-1, $imap->search($config));
        $this->assertEquals(
            "SINCE '18-Feb-2014' BEFORE '20-Feb-2014' From 'sender@domain.tld'",
            self::$imap_search_condition
        );


        // Search by received, with empty result
        $config['received'] = '2014-02-20';

        $this->assertEquals(-1, $imap->search($config));
        $this->assertEquals(
            "SINCE '19-Feb-2014' BEFORE '21-Feb-2014'",
            self::$imap_search_condition
        );


        // Search with empty result
        self::$imap_search = array();
        $this->assertEquals(-1, $imap->search($config));


        // Search with single result
        self::$imap_search = array(42);
        $this->assertEquals(42, $imap->search($config));


        // Search with multiple result, use message id filter too
        self::$imap_search = array(42, 1);
        $config['messageId'] = self::$dummyMessageId;
        $this->assertEquals(1, $imap->search($config));


        // Search with multiple result, message id filter to empty
        self::$imap_search = array(42, 1);
        $config['messageId'] = self::$dummyMessageId . 'not exists';
        $this->assertEquals(-1, $imap->search($config));
    }
}


/* Mock imap function */
namespace Fwolf\Bin\ImapDeleteForMh;


function imap_delete($imaapStream, $uid, $option)
{
    if (is_null(\Fwolf\Bin\ImapDeleteForMh\Test\ImapTest::$imap_delete)) {
        throw new \Exception('imap delete fail');

    } else {
        return true;
    }
}


function imap_expunge($imapStream)
{
    return true;
}


function imap_fetch_overview($imapStream, $sequence, $option)
{
    $mailOverview = array();

    foreach (explode(',', $sequence) as $uid) {
        $mail = new \stdClass;

        $mail->uid = $uid;
        if (1 == $uid) {
            $mail->message_id = \Fwolf\Bin\ImapDeleteForMh\Test\ImapTest::
                $dummyMessageId;
        } else {
            $mail->message_id = $uid;
        }

        $mailOverview[] = $mail;
    }

    return $mailOverview;
}


function imap_mail_move($imapStream, $uid, $mailbox, $option)
{
    return true;
}


function imap_open($host, $user, $pass)
{
    if (is_null(\Fwolf\Bin\ImapDeleteForMh\Test\ImapTest::$imap_open)) {
        throw new \Exception('imap connect fail');

    } else {
        return \Fwolf\Bin\ImapDeleteForMh\Test\ImapTest::$imap_open;
    }
}


function imap_search($imapStream, $condition, $option)
{
    \Fwolf\Bin\ImapDeleteForMh\Test\ImapTest::$imap_search_condition =
        $condition;

    return \Fwolf\Bin\ImapDeleteForMh\Test\ImapTest::$imap_search;
}
