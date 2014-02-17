<?php
namespace Fwolf\Bin\ImapDeleteForMh\Test;

use Fwlib\Bridge\PHPUnitTestCase;
use Fwlib\Config\GlobalConfig;
use Fwolf\Bin\ImapDeleteForMh\Config;

/**
 * @copyright   Copyright 2014 Fwolf
 * @author      Fwolf <fwolf.aide+imap-del-for-mh.php@gmail.com>
 * @license     http://opensource.org/licenses/mit-license MIT
 * @since       2014-02-17
 */
class ConfigTest extends PHPUnitTestCase
{
    private $globalConfig;


    protected function buildMock()
    {
        $config = Config::getInstance();

        $globalConfig = $this->getMock(
            'Fwlib\Config\GlobalConfig',
            null
        );
        $this->reflectionSet(
            $globalConfig,
            'config',
            $this->reflectionGet(GlobalConfig::getInstance(), 'config')
        );
        $this->reflectionSet($config, 'globalConfig', $globalConfig);
        $this->globalConfig = $globalConfig;

        return $config;
    }


    public function testGet()
    {
        $config = $this->buildMock();

        $this->assertNotNull($config->get('batchsize'));
        $this->assertNotNull($config->get('dir.mh'));
        $this->assertNotNull($config->get('dir.done'));
        $this->assertNotNull($config->get('dir.error'));
        $this->assertNotNull($config->get('file.ignore'));
    }


    public function testGetMailAccount()
    {
        $config = $this->buildMock();

        $accountSetting = array(
            'provider'  => 'gmail',
            'name'      => 'user@domain.tld',
            'user'      => 'user',
            'pass'      => 'password',
        );
        $this->globalConfig->set(
            "mail.account.user@domain_tld",
            $accountSetting
        );

        $this->assertEqualArray(
            $accountSetting,
            $config->getMailAccount('user@domain_tld')
        );
    }


    public function testGetMailAccountDirectory()
    {
        $config = $this->buildMock();

        $directory = array(
            'mailbox'   => '[Gmail]/All Mail',
            'trash'     => '[Gmail]/Trash',
        );
        $prefix = $this->reflectionGet($config, 'prefix');

        $this->globalConfig->set("$prefix.mail.testAccount", $directory);

        $this->assertEqualArray(
            $directory,
            $config->getMailAccountDirectory('testAccount')
        );

        $this->assertEqualArray(
            array('testAccount'),
            $config->getMailAccountList()
        );
    }


    public function testGetMailProviderByAccount()
    {
        $config = $this->buildMock();

        $accountSetting = array(
            'provider'  => 'gmail',
        );
        $this->globalConfig->set(
            "mail.account.user@domain_tld",
            $accountSetting
        );

        $providerSetting = array(
            'host'  => 'imap host',
            'port'  => 'imap port',
        );
        $this->globalConfig->set(
            "mail.provider.gmail.imap",
            $providerSetting
        );

        $this->assertEqualArray(
            $providerSetting,
            $config->getMailProviderByAccount('user@domain_tld')
        );
    }
}
