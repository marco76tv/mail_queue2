<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * +----------------------------------------------------------------------+
 * | PEAR :: Mail :: Queue2 :: Body                                       |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2008 The PHP Group                                |
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
 *
 * PHP Version 5
 *
 * @category Mail
 * @package  Mail_Queue2
 * @author   Radek Maciaszek <chief@php.net>
 * @author   Lorenzo Alberton <l dot alberton at quipo dot it>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  CVS: $Id$
 * @link     http://pear.php.net/package/Mail_Queue2
 */

/**
 * Mail_Queue2_Body contains mail data
 *
 * @category Mail
 * @package  Mail_Queue2
 * @author   Radek Maciaszek <chief@php.net>
 * @author   Lorenzo Alberton <l dot alberton at quipo dot it>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Mail_Queue2
*/
class Mail_Queue2_Body
{

    /**
     * Ident
     *
     * @var integer
     */
    protected $id;

    /**
     * Create time
     *
     * @var string
     */
    protected $create_time;

    /**
     * Time to send mail
     *
     * @var string
     */
    protected $time_to_send;

    /**
     * Time when mail was sent
     *
     * @var string
     */
    protected $sent_time = null;

    /**
     * User id - who send mail
     * Mail_Queue2::UNKNOWN - not login user (guest)
     * Mail_Queue2::SYSTEM - mail send by system
     *
     * @var string
     */
    protected $id_user = Mail_Queue2::SYSTEM;

    /**
     * use IP
     *
     * @var string
     */
    protected $ip;

    /**
     * Sender email
     *
     * @var string
     */
    protected $sender;

    /**
     * Reciepient email
     *
     * @var string
     */
    protected $recipient;

    /**
     * Email headers (in RFC)
     *
     * @var string
     */
    protected $headers;

    /**
     * Email body (in RFC) - could have attachments etc
     *
     * @var string
     */
    protected $body;

    /**
     * How many times mail was sent
     *
     * @var integer
     */
    protected $try_sent = 0;

    /**
     * Delete mail from database after success send
     *
     * @var bool
     */
    protected $delete_after_send = true;

    /**
     * Mail_Queue2_Body::__construct() constructor
     *
     * @param Mail_Queue2_Container $container Optional.
     *
     * @return Mail_Queue2_Body
     * @see    self::setContainer()
     */
    public function __construct(Mail_Queue2_Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Public method to change/set the container used.
     *
     * @param Mail_Queue2_Container $container
     *
     * @return Mail_Queue2_Body
     */
    public function setContainer(Mail_Queue2_Container $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Mail_Queue2_Body::getId()
     *
     * @return integer  Sender id
     * @access public
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Return mail create time.
     *
     * Mail_Queue2_Body::getCreateTime()
     *
     * @return string  Mail create time
     * @access public
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    public function setCreateTime($create_time)
    {
        $this->create_time = $create_time;
        return $this;
    }

    public function setTimeToSend($time_to_send)
    {
        $this->time_to_send = $time_to_send;
        return $this;
    }


    /**
     * Return time to send mail.
     *
     * Mail_Queue2_Body::getTimeToSend()
     *
     * @return string  Time to send
     * @access public
     */
    function getTimeToSend()
    {
        return $this->time_to_send;
    }

    /**
     * Return mail sent time (if sended) else false.
     *
     * Mail_Queue2_Body::getSentTime()
     *
     * @return mixed  String sent time or false if mail not was sent yet
     * @access public
     */
    public function getSentTime()
    {
        return empty($this->sent_time) ? false : $this->sent_time;
    }

    public function setSentTime($sent_time)
    {
        $this->sent_time = $sent_time;
        return $this;
    }

    /**
     * Return sender id.
     *
     * Mail_Queue2_Body::getIdUser()
     *
     * @return integer  Sender id
     * @access public
     */
    public function getIdUser()
    {
        return $this->id_user;
    }

    public function setIdUser($id_user)
    {
        $this->id_user = $id_user;
        return $this;
    }

    /**
     * Return sender ip.
     *
     * Mail_Queue2_Body::getIp()
     *
     * @return string  IP
     * @access public
     */
    public function getIp()
    {
        return stripslashes($this->ip);
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Return sender e-mail.
     *
     * Mail_Queue2_Body::getSender()
     *
     * @return string E-mail
     * @access public
     */
    public function getSender()
    {
        return stripslashes($this->sender);
    }

    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Return recipient e-mail.
     *
     * Mail_Queue2_Body::getRecipient()
     *
     * @return string|array E-mail(s)
     * @access public
     */
    public function getRecipient()
    {
        if (is_array($this->recipient)) {
            $tmp_recipients = array();
            foreach ($this->recipient as $key => $value) {
                $tmp_recipients[$key] = stripslashes($value);
            }
            return $tmp_recipients;
        }
        return stripslashes($this->recipient);
    }

    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Return mail headers (in RFC)
     *
     * Mail_Queue2_Body::getHeaders()
     *
     * @return mixed array|string headers
     * @access public
     */
    public function getHeaders()
    {
        if (is_array($this->headers)) {
            $tmp_headers = array();
            foreach ($this->headers as $key => $value) {
                $tmp_headers[$key] = stripslashes($value);
            }
            return $tmp_headers;
        }
        return stripslashes($this->headers);
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Return mail body (in RFC)
     *
     * Mail_Queue2_Body::getBody()
     *
     * @return string  Body
     * @access public
     */
    public function getBody()
    {
        return stripslashes($this->body);
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Return how many times mail was try to sent.
     *
     * Mail_Queue2_Body::getTrySent()
     *
     * @return integer  How many times mail was sent
     * @access public
     */
    public function getTrySent()
    {
        return $this->try_sent;
    }

    public function setTrySent($try_sent)
    {
        $this->try_sent = $try_sent;
        return $this;
    }

    /**
     * Return true if mail must be delete after send from db.
     *
     * MailBody::isDeleteAfterSend()
     *
     * @return bool  True if must be delete else false.
     * @access public
     */
    public function isDeleteAfterSend()
    {
        return $this->delete_after_send;
    }

    public function setDeleteAfterSend($delete_after_send)
    {
        //if (!is_bool($delete_after_send)) {
        //    throw new InvalidArgumentException('$delete_after_send must be boolean.['.$delete_after_send.']');
        //}
        $delete_after_send= (bool) $delete_after_send;
        $this->delete_after_send = $delete_after_send;
        return $this;
    }


    /**
     * Load message from queue, to alter.
     */
    public function load($id)
    {
        $this->setId($id);

        // retrieve from container
        $mail = $this->container->getMailById($id);

        throw new Mail_Queue2_Exception("Not implemented.");
    }

    /**
     * Save message into queue.
     *
     * @return Mail_Queue2_Body
     */
    public function save()
    {
        if ($this->ip === null) {
            $this->ip = getenv('REMOTE_ADDR');
        }

        $time_to_send = date("Y-m-d H:i:s", time() + $this->time_to_send);

        $this->id = $this->container->put(
            $time_to_send,
            $this->id_user,
            $this->ip,
            $this->sender,
            serialize($this->recipient),
            serialize($this->headers),
            serialize($this->body),
            $this->delete_after_send
        );
        return true;
    }

    /**
     * Increase and return try_sent
     *
     * Mail_Queue2_Body::_try()
     *
     * @return integer  How many times mail was sent
     * @access public
     */
    protected function _try()
    {
        return ++$this->try_sent;
    }

    /**
     * Public interface to {@link self::_try()}.
     *
     * @return int
     * @uses   self::_try()
     */
    public function raiseTry()
    {
        return $this->_try();
    }
}
