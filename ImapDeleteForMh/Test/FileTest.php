<?php
namespace Fwolf\Bin\ImapDeleteForMh\Test;

use Fwlib\Bridge\PHPUnitTestCase;
use Fwolf\Bin\ImapDeleteForMh\File;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;

/**
 * @copyright   Copyright 2014 Fwolf
 * @author      Fwolf <fwolf.aide+imap-del-for-mh.php@gmail.com>
 * @license     http://opensource.org/licenses/mit-license MIT
 * @since       2014-02-18
 */
class FileTest extends PHPUnitTestCase
{
    protected function buildMock($name)
    {
        $file = new File(vfsStream::url('mhDir/mh/'), $name);

        return $file;
    }


    public static function setUpBeforeClass()
    {
        $root = vfsStream::setup('mhDir');
        $mhDir = vfsStream::newDirectory('mh');
        $root->addChild($mhDir);
        vfsStream::copyFromFileSystem(
            __DIR__ . '/testmail',
            $mhDir
        );

        $root->addChild(vfsStream::newDirectory('done'));

        //vfsStream::inspect(new vfsStreamPrintVisitor);
    }


    public function testGetDate()
    {
        $file = $this->buildMock('1');
        $this->assertEquals(
            'Mon, 17 Feb 2014 17:50:25 +0800',
            $file->getDate()
        );

        $file = $this->buildMock('2');
        $this->assertNull($file->getDate());
    }


    public function testGetFrom()
    {
        $file = $this->buildMock('1');
        $this->assertEquals(
            '发信人 <user@domain.tld>',
            $file->getFrom()
        );

        $file = $this->buildMock('2');
        $this->assertNull($file->getFrom());
    }


    public function testGetMessageId()
    {
        $file = $this->buildMock('1');
        $this->assertEquals(
            'CAL31YSwTVMq+Vdx7GD5BH4=oO2=Xi5jQToSuuYwdE6T4i19d-A@mail.gmail.com',
            $file->getMessageId()
        );

        $file = $this->buildMock('2');
        $this->assertNull($file->getMessageId());
    }


    public function testGetReceived()
    {
        $file = $this->buildMock('1');
        $this->assertEquals(
            'Mon, 17 Feb 2014 01:50:25 -0800 (PST)',
            $file->getReceived()
        );

        $file = $this->buildMock('2');
        $this->assertNull($file->getReceived());
    }


    public function testGetSubject()
    {
        $file = $this->buildMock('1');
        $this->assertEquals(
            'Test imap-del-for-mh.php',
            $file->getSubject()
        );

        $file = $this->buildMock('2');
        $this->assertNull($file->getSubject());
    }


    public function testMove()
    {
        $mhDir = vfsStreamWrapper::getRoot()->getChild('mh');
        $doneDir = vfsStreamWrapper::getRoot()->getChild('done');

        $this->assertTrue($mhDir->hasChild('1'));
        $this->assertTrue($mhDir->hasChild('2'));
        $this->assertFalse($doneDir->hasChild('1'));
        $this->assertFalse($doneDir->hasChild('2'));

        $file = $this->buildMock('2');
        $file->move(vfsStream::url('mhDir/done/'));

        // File 2 is moved to done dir, and rename to 1
        $this->assertTrue($mhDir->hasChild('1'));
        $this->assertFalse($mhDir->hasChild('2'));
        $this->assertTrue($doneDir->hasChild('1'));
        $this->assertFalse($doneDir->hasChild('2'));

        $file = $this->buildMock('1');
        $file->move(vfsStream::url('mhDir/done/'));

        // File 1 is moved to done dir, and rename to 2
        $this->assertFalse($mhDir->hasChild('1'));
        $this->assertFalse($mhDir->hasChild('2'));
        $this->assertTrue($doneDir->hasChild('1'));
        $this->assertTrue($doneDir->hasChild('2'));
    }
}
