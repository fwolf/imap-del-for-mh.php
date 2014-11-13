<?php
namespace Fwolf\Bin\ImapDeleteForMh;


/**
 * IMAP operator
 *
 * @copyright   Copyright 2014 Fwolf
 * @license     http://opensource.org/licenses/mit-license MIT
 */
class Imap
{
    /**
     * IMAP connection
     *
     * @var resource
     */
    protected $connection = null;


    /**
     * Constructor
     *
     * @param   string  $host
     * @param   string  $port
     * @param   string  $user
     * @param   string  $pass
     * @param   string  $mailbox
     */
    public function __construct($host, $port, $user, $pass, $mailbox)
    {
        $this->connection = $this->connect(
            $host,
            $port,
            $user,
            $pass,
            $mailbox
        );
    }


    /**
     * Connect to IMAP server
     *
     * @param   string  $host
     * @param   string  $port
     * @param   string  $user
     * @param   string  $pass
     * @param   string  $mailbox
     * @return  resource
     */
    protected function connect($host, $port, $user, $pass, $mailbox)
    {
        try {
            $hostString = "{{$host}:$port/imap/ssl/novalidate-cert}$mailbox";
            $connection = imap_open($hostString, $user, $pass);

            return $connection;

        } catch (\Exception $e) {
            throw new \Exception(
                'IMAP server connect error: ' . $e->getMessage()
            );
        }
    }


    /**
     * Delete a message
     *
     * @param   int     $uid
     * @param   string  $trashDir   IMAP folder
     * @return  boolean
     */
    public function delete($uid, $trashDir)
    {
        try {
            $op1 = imap_mail_move(
                $this->connection,
                $uid,
                $trashDir,
                CP_UID
            );

            $op2 = imap_delete($this->connection, $uid, FT_UID);

            if ($op1 && $op2) {
                imap_expunge($this->connection);
            }

            return true;

        } catch (\Exception $e) {
            throw new \Exception(
                'IMAP delete error: ' . $e->getMessage()
            );
        }
    }


    /**
     * Search for specified message, return message uid
     *
     * Content of $config:
     * {date, from, received(optional), messageId}
     *
     * @param   array   $config
     * @return  int
     */
    public function search(array $config)
    {
        $result = $this->searchByDateAndFrom(
            $config['date'],
            $config['from']
        );

        if (empty($result) && isset($config['received'])) {
            $result = $this->searchByReceived($config['received']);
        }

        if (empty($result)) {
            // Not found
            return -1;
        }

        $mailOverview = imap_fetch_overview(
            $this->connection,
            implode(',', $result),
            FT_UID
        );

        if (1 == count($mailOverview)) {
            return $mailOverview[0]->uid;

        } else {
            // Got multiple result, match with messageId
            foreach ((array)$mailOverview as $mail) {
                if ($mail->message_id == $config['messageId']) {
                    return $mail->uid;
                }
            }

            return -1;
        }
    }


    /**
     * @param   string  $date
     * @param   string  $from
     * @return  array
     */
    protected function searchByDateAndFrom($date, $from)
    {
        $dateSince = date('d-M-Y', strtotime($date . '-1 day'));
        $dateBefore = date('d-M-Y', strtotime($date . '+1 day'));

        // Only double quote allowed in condition
        $condition = "SINCE \"$dateSince\" BEFORE \"$dateBefore\"";

        if (1 === preg_match('/<(.+)>/', $from, $match)) {
            $from = $match[1];
        }
        $from = addslashes($from);

        if (false !== strpos($from, '@')) {
            $condition .= " FROM \"$from\"";
        }

        return imap_search($this->connection, $condition, SE_UID);
    }


    /**
     * @param   string  $received
     * @return  array
     */
    protected function searchByReceived($received)
    {
        $receivedSince = date('d-M-Y', strtotime($received . '-1 day'));
        $receivedBefore = date('d-M-Y', strtotime($received . '+1 day'));

        // Only double quote allowed in condition
        $condition = "SINCE \"$receivedSince\" BEFORE \"$receivedBefore\"";

        return imap_search($this->connection, $condition, SE_UID);
    }
}
