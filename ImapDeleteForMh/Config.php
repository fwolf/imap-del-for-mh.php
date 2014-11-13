<?php
namespace Fwolf\Bin\ImapDeleteForMh;

use Fwlib\Base\AbstractSingleton;
use Fwlib\Config\GlobalConfig;

/**
 * Config getter
 *
 * @copyright   Copyright 2014 Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 */
class Config extends AbstractSingleton
{
    /**
     * @var GlobalConfig
     */
    protected $globalConfig = null;


    /**
     * Configure prefix
     *
     * @var string
     */
    protected $prefix = 'imap-del-for-mh';


    /**
     * Constructor
     */
    protected function __construct()
    {
        $this->globalConfig = GlobalConfig::getInstance();
    }


    /**
     * Get common config
     *
     * @param   string  $key
     * @return  string|array
     */
    public function get($key)
    {
        return $this->globalConfig->get("{$this->prefix}.$key");
    }


    /**
     * Get mail account authentication setting
     *
     * @param   string  $account
     * @return  array
     */
    public function getMailAccount($account)
    {
        return $this->globalConfig->get("mail.account.$account");
    }


    /**
     * Get mail account directory setting
     *
     * @param   string  $account
     * @return  array
     */
    public function getMailAccountDirectory($account)
    {
        return $this->globalConfig->get("{$this->prefix}.mail.$account");
    }


    /**
     * @return  array
     */
    public function getMailAccountList()
    {
        $accountSetting = $this->globalConfig->get("{$this->prefix}.mail");

        return array_keys((array)$accountSetting);
    }


    /**
     * Get mail provider setting
     *
     * @param   string  $provider
     * @return  array
     */
    public function getMailProvider($provider)
    {
        return $this->globalConfig->get("mail.provider.$provider.imap");
    }


    /**
     * Get mail provider setting by mail account
     *
     * @param   string  $account
     * @return  array
     */
    public function getMailProviderByAccount($account)
    {
        $accountSetting = $this->getMailAccount($account);
        $provider = $accountSetting['provider'];

        return $this->getMailProvider($provider);
    }
}
