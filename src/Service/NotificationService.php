<?php

/**
 * @copyright   (c) 2014-17, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
use Vrok\Entity\Notification;
use Vrok\Entity\Filter\NotificationFilter;
use Vrok\Entity\Listener\NotificationListener;
use Vrok\Service\Email;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Http\Client;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Request;
use Zend\View\HelperPluginManager;

/**
 * Formats and sends notifications for the user.
 */
class NotificationService implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * Global flag wether or not the application should push HTTP notifications.
     *
     * @var bool
     */
    protected $httpNotificationsEnabled = true;

    /**
     * @var Email
     */
    protected $emailService = null;

    /**
     * @var \Doctrine\Orm\EntityManager
     */
    protected $entityManager = null;

    /**
     * @var HelperPluginManager
     */
    protected $viewHelperManager = null;

    /**
     * Class constructor - stores the given dependencies.
     *
     * @param HelperPluginManager $vhm
     */
    public function __construct(Email $es, EntityManager $em,
            HelperPluginManager $vhm)
    {
        $this->emailService = $es;
        $this->entityManager = $em;
        $this->viewHelperManager = $vhm;
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
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $sharedEvents = $events->getSharedManager();

        $this->listeners[] = $sharedEvents->attach(
            NotificationListener::class, // the postPersist event is triggered
                // by the Listener instead of the entity class itself
            NotificationListener::EVENT_NOTIFICATION_POSTPERSIST,
            [$this, 'onNotificationCreated'],
            $priority
        );
    }

    /**
     * Called when a notification was created
     * Sends it to the user if email/push notifications are enabled.
     *
     * @param EventInterface $e
     */
    public function onNotificationCreated(EventInterface $e)
    {
        $notification = $e->getTarget();
        /* @var $notification Notification */
        $user = $notification->getUser();

        // This notification is always sent by mail or the user has email
        // notifications enabled AND the notification can be mailed
        if ($notification->forceMail() ||
            ($notification->isMailable() && $user->getEmailNotificationsEnabled())
        ) {
            $this->sendEmail($notification);
        }

        // The applications allows HTTP push and the user has enabled it
        if ($this->httpNotificationsEnabled
            && $user->getHttpNotificationsEnabled()
        ) {
            $this->pushHttpNotification($notification);
        }
    }

    /**
     * Retrieve a array representation of the given notification.
     * Used for API results and HTTP notifications.
     *
     * @param Notification $notification
     * return array
     */
    public function convertNotificationToArray(Notification $notification) : array
    {
        $notification->setEntityManager($this->getEntityManager());
        $notification->setViewHelperManager($this->viewHelperManager);

        return [
            'messageHtml'      => $notification->getMessageHtml(),
            'messageTextLong'  => $notification->getMessageTextLong(),
            'messageTextShort' => $notification->getMessageTextShort(),
            'title'            => $notification->getTitle(),
            'timestamp'        => $notification->getCreatedAt()->format('U'),
            'type'             => get_class($notification),
            'parameters'       => $notification->getParams(),
        ];
    }

    /**
     * Push the given notification via POST to the URL the user specified.
     *
     * @param Notification $notification
     */
    protected function pushHttpNotification(Notification $notification)
    {
        $options = [
            'adapter'     => Curl::class,
            'curloptions' => [
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_MAXREDIRS      => 3,
            ],
        ];

        $user = $notification->getUser();
        if (!$user->getHttpNotificationCertCheck()) {
            $options['curloptions'][\CURLOPT_SSL_VERIFYPEER] = false;
            $options['curloptions'][\CURLOPT_SSL_VERIFYHOST] = false;
        }

        $client  = new Client($user->getHttpNotificationUrl(), $options);

        if ($user->getHttpNotificationUser() && $user->getHttpNotificationPw()) {
            $client->setAuth(
                $user->getHttpNotificationUser(),
                $user->getHttpNotificationPw(),
                Client::AUTH_BASIC
            );
        }

        $client->setMethod(Request::METHOD_POST);
        $client->setRawBody(json_encode([
            'notification' => $this->convertNotificationToArray($notification),
        ]));

        $response = null;
        try {
            $response = $client->send();
            \Doctrine\Common\Util\Debug::dump($response->getStatusCode(), 4);
            \Doctrine\Common\Util\Debug::dump($response->getBody(), 4);
        }
        catch (\Exception $e) {
            \Doctrine\Common\Util\Debug::dump($e->getMessage(), 4);
            // @todo new notification with the error
            // @todo log error? or only new notification for the user
        }

        if ($response && $response->getStatusCode() != 200) {
            //$body = $response->getBody();
            // @todo new notification with the error
            // @todo log error? or only new notification for the user
        }
    }

    /**
     * Sends the email with the notification to the user.
     *
     * @param Notification $notification
     */
    public function sendEmail(Notification $notification)
    {
        $notification->setEntityManager($this->getEntityManager());
        $notification->setViewHelperManager($this->viewHelperManager);
        $user = $notification->getUser();

        $mail = $this->emailService->createMail();
        $mail->setSubject($notification->getMailSubject());
        $mail->addTo($user->getEmail(true), $user->getDisplayName());

        $htmlPart = $mail->getHtmlPart($notification->getMailBodyHTML(), false, false);
        $textPart = $mail->getTextPart($notification->getMailBodyText(), false, true);
        $mail->setAlternativeBody($textPart, $htmlPart);

        $this->emailService->sendMail($mail);
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
