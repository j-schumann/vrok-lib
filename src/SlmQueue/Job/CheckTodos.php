<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue\Job;

use SlmQueue\Job\AbstractJob;
use Zend\EventManager\EventInterface;

/**
 * Checks all open todos if the deadline was missed.
 */
class CheckTodos extends AbstractJob
{
    /**
     * @var Vrok\Service\Todo
     */
    protected $todoService = null;

    /**
     * Class constructor - save dependency
     *
     * @param \Vrok\Service\Todo $ts
     */
    public function __construct(\Vrok\Service\Todo $ts)
    {
        $this->todoService = $ts;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->todoService->checkTodos();
    }

    /**
     * Adds himself to the jobqueue to check the todos.
     * Listener is attached in the module onBootstap.
     *
     * @todo push without instantiation
     * @param EventInterface $e
     */
    public static function onCronDaily(EventInterface $e)
    {
        $controller = $e->getTarget();
        $qm         = $controller->getServiceLocator()
                ->get('SlmQueue\Queue\QueuePluginManager');
        $queue      = $qm->get('jobs');
        $job        = $queue->getJobPluginManager()->get(__CLASS__);
        $queue->push($job);
    }
}
