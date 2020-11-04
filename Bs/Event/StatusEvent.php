<?php
namespace Bs\Event;

use Bs\Db\Status;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class StatusEvent extends \Tk\Event\Event
{

    /**
     * @var Status
     */
    protected $status = null;

    /**
     * @var array
     */
    protected $messageList = array();


    /**
     * constructor.
     *
     * @param Status $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * If false events should not send emails to external users.
     *
     * @return bool
     */
    public function getNotify()
    {
        return $this->status->notify;
    }

    /**
     * @param $message
     * @return $this
     */
    public function addMessage($message)
    {
        $this->messageList[] = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getMessageList()
    {
        return $this->messageList;
    }

    /**
     * @param array $list
     * @return $this
     */
    public function setMessageList($list = array())
    {
        $this->messageList = $list;
        return $this;
    }
}