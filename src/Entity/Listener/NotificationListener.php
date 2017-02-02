<?php

/**
 * @copyright   (c) 2014-17, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Listener;

use Vrok\Entity\Notification;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

/**
 * Used to translate Doctrine events to ZF3 events.
 *
 * @IgnoreAnnotation("triggers")
 */
class NotificationListener implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    const EVENT_NOTIFICATION_PREPERSIST = 'notification.prePersist';
    const EVENT_NOTIFICATION_POSTPERSIST = 'notification.postPersist';

    /**
     * Triggers an event when a new notification is to be saved.
     *
     * @param Notification       $notification
     * @triggers notification.prePersist
     */
    public function prePersist(Notification $notification)
    {
        $this->getEventManager()->trigger(
            self::EVENT_NOTIFICATION_PREPERSIST,
            $notification
        );
    }

    /**
     * Triggers an event when a new notification was created.
     *
     * @param Notification       $notification
     * @triggers notification.postPersist
     */
    public function postPersist(Notification $notification)
    {
        $this->getEventManager()->trigger(
            self::EVENT_NOTIFICATION_POSTPERSIST,
            $notification
        );
    }
}
