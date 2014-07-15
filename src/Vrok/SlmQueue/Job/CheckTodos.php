<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue\Job;

use Zend\EventManager\EventInterface;

/**
 * Checks all open todos if the deadline was missed.
 */
class CheckTodos extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $todoService = $this->getServiceLocator()->get('Vrok\Service\Todo');
        $todoService->checkTodos();
    }

    /**
     * Adds himself to the jobqueue to check the todos.
     * Listener is attached in the module onBootstap.
     *
     * @param EventInterface $e
     */
    public static function onCronDaily(EventInterface $e)
    {
        $controller = $e->getTarget();
        $qm = $controller->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
        $queue = $qm->get('jobs');
        $job = $queue->getJobPluginManager()->get(__CLASS__);
        $queue->push($job);
    }
}
