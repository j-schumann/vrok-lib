<?php

/**
 * @copyright   (c) 2014-17, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
use SlmQueue\Controller\Plugin\QueuePlugin;
use Vrok\Entity\Notification;
use Vrok\Entity\Filter\NotificationFilter;
use Vrok\Entity\Listener\NotificationListener;
use Vrok\Notifications\FormatterInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;

/**
 * Formats and sends notifications for the user.
 */
class NotificationService
    implements EventManagerAwareInterface, ListenerAggregateInterface
{
    use EventManagerAwareTrait;
    use ListenerAggregateTrait;

    const EVENT_GET_NOTIFICATION_FORMATTER = 'getNotificationFormatter';

    /**
     * Global flag wether or not the application should push HTTP notifications.
     *
     * @var bool
     */
    protected $httpNotificationsEnabled = true;

    /**
     * @var \Doctrine\Orm\EntityManager
     */
    protected $entityManager = null;

    /**
     * @var QueuePlugin
     */
    protected $queue = null;

    /**
     * Hash containing all used strategies to avoid triggering the event multiple times.
     *
     * @var array
     */
    protected $formatters = [];

    /**
     * Class constructor - stores the given dependencies.
     *
     * @param Vrok\Service\Email $es
     * @param \Doctrine\Orm\EntityManager $em
     */
    public function __construct(EntityManager $em, QueuePlugin $queue)
    {
        $this->entityManager = $em;
        $this->queue         = $queue;
    }

    /**
     * Sets multiple config options at once.
     *
     * @todo validate $config
     *
     * @param array $config
     */
    public function setOptions(array $config) // @todo how to allow ArrayAccess?
    {
        if (isset($config['http_notifications_enabled'])) {
            $this->setHttpNotificationsEnabled(
                    (bool)$config['http_notifications_enabled']);
        }
    }

    /**
     * Sets wether the application wants to support http notifications.
     *
     * @param bool $value
     */
    public function setHttpNotificationsEnabled(bool $value)
    {
        $this->httpNotificationsEnabled = $value;
    }

    /**
     * Returns wether the application wants to support http notifications.
     *
     * @return bool
     */
    public function getHttpNotificationsEnabled() : bool
    {
        return $this->httpNotificationsEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents = $events->getSharedManager();

        $this->listeners[] = $sharedEvents->attach(
            NotificationListener::class, // the prePersist event is triggered
                // by the Listener instead of the entity class itself
            NotificationListener::EVENT_NOTIFICATION_PREPERSIST,
            [$this, 'onNotificationPrePersist'],
            $priority
        );

        $this->listeners[] = $sharedEvents->attach(
            NotificationListener::class, // the postPersist event is triggered
                // by the Listener instead of the entity class itself
            NotificationListener::EVENT_NOTIFICATION_POSTPERSIST,
            [$this, 'onNotificationPostPersist'],
            $priority
        );
    }

    /**
     * Retrieve the formatter to use for the given notification type.
     *
     * @triggers getNotificationFormatter
     * @param string $type  the notification type which should be formatted
     * @return FormatterInterface
     * @throws Exception\RuntimeException when no formatter was found
     */
    public function getNotificationFormatter(string $type) : FormatterInterface
    {
        if (isset($this->formatters[$type])) {
            return $this->formatters[$type];
        }

        // try to find a formatter that feels responsible for the given type
        $results = $this->getEventManager()->triggerUntil(
            function ($result) {
                return $result instanceof FormatterInterface;
            },
            self::EVENT_GET_NOTIFICATION_FORMATTER,
            $this,
            ['type' => $type]
        );

        if ($results->stopped()) {
            $this->formatters[$type] = $results->last();
            return $this->formatters[$type];
        }

        throw new Exception\RuntimeException(
                'No notification formatter for type "'.$type.'" found!');
    }

    /**
     * Called when a notification is to be saved. Renders and sets the messages.
     *
     * @param EventInterface $e
     * @throws Exception\RuntimeException when the notification has no user set
     */
    public function onNotificationPrePersist(EventInterface $e)
    {
        $notification = $e->getTarget();
        /* @var $notification Notification */

        $formatter = $this->getNotificationFormatter($notification->getType());

        $notification->setHtml($formatter->getHtml($notification));
        $notification->setTextLong($formatter->getTextLong($notification));
        $notification->setTextShort($formatter->getTextShort($notification));
        $notification->setTitle($formatter->getTitle($notification));
    }

    /**
     * Called when a notification was created
     * Creates the job which will send it to the user if email/push
     * notifications are enabled.
     *
     * @param EventInterface $e
     * @throws Exception\RuntimeException when the notification has no user set
     */
    public function onNotificationPostPersist(EventInterface $e)
    {
        $notification = $e->getTarget();
        /* @var $notification Notification */

        $qh = $this->queue;
        $qh('jobs')->push('Vrok\Notifications\SendNotificationJob', [
            'notificationId' => $notification->getId(),
        ]);
    }

    /**
     * Retrieve the entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Retrieve the notification repository instance.
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getNotificationRepository()
    {
        return $this->getEntityManager()->getRepository(Notification::class);
    }

    /**
     * Retrieve a new notification filter instance.
     *
     * @param string $alias the alias for the notification record
     *
     * @return NotificationFilter
     */
    public function getNotificationFilter($alias = 'n') : NotificationFilter
    {
        $qb     = $this->getNotificationRepository()->createQueryBuilder($alias);
        $filter = new NotificationFilter($qb);

        return $filter;
    }
}
