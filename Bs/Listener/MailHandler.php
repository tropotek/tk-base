<?php
namespace Bs\Listener;

use Tk\Event\Subscriber;


/**
 * @author Michael Mifsud <http://www.tropotek.com/>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class MailHandler implements Subscriber
{

    /**
     * Add any 'mail.bcc' addresses from the config
     * These addresses will be added to the BCC of any message sent from the EMS
     *
     * @param \Tk\Mail\MailEvent $event
     */
    public function preSend($event)
    {
        $message = $event->getMessage();
        $config = \Bs\Config::getInstance();

        if ($message) {
            if (is_array($config['mail.bcc'])) {
                $recip = $message->getRecipients();
                foreach ($config['mail.bcc'] as $email) {
                    if (in_array($email, $recip)) continue;
                    if (!\Tk\Mail\Message::isValidEmail($email)) continue;
                    $message->addBcc($email);
                }
            }
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            \Tk\Mail\MailEvents::PRE_SEND => array('preSend', 10)
        );
    }

}


