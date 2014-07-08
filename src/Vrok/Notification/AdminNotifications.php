<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Notification;

use Zend\EventManager\EventInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Listens to system events and notifies the administrators about them.
 */
class AdminNotifications implements ListenerAggregateInterface, ServiceLocatorAwareInterface
{
    use ListenerAggregateTrait;
    use ServiceLocatorAwareTrait;

    /**
     * Attaches to the shared eventmanager to listen for all events of interest for this
     * handler.
     *
     * @param \Zend\EventManager\EventManagerInterface $events
     */
    public function attach(\Zend\EventManager\EventManagerInterface $events)
    {
        $shared = $events->getSharedManager();

        $shared->attach(
            'Vrok\Controller\SlmQueueController',
            \Vrok\Controller\SlmQueueController::EVENT_BURIEDJOBSFOUND,
            array($this, 'onBuriedJobsFound')
        );

        $shared->attach(
            'Vrok\Controller\SlmQueueController',
            \Vrok\Controller\SlmQueueController::EVENT_LONGRUNNINGJOBSFOUND,
            array($this, 'onLongRunningJobsFound')
        );

        $shared->attach(
            'SupervisorControl\Controller\ConsoleController',
            \SupervisorControl\Controller\ConsoleController::EVENT_PROCESSNOTRUNNING,
            array($this, 'onProcessNotRunning')
        );

        /*
         * Currently we can not detect the jobs process result:
         * @todo https://github.com/juriansluiman/SlmQueue/pull/83
        $shared->attach(
            'SlmQueue\Worker\WorkerInterface',
            \SlmQueue\Worker\WorkerEvent::EVENT_PROCESS_JOB_POST,
            array($this, 'onProcessJobPost')
        );*/
    }

    /**
     * Sends a notification email to all queueAdmins reporting the number of buried
     * jobs remaining in the queue.
     *
     * @param \Zend\EventManager\EventInterface $e
     * @throws \RuntimeException when the queueAdmin group does not exist
     */
    public function onBuriedJobsFound(EventInterface $e)
    {
        $queue = $e->getTarget();
        $count = $e->getParam('count');

        $emailService = $this->serviceLocator->get('Vrok\Service\Email');
        $url = $this->serviceLocator->get('viewhelpermanager')->get('url');
        $fullUrl = $this->serviceLocator->get('viewhelpermanager')->get('FullUrl');

        $mail = $emailService->createMail();
        $mail->setSubject('mail.slmQueue.buriedJobsFound.subject');

        $mail->setHtmlBody(array('mail.slmQueue.buriedJobsFound.body', array(
            'queueName' => $queue->getName(),
            'count' => $count,
            'queueUrl' => $fullUrl('https').$url('slm-queue/list-buried', array(
                'name' => $queue->getName()
            )),
        )));

        $userManager = $this->serviceLocator->get('Vrok\User\Manager');
        $group = $userManager->getGroupRepository()
                ->findOneBy(array('name' => 'queueAdmin'));

        if (!$group) {
            throw new \RuntimeException(
                'Group "queueAdmin" not found when buried jobs where found!');
        }

        $admins  = $group->getMembers();
        foreach($admins as $user) {
            $mail->addTo($user->getEmail(), $user->getDisplayName());
        }

        $emailService->sendMail($mail);
    }

    /**
     * Sends a notification email to all queueAdmins reporting the number of long running
     * jobs remaining in the queue.
     *
     * @param \Zend\EventManager\EventInterface $e
     * @throws \RuntimeException when the queueAdmin group does not exist
     */
    public function onLongRunningJobsFound(EventInterface $e)
    {
        $queue = $e->getTarget();
        $count = $e->getParam('count');

        $emailService = $this->serviceLocator->get('Vrok\Service\Email');
        $url = $this->serviceLocator->get('viewhelpermanager')->get('url');
        $fullUrl = $this->serviceLocator->get('viewhelpermanager')->get('FullUrl');

        $mail = $emailService->createMail();
        $mail->setSubject('mail.slmQueue.longRunningJobsFound.subject');

        $mail->setHtmlBody(array('mail.slmQueue.longRunningJobsFound.body', array(
            'queueName' => $queue->getName(),
            'count' => $count,
            'queueUrl' => $fullUrl('https').$url('slm-queue/list-running', array(
                'name' => $queue->getName()
            )),
        )));

        $userManager = $this->serviceLocator->get('Vrok\User\Manager');
        $group = $userManager->getGroupRepository()
                ->findOneBy(array('name' => 'queueAdmin'));

        if (!$group) {
            throw new \RuntimeException(
                'Group "queueAdmin" not found when buried jobs where found!');
        }

        $admins  = $group->getMembers();
        foreach($admins as $user) {
            $mail->addTo($user->getEmail(), $user->getDisplayName());
        }

        $emailService->sendMail($mail);
    }

    /**
     * Sends a notification to all supervisorAdmins when a process is not running.
     *
     * @param EventInterface $e
     */
    public function onProcessNotRunning(EventInterface $e)
    {
        $processName = $e->getParam('processName');
        $processInfo = $e->getParam('info');

        $emailService = $this->serviceLocator->get('Vrok\Service\Email');
        $url = $this->serviceLocator->get('viewhelpermanager')->get('url');
        $fullUrl = $this->serviceLocator->get('viewhelpermanager')->get('FullUrl');
        $dateFormat = $this->serviceLocator->get('viewhelpermanager')->get('DateFormat');

        $mail = $emailService->createMail();
        $mail->setSubject('mail.supervisor.processNotRunning.subject');

        $mail->setHtmlBody(array('mail.supervisor.processNotRunning.body', array(
            'processName' => $processName,
            'processState' => $processInfo ? $processInfo['statename'] : 'NOT_FOUND',
            'now' => $dateFormat(new \DateTime(),
                    \IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM),
            'supervisorUrl' => $fullUrl('https').$url('supervisor'),
        )));

        $userManager = $this->serviceLocator->get('Vrok\User\Manager');
        $group = $userManager->getGroupRepository()
                ->findOneBy(array('name' => 'supervisorAdmin'));

        if (!$group) {
            throw new \RuntimeException(
                'Group "supervisorAdmin" not found when a process was not running!');
        }

        $admins  = $group->getMembers();
        foreach($admins as $user) {
            $mail->addTo($user->getEmail(), $user->getDisplayName());
        }

        $emailService->sendMail($mail);
    }

    /**
     *
     * @todo https://github.com/juriansluiman/SlmQueue/pull/83
     * @param \Zend\EventManager\EventInterface $e
     */
    public function onProcessJobPost(EventInterface $e)
    {
        \Doctrine\Common\Util\Debug::dump($e, 4);
        \Doctrine\Common\Util\Debug::dump($e->getJob(), 4);
        $log = $this->serviceLocator->get('ZendLog');
        /* @var $log \Zend\Log\Logger */

        $log->debug(get_class($e));
        $log->debug(get_class($e->getTarget()));
        $log->debug(get_class($e->getParam('job')));

    }
}
