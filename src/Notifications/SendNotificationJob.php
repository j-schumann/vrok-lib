<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Notifications;

use DateInterval;
use DateTime;
use SlmQueue\Job\AbstractJob;
use Vrok\Entity\Notification;
use Vrok\Service\Email;
use Vrok\Service\NotificationService;
use Zend\Http\Client;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Request;

/**
 * Sends the new notification to the user via mail and/or HTTP push.
 */
class SendNotificationJob extends AbstractJob
{
    /**
     * @var Vrok\Service\NotificationService
     */
    protected $notificationService = null;

    /**
     * @var \Vrok\Service\Email
     */
    protected $emailService = null;

    /**
     * Class constructor - save dependency
     *
     * @param NotificationService $ns
     */
    public function __construct(NotificationService $ns, Email $es)
    {
        $this->notificationService = $ns;
        $this->emailService  = $es;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $payload = $this->getContent();
        $notification = $this->notificationService->getNotificationRepository()
                ->find($payload['notificationId']);
        if (!$notification) {
            throw new \RuntimeException(
                    'Item '.$payload['notificationId'].' not found!');
        }

        $user = $notification->getUser();

        // This notification is always sent by mail or the user has email
        // notifications enabled AND the notification can be mailed
        if ($notification->isMailForced() ||
            ($notification->isMailable() && $user->getEmailNotificationsEnabled())
        ) {
            $this->sendEmail($notification);
        }

        // The applications allows HTTP push and the user has enabled it
        if ($notification->isPushable()
            && $this->notificationService->getHttpNotificationsEnabled()
            && $user->getHttpNotificationsEnabled()
        ) {
            $this->pushHttpNotification($notification);
        }
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
                // deactivated, causes "Error occurred during gzip inflation"
                // when reading the response body. Unicode character work anyways
                //\CURLOPT_ENCODING       => 'UTF-8',
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
            // when pushing we don't know which format the user needs so we sent
            // him all text&html versions
            'notification' => $notification->toArray(),
        ]));

        try {
            $response = $client->send();
        }
        catch (\Exception $e) {
            $this->createPushError($notification, [
                'message' => 'message.notification.pushFailure',
                'error'   => $e->getMessage(),
            ]);

            return;
        }

        if ($response->getStatusCode() != 200) {
            $body = trim(strip_tags($response->getBody()), " \r\n");
            $this->createPushError($notification, [
                'message' => 'message.notification.pushResponseFailure',
                'status'   => $response->getStatusCode(),
                'body'     => substr($body, 0, 50),
            ]);
        }
    }

    /**
     * Creates a new notification containing the error that occured when the
     * given notification was pushed.
     * Checks if 3 or more pushErrors occured within the last 12 hours, if yes
     * http push notifications are disabled for this user and another
     * notification created and forceMailed to the user.
     *
     * @param Notification $notification
     * @param arry $params
     */
    protected function createPushError(Notification $notification, array $params)
    {
        $user = $notification->getUser();

        $error = new Notification();
        $error->setMailable(true);
        $error->setPullable(true);
        $error->setUser($user);
        $error->setType('notificationPushError');
        $error->setParams($params);

        $em = $this->notificationService->getEntityManager();
        $em->persist($error);
        $em->flush();

        $timeFrame = new DateTime();
        $timeFrame->sub(new DateInterval('PT12H')); // @todo interval configurable?

        $filter = $this->notificationService->getNotificationFilter()
            ->byUser($user)
            ->byType('notificationPushError')
            ->createdAfter($timeFrame);

        $errorCount = $filter->getCount();
        if ($errorCount >= 3) { // @todo maxErrors configurable?
            $user->setHttpNotificationsEnabled(false);

            $pushDisabled = new Notification();
            $pushDisabled->setMailable(true);
            $pushDisabled->setMailForced(true);
            $pushDisabled->setPullable(true);
            $pushDisabled->setUser($user);
            $pushDisabled->setType('notificationPushDisabled');
            $pushDisabled->setParams([
                'message' => 'message.notification.tooManyPushErrors',
                // @todo give the used dateinterval as parameter?
            ]);

            $em->persist($pushDisabled);
            $em->flush();
        }
    }

    /**
     * Sends the email with the notification to the user.
     *
     * @param Notification $notification
     */
    public function sendEmail(Notification $notification)
    {
        $formatter = $this->notificationService
                ->getNotificationFormatter($notification->getType());
        $user = $notification->getUser();

        $mail = $this->emailService->createMail();
        $mail->setSubject($formatter->getMailSubject($notification));
        $mail->addTo($user->getEmail(true), $user->getDisplayName());

        $htmlPart = $mail->getHtmlPart(
                $formatter->getMailBodyHTML($notification), false, false);
        $textPart = $mail->getTextPart(
                $formatter->getMailBodyText($notification), false, true);
        $mail->setAlternativeBody($textPart, $htmlPart);

        $this->emailService->sendMail($mail);
    }
}
