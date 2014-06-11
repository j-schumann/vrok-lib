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
     *
     * @var string
     */
    protected $defaultSenderAddress = 'mail@example.com';

    /**
     * @var string
     */
    protected $defaultSenderName = null;

    /**
     * Retrieve the default email sender address.
     *
     * @return string
     */
    public function getDefaultSenderAddress()
    {
        return $this->defaultSenderAddress;
    }

    /**
     * Sets the default address to use as sender for any new email.
     *
     * @param string $address
     * @return self
     */
    public function setDefaultSenderAddress($address)
    {
        $this->defaultSenderAddress = $address;
        return $this;
    }

    /**
     * Retrieve the default email sender name.
     *
     * @return string
     */
    public function getDefaultSenderName()
    {
        return $this->defaultSenderName;
    }

    /**
     * Sets the default name to use as sender for any new email.
     *
     * @param string $name
     * @return self
     */
    public function setDefaultSenderName($name)
    {
        $this->defaultSenderName = $name;
        return $this;
    }

    /**
     * Returns a new Message instance and presets the From header with the
     * configured default.
     *
     * @return Message
     */
    public function createMail()
    {
        $mail = new Message($this->getServiceLocator()->get('translator'));
        $mail->setFrom($this->defaultSenderAddress, $this->defaultSenderName);
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
