<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * +----------------------------------------------------------------------+
 * | PEAR :: Mail :: Queue2                                               |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2009 Radek Maciaszek, Lorenzo Alberton            |
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
 * @author   Lorenzo Alberton <l.alberton@quipo.it>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  SVN: $Id$
 * @link     http://pear.php.net/package/Mail_Queue2
 */

/**
 * Register autoload with SPL.
 */
//spl_autoload_register(array('Mail_Queue2', 'autoload'));

/**
 * Mail
 * @ignore
 */
//require_once 'Mail2.php';

/**
 * Mail_mime
 * @ignore
 */
require_once 'Mail/mime2.php';

/**
 * Mail_Queue2_Exception
 */
// require_once 'Mail/Queue2/Exception.php';

/**
 * Mail_Queue2 - base class for mail queue managment.
 *
 * @category Mail
 * @package  Mail_Queue
 * @author   Radek Maciaszek <chief@php.net>
 * @author   Lorenzo Alberton <l.alberton@quipo.it>
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/Mail_Queue2
 */
class Mail_Queue2 //implements ArrayIterator
{
    // {{{ Class vars

    /**
     * Mail options: smtp, mail etc. see Mail::factory
     *
     * @var array
     */
    protected $mail_options;

    /**
     * Mail_Queue2_Container
     *
     * @var object
     */
    protected $container;

    protected $containerSupported = array(
        'MDB2',
    );

    /**
     * Reference to Pear_Mail object
     *
     * @var object
     */
    protected $send_mail;

    /**
     * @var int
     */
    protected $position;

    // }}}
    // {{{ Error Constants

    /**
     * MAILQUEUE_ERROR constants
     * @global
     */
    const ERROR                   = -1;
    const ERROR_NO_DRIVER         = -2;
    const ERROR_NO_CONTAINER      = -3;
    const ERROR_CANNOT_INITIALIZE = -4;
    const ERROR_NO_OPTIONS        = -5;
    const ERROR_CANNOT_CONNECT    = -6;
    const ERROR_QUERY_FAILED      = -7;
    const ERROR_UNEXPECTED        = -8;
    const ERROR_CANNOT_SEND_MAIL  = -9;
    const ERROR_NO_RECIPIENT      = -10;
    const ERROR_UNKNOWN_CONTAINER = -11;
    const ERROR_NOT_IMPLEMENTED   = -12;

    // }}}
    // {{{ Default constants

    /**
     * This is special constant define start offset for limit sql queries to
     * get mails.
     * @global
     */
    const START = 0;

    /**
     * You can specify how many mails will be loaded to
     * queue else object use this constant for load all mails from db.
     * @global
     */
    const ALL = -1;

    /**
     * When you put new mail to queue you could specify user id who send e-mail.
     * Else you could use system id: MAILQUEUE_SYSTEM or user unknown id: MAILQUEUE_UNKNOWN
     * @global
     */
    const SYSTEM  = -1;
    const UNKNOWN = -2;

    /**
     * This constant tells Mail_Queue how many times should try
     * to send mails again if was any errors before.
     * @global
     */
    const RETRY = 25;

    // }}}
    // {{{ Mail_Queue

    /**
     * Mail_Queue constructor
     *
     * @param  array $container_options  Mail_Queue container options
     * @param  array $mail_options       How to send mails.
     *
     * @return Mail_Queue
     *
     * @access public
     */
    public function __construct(array $container_options, array $mail_options)
    {
        if (!is_array($mail_options) || !isset($mail_options['driver'])) {
            throw Mail_Queue2_Exception('No driver', self::ERROR_NO_DRIVER);
        }
        $this->mail_options = $mail_options;

        if (!is_array($container_options) || !isset($container_options['type'])) {
            throw new Mail_Queue2_Exception('No container', self::ERROR_NO_CONTAINER);
        }
       // $container_type      = strtoupper($container_options['type']);
        $container_type      = 'MDB2';
        $container_class     = 'Mail_Queue2_Container_' . $container_type;
        $container_classfile = $container_type . '.php';


        if (!class_exists($container_class)) {
            include_once 'Mail/Queue2/Container/' . $container_classfile;
        }
        if (!class_exists($container_class)) {
            echo '<br/>'.$container_class;muori();
            throw new Mail_Queue2_Exception('Unknown container', self::ERROR_UNKNOWN_CONTAINER);
        }

        //if (!in_array($container_type, $containerSupported)) {
        //    throw new Mail_Queue2_Exception('Unknown/Unavailable container', self::ERROR_UNKNOWN_CONTAINER);
        //}

        try {
            $this->container = new $container_class($container_options);
        } catch (Mail_Queue2_Exception $e) {
            throw $e; // do something nifty here
        }
    }

    // {{{ _Mail_Queue()

    /**
     * Mail_Queue desctructor
     *
     * @return void
     * @access public
     */
    public function __destruct()
    {
        unset($this);
    }

    // }}}
    // {{{ factorySendMail()

    /**
     * Provides an interface for generating Mail:: objects of various
     * types see Mail::factory().
     *
     * @return void
     */
    protected function factorySendMail()
    {
        $options = $this->mail_options;
        unset($options['driver']);

        $this->send_mail = Mail2::factory($this->mail_options['driver'], $options);
    }

    // }}}

    /**
     * Proxy method for Mail_Queue2_Body
     *
     * @return Mail_Queue2_Body
     * @see    self::getContainer
     */
    public function createQueueBody()
    {
        $entry = new Mail_Queue2_Body($this->container);
        return $entry;
    }

    /**
     * Return the container backend, e.g. for use with {@link Mail_Queue2_Body}.
     *
     * @return Mail_Queue2_Container
     * @uses   self::$container
     * @see    self::createQueueBody()
     */
    public function getContainer()
    {
        return $this->container;
    }

    // {{{ setBufferSize()

    /**
     * Keep memory usage under control. You can set the max number
     * of mails that can be in the preload buffer at any given time.
     * It won't limit the number of mails you can send, just the
     * internal buffer size.
     *
     * @param integer $size  Optional - internal preload buffer size
     *
     * @return Mail_Queue2
     */
    public function setBufferSize($size = 10)
    {
        $this->container->buffer_size = $size;
        return $this;
    }


    // }}}
    // {{{ sendMailsInQueue()

   /**
     * Send mails fom queue.
     *
     * Mail_Queue::sendMailsInQueue()
     *
     * @param integer $limit     Optional - max limit mails send.
     *                           This is the max number of emails send by
     *                           this function.
     * @param integer $offset    Optional - you could load mails from $offset (by id)
     * @param integer $retry     Optional - hoh many times mailqueu should try send
     *                           each mail. If mail was sent succesful it will be delete
     *                           from Mail_Queue.
     * @return mixed  True on success else MAILQUEUE_ERROR object.
     */
    public function sendMailsInQueue($limit = self::ALL, $offset = self::START,
                              $retry = self::RETRY, $callback = null)
    {
        $this->container->setOption($limit, $offset, $retry);
        while ($mail = $this->get()) {
            $this->container->countSend($mail);

            try {
                $result = $this->sendMail($mail, true);
                //take care of callback first, as it may need to retrieve extra data
                //from the mail_queue table.
                if ($callback !== null) {
                    call_user_func($callback,
                        array('id' => $mail->getId(),
                              'queued_as' => $this->queued_as,
                              'greeting'  => $this->greeting));
                }
                if ($mail->isDeleteAfterSend()) {
                    $this->deleteMail($mail->getId());
                }
            } catch (Mail_Queue2_Exception $e) {
                trigger_error($e->getMessage(), E_USER_NOTICE);
                $this->container->skip();
            }
        }
        if (!empty($this->mail_options['persist']) && is_object($this->send_mail)) {
            $this->send_mail->disconnect();
        }
        return true;
    }

    // }}}
    // {{{ sendMailById()

    /**
     * Send Mail by $id identifier. (bypass Mail_Queue2)
     *
     * @param integer $id          Mail identifier
     * @param bool    $set_as_sent A flag to mark sent email.
     *
     * @return bool   true on success else false
     * @throws Mail_Queue2_Exception
     */
    public function sendMailById($id, $set_as_sent=true)
    {
        $mail = $this->container->getMailById($id);
        return $this->sendMail($mail, $set_as_sent);
    }

    // }}}
    // {{{ sendMail()

    /**
     * Send mail from MailBody object
     *
     * @param object  MailBody object
     * @return bool   True on success
     * @param  bool   $set_as_sent
     *
     * @throws Mail_Queue2_Exception
     */
    public function sendMail($mail, $set_as_sent=true)
    {
        $recipient = $mail->getRecipient();
        if (empty($recipient)) {
            throw new Mail_Queue2_Exception('Recipient cannot be empty.',
                self::ERROR_NO_RECIPIENT);
        }

        $hdrs = $mail->getHeaders();
        $body = $mail->getBody();

        if (empty($this->send_mail)) {
            $this->factorySendMail();
        }
        if (PEAR::isError($this->send_mail)) {
            throw new Mail_Queue2_Exception($this->send_mail->getMessage(),
                self::ERROR_CANNOT_SEND_MAIL);
        }
        $sent = $this->send_mail->send($recipient, $hdrs, $body);
        if (PEAR::isError($sent)) {
            throw new Mail_Queue2_Exception($e->getMessage(),
                self::ERROR_CANNOT_SEND_MAIL);
        }
        if ($sent && $set_as_sent) {
            $this->container->setAsSent($mail);
        }
        if (isset($this->send_mail->queued_as)) {
            $this->queued_as = $this->send_mail->queued_as;
        }
        if (isset($this->send_mail->greeting)) {
            $this->greeting = $this->send_mail->greeting;
        }
        return $sent;
    }

    // }}}
    // {{{ get()

    /**
     * Get next mail from queue. The emails are preloaded
     * in a buffer for better performances.
     *
     * @return    object Mail_Queue_Container or error object
     * @throw     Mail_Queue2_Exception
     * @access    public
     */
    function get()
    {
        return $this->container->get();
    }

    // }}}

    // {{{ deleteMail()

    /**
     * Delete mail from queue database
     *
     * @param integer $id  Maila identifier
     * @return boolean
     *
     * @access private
     */
    function deleteMail($id)
    {
        return $this->container->deleteMail($id);
    }

    // }}}

    /**
     * Small autoload for Mail_Queue2.
     *
     * <code>
     * require_once 'Mail/Queue2.php';
     *
     * $queue = new Mail_Queue2();
     * $entry = $queue->createQueueBody();
     * $entry->setTo('hi@example.org')
     *    ->setFrom('hello@example.org')
     *    ->setBody('This is a test.');
     * $entry->save();
     * var_dump($entry->getStatus());
     * </code>
     *
     * @param string $className Name of the class.
     *
     * @return boolean
     * @see    spl_autoload_register()
     * @see    spl_autoload_unregister()
     */
    public static function autoload($className)
    {
        $filename = str_replace('_', '/', $className) . '.php';
        return require $fileName;
    }

    // {{{ errorMessage()

    /**
     * Return a textual error message for a Mail_Queue2 error code
     *
     * @param   int     $value error code
     * @return  string  error message, or false if the error code was
     *                  not recognized
     * @access public
     */
    public static function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                self::ERROR                    => 'unknown error',
                self::ERROR_NO_DRIVER          => 'No mail driver specified',
                self::ERROR_NO_CONTAINER       => 'No container specified',
                self::ERROR_CANNOT_INITIALIZE  => 'Cannot initialize container',
                self::ERROR_NO_OPTIONS         => 'No container options specified',
                self::ERROR_CANNOT_CONNECT     => 'Cannot connect to database',
                self::ERROR_QUERY_FAILED       => 'db query failed',
                self::ERROR_UNEXPECTED         => 'Unexpected class',
                self::ERROR_CANNOT_SEND_MAIL   => 'Cannot send email',
            );
        }

        if ($value instanceof Mail_Queue2_Exception) {
            $value = $value->getCode();
        }

        return isset($errorMessages[$value]) ?
           $errorMessages[$value] : $errorMessages[self::ERROR];
    }

    // }}}

    /**
     * Setup creates tables and what not, call this once. :-)
     */
    public function setup()
    {
        $this->container->db;
    }

    // {{{ ArrayIterator

    public function append($value)
    {
        throw new Mail_Queue2_Exception('Not implemented.',
            Mail_Queue2::ERROR_NOT_IMPLEMENTED);
    }

    public function count()
    {
        return $this->container->getQueueCount();
    }

    public function current()
    {
        return $this->container->queue_data[$this->position];
    }

    public function getArrayCopy()
    {
        return $this->container->queue_data;
    }

    public function getFlags()
    {
        throw new Mail_Queue2_Exception('Not implemented.',
            Mail_Queue2::ERROR_NOT_IMPLEMENTED);
    }

    public function key()
    {
        return $this->position;
    }

    public function ksort()
    {
        ksort($this->container->queue_data);
    }

    public function natcasesort()
    {
        throw new Mail_Queue2_Exception('Not implemented.',
            Mail_Queue2::ERROR_NOT_IMPLEMENTED);
    }

    public function natsort()
    {
        throw new Mail_Queue2_Exception('Not implemented.',
            Mail_Queue2::ERROR_NOT_IMPLEMENTED);
    }

    public function next()
    {
        return ++$this->position;
    }

    public function offsetExists($index)
    {
        return isset($this->container->queue_data[$index]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function seek($position)
    {
        $this->position = $position;
    }

    public function valid()
    {
        return isset($this->container->queue_data[$this->position]);
    }

    // }}}
     /**
     * Put new mail in queue.
     *
     * @see Mail_Queue_Container::put()
     *
     * @param string  $time_to_send  When mail have to be send
     * @param integer $id_user  Sender id
     * @param string  $ip    Sender ip
     * @param string  $from  Sender e-mail
     * @param string|array  $to    Reciepient(s) e-mail
     * @param string  $hdrs  Mail headers (in RFC)
     * @param string  $body  Mail body (in RFC)
     * @return mixed  ID of the record where this mail has been put
     *                or Mail_Queue_Error on error
     *
     * @access public
     */
    function put($from, $to, $hdrs, $body, $sec_to_send=0, $delete_after_send=true, $id_user=-1)
    {
        $ip = getenv('REMOTE_ADDR');

        $time_to_send = date("Y-m-d H:i:s", time() + $sec_to_send);

        return $this->container->put(
            $time_to_send,
            $id_user,
            $ip,
            $from,
            serialize($to),
            serialize($hdrs),
            serialize($body),
            $delete_after_send
        );
    }




}
