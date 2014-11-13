<?php
namespace Fwolf\Bin\ImapDeleteForMh;

use Fwlib\Util\UtilContainer;

/**
 * File lister and filter
 *
 * @copyright   Copyright 2014 Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 */
class FileLister
{
    /**
     * Array of files
     *
     * @var array
     */
    protected $fileList = array();


    /**
     * Constructor
     *
     * @param   string  $dir
     */
    public function __construct($dir)
    {
        if (!empty($dir) && is_readable($dir)) {
            $this->fileList = $this->listDir($dir);
        }
    }


    /**
     * Getter of $fileList
     *
     * @return  array
     */
    public function get()
    {
        return $this->fileList;
    }


    /**
     * Ignore files
     *
     * $ignoreList can be array of filename string, or filename string splitted
     * by ' ' or ','.
     *
     * @param   string|array    $ignoreList
     */
    public function ignore($ignoreList)
    {
        if (is_string($ignoreList)) {
            $ignoreList = preg_replace('/[, ]+/', ',', $ignoreList);
            $ignoreList = explode(',', $ignoreList);
        }

        $this->fileList = array_diff($this->fileList, $ignoreList);
    }


    /**
     * Get files in dir
     *
     * @param   string  $dir
     * @return  array of filename
     */
    protected function listDir($dir)
    {
        $fileSystemUtil = UtilContainer::getInstance()->get('FileSystem');

        $file = array();
        foreach ((array)$fileSystemUtil->listDir($dir) as $fileInfo) {
            $file[] = $fileInfo['name'];
        }

        return $file;
    }
}
