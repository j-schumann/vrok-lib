<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
use Vrok\Entity\ActionLog;
use Vrok\Client\Info;
use Vrok\Entity\User;
use Zend\Authentication\AuthenticationServiceInterface;

/**
 * Allows to log actions to the database.
 */
class ActionLogger
{
    /**
     * @var AuthenticationServiceInterface
     */
    protected $authService = null;

    /**
     * @var ClientInfo
     */
    protected $clientInfo = null;

    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * Class constructor - stores the dependencies.
     *
     * @param EntityManager                  $entityManager
     * @param AuthenticationServiceInterface $authService
     * @param Info                           $clientInfo
     */
    public function __construct(
            EntityManager $entityManager,
            AuthenticationServiceInterface $authService,
            Info $clientInfo
    ) {
        $this->entityManager = $entityManager;
        $this->authService   = $authService;
        $this->clientInfo    = $clientInfo;
    }

    /**
     * Logs the given action to the database.
     *
     * @param string $action
     * @param string $content
     * @param int    $reactionTime
     * @param bool   $flush        if true the current unitOfWork is committed to the DB
     */
    public function logAction($action, $content = null, $reactionTime = 0, $flush = false)
    {
        $log = new ActionLog();
        $log->setAction($action);
        $log->setIpAddress($this->clientInfo->getIp());
        $log->setReactionTime($reactionTime);

        $user = $this->authService->getIdentity();
        if ($user instanceof User) {
            $log->setUser($user);
        }

        if ($content) {
            $log->setContent($content);
        }

        $this->entityManager->persist($log);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Retrieve the repository for the action log entries.
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getLogRepository()
    {
        return $this->entityManager->getRepository('Vrok\Entity\ActionLog');
    }
}
