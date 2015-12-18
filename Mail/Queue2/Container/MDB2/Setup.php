<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * +----------------------------------------------------------------------+
 * | PEAR :: Mail :: Queue2 :: MDB2 Container :: Setup                    |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 2009 Till Klampaeckel                                  |
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
 * | Author: Till Klampaeckel <till@php.net>                              |
 * +----------------------------------------------------------------------+
 */

/**
 * Setup scripts for MDB2-powered Mail_Queue2.
 *
 * PHP Version 5
 *
 * @category Mail
 * @package  Mail_Queue2
 * @author   Till Klampaeckel <till@php.net>
 * @version  SVN: $Id$
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link     http://pear.php.net/package/Mail_Queue2
 */

/**
 * Mail_Queue_Container_MDB2_Setup
 *
 * @category Mail
 * @package  Mail_Queue2
 * @author   Till Klampaeckel <till@php.net>
 * @version  Release: @package_version@
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @link     http://pear.php.net/package/Mail_Queue
 */
class Mail_Queue2_Container_MDB2_Setup
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
     * @var string
     */
    protected $error_tpl = 'MDB2::query failed - "%s" - %s';

    // }}}
    // {{{ __construct()

    /**
     * Constructor
     *
     * Mail_Queue_Container_mdb2()
     *
     * @param MDB2_Common $db    An associative array of connection option.
     * @param string      $table Name of the queue table.
     * @param string      $tpl   Template for errors, optional.
     *
     * @return Mail_Queue2_Container_MDB2_Setup
     */
    public function __construct(MDB2_Common $db, $table, $tpl = null)
    {
        $this->db         = $db;
        $this->mail_table = $table;

        if ($tpl !== null) {
            $this->error_tpl  = $tpl;
        }
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
     * @todo This is still very MySQL specific. Too much hardcoded.
     */
    public function setup()
    {
        $this->db->loadModule('Manager');

        // queue table
        $queue     = $this->getMailTableDef();
        $queue_opt = array(
            'comment' => 'The table to hold the queue.',
            'charset' => 'utf8',
            'collate' => 'uft8_unicode_ci',
            'type'    => 'innodb',
        );
        $this->createTable(
            $this->mail_table,
            $queue,
            $queue_opt
        );
        $this->createConstraint(
                $this->mail_table,
                'PRIMARY',
                array (
                    'primary' => true,
                    'fields' => array(
                        'id' => array()
                    )
                )
        );
        $this->createIndex(
            $this->mail_table,
            'time_to_send',
            array(
                'fields' => array('time_to_send' => array())
            )
        );
        $this->createIndex(
            $this->mail_table,
            'id_user',
            array(
                'fields' => array('id_user' => array())
            )
        );

        // auxiliary table
        $auxiliary     = $this->getAuxiliaryTableDef();
        $auxiliary_opt = array(
            'comment' => 'Locks mail IDs when they are being worked on.',
            'charset' => 'utf8', // FIXME
            'collate' => 'utf8_unicode_ci', // FIXME
            'type'    => 'innodb', // FIXME: hardcoded !!!
        );

        $auxiliary_table = $this->mail_table . '_blocking';

        $this->createTable(
            $auxiliary_table,
            $auxiliary,
            $auxiliary_opt
        );

       $this->createConstraint($auxiliary_table,
            'unq_mail',
            array(
                'unique' => true,
                'fields' => array('mail_id' => array())
            )
        );
    }

    // }}}

    protected function createConstraint($table, $name, $opts)
    {
        $result = $this->db->createConstraint($table, $name, $opts);
        if (MDB2::isError($result)) {
            $msg = sprintf($this->error_tpl, 'createConstraint', $result->getDebugInfo());
            throw new MDB2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
        }
    }

    protected function createIndex($table, $name, $opts)
    {
        $this->db->createIndex($table, $name, $opts);
        if (MDB2::isError($result)) {
            $msg = sprintf($this->error_tpl, 'createIndex', $result->getDebugInfo());
            throw new MDB2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
        }
    }

    protected function createTable($table, $def, $opts)
    {
        $result = $this->db->createTable(
            $table,
            $def,
            $opts
        );
        if (MDB2::isError($result)) {
            $msg = sprintf($this->error_tpl, 'createTable', $result->getDebugInfo());
            throw new MDB2_Exception($msg, Mail_Queue2::ERROR_QUERY_FAILED);
        }
    }

    protected function getAuxiliaryTableDef()
    {
        $auxiliary = array (
            'mail_id' => array (
                'type'     => 'bigint',
                'unsigned' => 1,
                'notnull'  => 1,
                'default'  => 0,
            ),
            'worker' => array (
                'type'   => 'text',
                'length' => 255
            ),
            'create_time' => array (
                'type' => 'datetime'
            ),
        );
        return $auxiliary;
    }

    protected function getMailQueueTableDef()
    {
        // queue table
        $queue = array(
            'id' => array(
                'type'     => 'bigint',
                'unsigned' => 1,
                'notnull'  => 1,
                'default'  => 0,
            ),
            'create_time' => array(
                'type'    => 'datetime',
                'notnull' => 1,
            ),
            'time_to_send' => array(
                'type'    => 'datetime',
                'notnull' => 1,
            ),
            'sent_time' => array(
                'type'    => 'datetime',
                'notnull' => 0,
                'default' => null,
            ),
            'id_user' => array(
                'type'     => 'bigint',
                'unsigned' => 1,
                'notnull'  => 0,
                'default'  => null,
            ),
            'ip' => array(
                'type'    => 'char',
                'length'  => 255,
                'default' => 'unknown',
            ),
            'worker' => array(
                'type'    => 'char',
                'length'  => 255,
                'default' => '',
            ),
            'recipient' => array(
                'type'    => 'char',
                'length'  => 255,
                'notnull' => 1,
            ),
            'headers' => array(
                'type'    => 'text',
                'notnull' => 0,
            ),
            'body' => array(
                'type'    => 'longtext',
                'notnull' => 0,
                'default' => null,
            ),
            'try_sent' => array(
                'type'    => 'tinyint',
                'length'  => 4,
                'notnull' => 1,
                'default' => 0,
            ),
            'delete_after_sent' => array(
                'type'    => 'tinyint',
                'length'  => 1,
                'notnull' => 1,
                'default' => 1,
            ),
        );

        return $queue;
    }
}
