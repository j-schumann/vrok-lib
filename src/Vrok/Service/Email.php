<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Vrok\Mail\Message;
use Zend\Mail\Message as ZendMessage;
use Zend\Mail\Transport;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Service for easy composing and sending of emails.
 */
class Email implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Returns a new Message instance and presets the From header with the
     * configured default.
     *
     * @return Message
     */
    public function createMail()
    {
        $mail = new Message($this->getServiceLocator()->get('translator'));
        $mail->setFrom('info@ellievantgrey.com');
        return $mail;
    }

    /**
     * Sends the given email using the default transport.
     *
     * @param ZendMessage $mail
     */
    public function sendMail(ZendMessage $mail)
    {
        $transport = new Transport\Sendmail();
        $transport->send($mail);
    }
}
