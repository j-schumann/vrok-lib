<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Vrok\Mail\Message;
use Zend\Mail\Message as ZendMessage;
use Zend\Mail\Transport\TransportInterface;
use Zend\View\HelperPluginManager as ViewHelperManager;

/**
 * Service for easy composing and sending of emails.
 */
class Email
{
    /**
     * Email address to set as default sender.
     *
     * @var string
     */
    protected $defaultSenderAddress = 'mail@example.com';

    /**
     * Name to set as default sender.
     *
     * @var string
     */
    protected $defaultSenderName = null;

    /**
     * Partial name to use as surrounding layout for the HTML email body.
     *
     * @var string
     */
    protected $layout = '';

    /**
     * @var TransportInterface
     */
    protected $transport = null;

    /**
     * View helper service locator.
     *
     * @var ViewHelperManager
     */
    protected $viewHelperManager = null;

    /**
     * Class constructor - sets the hard dependency.
     *
     * @param ViewHelperManager $vhm
     */
    public function __construct(TransportInterface $transport, ViewHelperManager $vhm)
    {
        $this->transport = $transport;
        $this->viewHelperManager = $vhm;
    }

    /**
     * Returns a new Message instance and presets the From header with the
     * configured default.
     *
     * @param bool $useLayout   if set true the configured default layout is injected
     *     into and used by the new message
     * @return Message
     */
    public function createMail($useLayout = true)
    {
        $mail = new Message($this->viewHelperManager);
        $mail->setFrom($this->defaultSenderAddress, $this->defaultSenderName);
        if ($useLayout && $this->layout) {
            $mail->setLayout($this->layout);
        }

        return $mail;
    }

    /**
     * Sends the given email using the default transport.
     *
     * @param ZendMessage $mail
     */
    public function sendMail(ZendMessage $mail)
    {
        // fixes errors in long running processes, e.g. queue worker, where
        // the SMTP server disconnects after some minutes and the transport
        // does not recognize this but returns "Could not read from [mailserver]"
        if (is_callable([$this->transport, 'disconnect'])) {
            $this->transport->disconnect();
        }

        $this->transport->send($mail);
    }

    /**
     * Allows to set multiple options as once.
     *
     * @param array $options
     */
    public function setOptions($options)
    {
        // @todo use Zend guards to check argument

        if (isset($options['default_sender_address'])) {
            $this->setDefaultSenderAddress($options['default_sender_address']);
        }
        if (isset($options['default_sender_name'])) {
            $this->setDefaultSenderName($options['default_sender_name']);
        }
        if (isset($options['layout'])) {
            $this->setLayout($options['layout']);
        }
    }

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
     * Retrieve the HTML mail layout partial.
     *
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * Sets the HTML mail layout partial
     *
     * @param string $layout
     * @return self
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }
}
