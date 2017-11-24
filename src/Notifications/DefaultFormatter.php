<?php

/**
 * @copyright   (c) 2014-17, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Notifications;

use Vrok\Entity\Notification;
use Zend\View\HelperPluginManager;

/**
 * Basic formatter for all notifications that have no custom formatter set via
 * the event "getNotificationFormatter".
 */
class DefaultFormatter implements FormatterInterface
{
    /**
     * View Helper Manager for translating and formatting the message.
     * Must be injected before calling the getters.
     *
     * @var HelperPluginManager
     */
    protected $viewHelperManager = null;

    /**
     * Sets the ViewHelperManager instance to use.
     *
     * @param HelperPluginManager $vhm
     */
    public function __construct(HelperPluginManager $vhm)
    {
        $this->viewHelperManager = $vhm;
    }

    /**
     * {@inheritdoc}
     */
    public function getMailBodyHTML(Notification $notification): string
    {
        // implement automatic fallback here so subclasses don't have to worry
        // about it. getHtml/getTextLong still need to return null to save db space
        return $this->getHtml($notification) ?: (
                $this->getTextLong($notification) ?:
                        $this->getTextShort($notification));
    }

    /**
     * {@inheritdoc}
     */
    public function getMailBodyText(Notification $notification): string
    {
        // implement automatic fallback here so subclasses don't have to worry
        // about it. getTextLong still needs to return null to save db space
        return $this->getTextLong($notification) ?:
                    $this->getTextShort($notification);
    }

    /**
     * {@inheritdoc}
     */
    public function getMailSubject(Notification $notification): string
    {
        return $this->getTitle($notification);
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml(Notification $notification): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextLong(Notification $notification): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTextShort(Notification $notification): string
    {
        $translate = $this->viewHelperManager->get('translate');
        $params = $notification->getParams();
        $message = empty($params['message'])
            ? 'Notification has no message set!' // cannot use ID, called prePersist
            : $params['message'];
        return $translate([$message, $params]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(Notification $notification): string
    {
        $translate = $this->viewHelperManager->get('translate');
        return $translate('message.system.notification');
    }
}
