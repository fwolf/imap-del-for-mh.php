<?php
namespace Fwolf\Bin\ImapDeleteForMh\Test;

use Fwlib\Bridge\PHPUnitTestCase;
use Fwolf\Bin\ImapDeleteForMh\FileLister;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;

/**
 * @copyright   Copyright 2014 Fwolf
 * @author      Fwolf <fwolf.aide+imap-del-for-mh.php@gmail.com>
 * @license     http://opensource.org/licenses/mit-license MIT
 * @since       2014-02-17
 */
class FileListerTest extends PHPUnitTestCase
{
    protected function buildMock()
    {
        $fileLister = new FileLister(vfsStream::url('mhDir'));

        return $fileLister;
    }


    public static function setUpBeforeClass()
    {
        $root = vfsStream::setup('mhDir');
        vfsStream::copyFromFileSystem(
            __DIR__ . '/mhDir'
        );
    }


    public function testIgnore()
    {
        $fileLister = $this->buildMock();

        $this->assertNotEmpty($fileLister->get());

        $fileLister->ignore(' , testmail-1,');

        $this->assertEmpty($fileLister->get());
    }


    public function testListDir()
    {
        $fileLister = $this->buildMock();

        $fileList = $fileLister->get();

        $this->assertGreaterThan(0, count($fileList));
        $this->assertEquals('testmail-1', current($fileList));
    }
}


// realpath() doesn't work for vfs, overwrite it
// https://github.com/mikey179/vfsStream/wiki/Known-Issues
namespace Fwlib\Util;

function realpath($path)
{
    return $path;
}
