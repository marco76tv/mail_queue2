<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * +----------------------------------------------------------------------+
 * | PEAR :: Mail :: Queue2 :: MDB2 Container                             |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 Lorenzo Alberton                             |
 * +----------------------------------------------------------------------+
 * | All rights reserved.                                                 |
 * |                                                                      |
 * | Redistribution and use in source and binary forms, with or without   |
 * | modification, are permitted provided that the following conditions   |
 * | are met:                                                             |
 * |                                                                      |
 * | * Redistributions of source code must retain the above copyright     |
 * |   notice, this list of conditions and the following disclaimer.      |
 * | * Redistributions in binary form must reproduce the above copyright  |
 * |   notice, this list of conditions and the following disclaimer in    |
 * |   the documentation and/or other materials provided with the         |
 * |   distribution.                                                      |
 * | * The names of its contributors may be used to endorse or promote    |
 * |   products derived from this software without specific prior written |
 * |   permission.                                                        |
 * |                                                                      |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE       |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,  |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;     |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER     |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT   |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN    |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE      |
 * | POSSIBILITY OF SUCH DAMAGE.                                          |
 * +----------------------------------------------------------------------+
 * | Author: Lorenzo Alberton <l.alberton at quipo.it>                    |
 * +----------------------------------------------------------------------+
 */

/**
 * Storage driver for fetching mail queue data from a PEAR::MDB2 database
 *
 * This storage driver can use all databases which are supported
 * by the PEAR MDB2 abstraction layer.
 *
 * PHP Version 5
 *
 * @category Mail
 * @package  Mail_Queue2
 * @author   Lorenzo Alberton <l dot alberton at quipo dot it>
 * @version  SVN: $Id$
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link     http://pear.php.net/package/Mail_Queue2
 */

require_once 'Mail/Queue2/Exception.php';
require_once 'Mail/Queue2/Container.php';

require_once 'Mail/Queue2/Body.php';



/**
 * Mail_Queue_Container_MDB2
 *
 * @category Mail
 * @package  Mail_Queue2
 * @author   Lorenzo Alberton <l dot alberton at quipo dot it>
 * @version  Release: @package_version@
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link     http://pear.php.net/package/Mail_Queue
 */
class Mail_Queue2_Container_MDB2 extends Mail_Queue2_Container
{
    // {{{ class vars

    /**
     * Reference to the current database connection.
     * @var object PEAR::MDB2 instance
     */
    protected $db = null;

    /**
     * Table for sql database
     * @var  string
     */
    protected $mail_table = 'mail_queue';

    /**
     * @var string  the name of the sequence for this table
     */
    protected $sequence = null;

    protected $error_tpl = 'MDB2::query failed - "%s" - %s';

    // }}}
    // {{{ Mail_Queue_Container_mdb2()

    /**
     * Constructor
     *
     * Mail_Queue_Container_mdb2()
     *
     * @param mixed $options    An associative array of connection option.
     *
     * @return Mail_Queue2_Container_mdb2
     * @throws Mail_Queue2_Exception
     */
    public function __construct($options)
    {
        if (!is_array($options) || !isset($options['dsn'])) {
            throw new Mail_Queue_Exception('No dsn specified', Mail_Queue2::ERROR_NO_OPTIONS);
        }
        if (isset($options['mail_table'])) {
            $this->mail_table = $options['mail_table'];
        }
        $this->sequence = (isset($options['sequence']) ? $options['sequence'] : $this->mail_table);

        $dsn = array_key_exists('dsn', $options) ? $options['dsn'] : $options;
        $res = $this->_connect($dsn);

        if ($res !== true) {
            throw new Mail_Queue2_Exception('unknown error', Mail_Queue2::ERROR);
        }
        $this->setOption();
    }

    // }}}
    // {{{ _connect()

    /**
     * Connect to database by using the given DSN string
     *
     * @param mixed &$db DSN string | array | MDB2 object
     *
     * @return boolean
     * @throws Mail_Queue2_Exception
     */
    protected function _connect($db)
    {
        if (is_object($db) && is_a($db, 'MDB2_Driver_Common')) {
            $this->db = &$db;
        } elseif (is_string($db) || is_array($db)) {
            $this->db =& MDB2::connect($db);
        }
        if (is_object($this->db) && MDB2::isError($this->db)) {
            throw new Mail_Queue2_Exception($db->getDebugInfo(),
                Mail_Queue2::ERROR_CANNOT_CONNECT);
        }
        return true;
    }

    // }}}
    // {{{ _checkConnection()

    /**
     * Check if there's a valid db connection
     *
     * @return boolean
     * @throws Mail_Queue2_Exception Converted from PEAR_Error on error
     */
    protected function _checkConnection() {
        if (!is_object($this->db) || !is_a($this->db, 'MDB2_Driver_Common')) {
            $msg = 'MDB2::connect failed';
            if (PEAR::isError($this->db)) {
                $msg .= ': '.$this->db->getDebugInfo();
            }
            throw new Mail_Queue2_Exception($msg, Mail_Queue2::ERROR_CANNOT_CONNECT);
        }
        return true;
    }

    // }}}
    // {{{ _preload()

    /**
     * Preload mail to queue.
     *
     * @return mixed  True on success else Mail_Queue_Error object.
     * @access private
     */
    protected function _preload()
    {
        $res = $this->_checkConnection();

        $query = 'SELECT * FROM ' . $this->db->quoteIdentifier($this->mail_table)
                .' WHERE sent_time IS NULL AND try_sent < '. $this->retry
                .' AND time_to_send <= '.$this->db->quote(date('Y-m-d H:i:s'), 'timestamp')
                .' ORDER BY time_to_send';
        $this->db->setLimit($this->limit, $this->offset);
        $res = $this->query($query);

        $this->_last_item = 0;
        $this->queue_data = array(); //reset buffer

        $errors = array();

        while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {

            //var_dump($row['headers']);
            if (!is_array($row)) {
                $msg = sprintf($this->error_tpl,
                    $query,
                    MDB2::errorMessage($res));

                $errors[] = new Mail_Queue2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
                continue;
            }

            $body = new Mail_Queue2_Body;
            $body->setId($row['id'])
                ->setCreateTime($row['create_time'])
                ->setTimeToSend($row['time_to_send'])
                ->setSentTime($row['sent_time'])
                ->setIdUser($row['id_user'])
                ->setIp($row['ip'])
                ->setSender($row['sender'])
                ->setRecipient($this->_isSerialized($row['recipient']) ? unserialize($row['recipient']) : $row['recipient'])
                ->setHeaders(unserialize($row['headers']))
                ->setBody(unserialize($row['body']))
                ->setDeleteAfterSend($row['delete_after_send'])
                ->setTrySent($row['try_sent']);

            $this->queue_data[$this->_last_item] = $body;

            $this->_last_item++;
        }

        if (count($errors) > 0) {
            return $errors;
        }

        return true;
    }

    // }}}
    // {{{ put()

    /**
     * Put new mail in queue and save in database.
     *
     * Mail_Queue_Container::put()
     *
     * @param string $time_to_send  When mail have to be send
     * @param integer $id_user  Sender id
     * @param string $ip  Sender ip
     * @param string $from  Sender e-mail
     * @param string $to  Reciepient e-mail
     * @param string $hdrs  Mail headers (in RFC)
     * @param string $body  Mail body (in RFC)
     * @param bool $delete_after_send  Delete or not mail from db after send
     *
     * @return mixed  ID of the record where this mail has been put
     *                or Mail_Queue_Error on error
     * @access public
     **/
    public function put($time_to_send, $id_user, $ip, $sender,
                $recipient, $headers, $body, $delete_after_send=true)
    {
        $res = $this->_checkConnection();

        $id = $this->db->nextID($this->sequence);
        if (empty($id) || PEAR::isError($id)) {

            $_error = '';
            if (PEAR::isError($id)) {
                $_error .= $id->getDebugInfo();
            }

            $msg = sprintf($this->error_tpl,
                'Cannot create id in: ' . $this->sequence,
                $_error);

            throw new Mail_Queue2_Exception($msg, Mail_Queue2::ERROR);
        }
        $query = 'INSERT INTO '. $this->db->quoteIdentifier($this->mail_table)
                .' (id, create_time, time_to_send, id_user, ip'
                .', sender, recipient, headers, body, delete_after_send) VALUES ('
                .       $this->db->quote($id, 'integer')
                .', ' . $this->db->quote(date('Y-m-d H:i:s'), 'timestamp')
                .', ' . $this->db->quote($time_to_send, 'timestamp')
                .', ' . $this->db->quote($id_user, 'integer')
                .', ' . $this->db->quote($ip, 'text')
                .', ' . $this->db->quote($sender, 'text')
                .', ' . $this->db->quote($recipient, 'text')
                .', ' . $this->db->quote($headers, 'text')   //clob
                .', ' . $this->db->quote($body, 'text')      //clob
                .', ' . ($delete_after_send ? 1 : 0)
                .')';
        $res = $this->query($query);
        return $id;
    }

    // }}}
    // {{{ countSend()

    /**
     * Check how many times mail was sent.
     *
     * @param object  Mail_Queue_Body
     *
     * @return mixed  Integer or Mail_Queue_Error class if error.
     */
    public function countSend(Mail_Queue2_Body $mail)
    {
        $res = $this->_checkConnection();

        if (!($mail instanceof Mail_Queue2_Body)) {
            $msg = 'Unexpected error.';
            throw new Mail_Queue2_Error($msg, Mail_Queue2::ERROR_UNEXPECTED);
        }

        $count = $mail->raiseTry();
        $query = 'UPDATE ' . $this->db->quoteIdentifier($this->mail_table)
                .' SET try_sent = ' . $this->db->quote($count, 'integer')
                .' WHERE id = '     . $this->db->quote($mail->getId(), 'integer');
        $res = $this->query($query);
        return $count;
    }

    // }}}
    // {{{ setAsSent()

    /**
     * Set mail as already sent.
     *
     * @param object Mail_Queue2_Body object
     * @return bool
     * @access public
     */
    function setAsSent(Mail_Queue2_Body $mail)
    {
        $res = $this->_checkConnection();

        if (!($mail instanceof Mail_Queue2_Body)) {
            $msg = 'Unexpected error.';
            throw new Mail_Queue2_Error($msg, Mail_Queue2::ERROR_UNEXPECTED);
        }
        $query = 'UPDATE ' . $this->db->quoteIdentifier($this->mail_table)
                .' SET sent_time = '.$this->db->quote(date('Y-m-d H:i:s'), 'timestamp')
                .' WHERE id = '. $this->db->quote($mail->getId(), 'integer');

        $res = $this->query($query);
        return true;
    }

    // }}}
    // {{{ setup()

    /**
     * Setup necessary tables in the database.
     *
     * @return bool
     * @throws MDB2_Exception In case of database errors.
     *
     * @see  parent::setup()
     * @see  MDB2::setup()
     * @uses Mail_Queue2_Container_MDB2_Setup
     */
    public function setup()
    {
        $setup = new Mail_Queue2_Container_MDB2_Setup(
            $this->db,
            $this->mail_table,
            $this->error_tpl
        );
        $setup->setup();
    }

    // }}}
    // {{{ getMailById()

    /**
     * Return mail by id $id (bypass mail_queue)
     *
     * @param integer $id  Mail ID
     * @return Mail_Queue2_Body
     * @access public
     */
    public function getMailById($id)
    {
        $res = $this->_checkConnection();

        $query = 'SELECT * FROM ' . $this->db->quoteIdentifier($this->mail_table)
                .' WHERE id = '   . (int)$id;

        $row = $this->db->queryRow($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($row) || !is_array($row)) {
            $msg = 'MDB2: query failed - "' . $query . '" - ' . $row->getMessage();
            throw new Mail_Queue2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
        }
        $body = new Mail_Queue2_Body;
        $body->setId($row['id'])
            ->setCreateTime($row['create_time'])
            ->setTimeToSend($row['time_to_send'])
            ->setSentTime($row['sent_time'])
            ->setIdUser($row['id_user'])
            ->setIp($row['ip'])
            ->setSender($row['sender'])
            ->setRecipient($this->_isSerialized($row['recipient']) ? unserialize($row['recipient']) : $row['recipient'])
            ->setHeaders(unserialize($row['headers']))
            ->setBody(unserialize($row['body']))
            ->setDeleteAfterSend($row['delete_after_send'])
            ->setTrySent($row['try_sent']);

        return $body;
    }

    /**
     * Get the current amount of mails in the queue.
     *
     * @return int
     * @throws Mail_Queue2_Exception
     */
    public function getQueueCount()
    {
        $res = $this->_checkConnection();

        $sql  = "SELECT count(*) as queue_count";
        $sql .= " FROM " . $this->db->quoteIdentifier($this->mail_table);
        $row = $this->db->fetchRow($sql);
        if (PEAR::isError($row) || !is_array($row)) {
            $msg = 'MDB2: query failed - "'.$query.'" - '.$row->getMessage();
            throw new Mail_Queue2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
        }
        return (int) $row['queue_count'];
    }

    // }}}
    // {{{ deleteMail()

    /**
     * Remove from queue mail with $id identifier.
     *
     * @param integer $id  Mail ID
     * @return bool  True on success else Mail_Queue_Error class
     *
     * @access public
     */
    function deleteMail($id)
    {
        $res = $this->_checkConnection();

        $query = 'DELETE FROM ' . $this->db->quoteIdentifier($this->mail_table)
                .' WHERE id = ' . $this->db->quote($id, 'text');
        $res = $this->query($query);
        return true;
    }

    // }}}
    // {{{ Mail_Queue2_Container_mdb2_query

    /**
     * A wrapper around {@link self::$db::query()}. For easy error handling.
     *
     * @param string $query The SQL query.
     *
     * @return mixed
     * @throws Mail_Queue2_Exception In case of an error.
     */
    protected function query($query)
    {
        $res = $this->db->query($query);
        if (PEAR::isError($res)) {
            $msg = sprintf($this->error_tpl, $query, $res->getUserInfo());
            throw new Mail_Queue2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
        }
        return $res;
    }

    /// }}}
}
