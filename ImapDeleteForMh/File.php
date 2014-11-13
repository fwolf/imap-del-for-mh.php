<?php
namespace Fwolf\Bin\ImapDeleteForMh;


/**
 * Mail file, in MH directory
 *
 * @copyright   Copyright 2014 Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 */
class File
{
    /**
     * Mail file content
     *
     * @var string
     */
    protected $content = '';

    /**
     * Filename only, no directory
     *
     * @var string
     */
    protected $name = '';

    /**
     * File path
     *
     * @var string
     */
    protected $path = '';


    /**
     * Constructor
     *
     * @param   string  $path
     * @param   string  $name
     */
    public function __construct($path, $name)
    {
        $this->path = $path;
        $this->name = $name;
        $this->content = file_get_contents($path . $name);
    }


    /**
     * Get mail 'Date:' information
     *
     * @return  string|null
     */
    public function getDate()
    {
        $i = preg_match('/\nDate:(.+?)\n/i', $this->content, $match);
        if (1 === $i) {
            return trim($match[1]);
        } else {
            return null;
        }
    }


    /**
     * Get mail 'From:' information
     *
     * @return  string|null
     */
    public function getFrom()
    {
        $i = preg_match('/\nFrom:(.+?)\n/i', $this->content, $match);

        if (1 === $i) {
            // Decode multiple line text
            $elm = imap_mime_header_decode(trim($match[1]));

            $from = '';
            foreach ((array)$elm as $line) {
                $from .= $line->text;
            }

            return $from;

        } else {
            return null;
        }
    }


    /**
     * Get mail 'Message-ID:' information
     *
     * @return  string|null
     */
    public function getMessageId()
    {
        $i = preg_match('/\nMessage-ID:(.+?)\n/i', $this->content, $match);
        if (1 === $i) {
            // Message ID in imap_fetch_overview() result has '<>', so keep it
            return trim($match[1]);
        } else {
            return null;
        }
    }


    /**
     * Get mail 'Received:' information
     *
     * @return  string|null
     */
    public function getReceived()
    {
        $i = preg_match('/\nReceived:.*;\s+(.+?)\n/im', $this->content, $match);
        if (1 === $i) {
            return trim($match[1]);
        } else {
            return null;
        }
    }


    /**
     * Get mail 'Subject:' information
     *
     * @return  string|null
     */
    public function getSubject()
    {
        $i = preg_match('/\nSubject:([\s\S]*?)\n\w/im', $this->content, $match);

        if (1 === $i) {
            // Decode multiple line text
            $elm = imap_mime_header_decode(trim($match[1]));

            $subject = '';
            foreach ((array)$elm as $line) {
                $subject .= $line->text;
            }

            return $subject;

        } else {
            return null;
        }
    }


    /**
     * Move mail file to another MH dir
     *
     * @param   string  $destinationDir
     */
    public function move($destinationDir)
    {
        // Scan destination dir, use max + 1 as new moved file
        $destinationFiles = scandir($destinationDir);

        $i = 0;
        foreach ((array)$destinationFiles as $file) {
            if (is_file($destinationDir . $file) &&
                is_numeric($file) && $file > $i
            ) {
                $i = intval($file);
            }
        }

        $filename = (0 == $i) ? 1 : $i + 1;
        rename($this->path . $this->name, $destinationDir . $filename);
    }
}
