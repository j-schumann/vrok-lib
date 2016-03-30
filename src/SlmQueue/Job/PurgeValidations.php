<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue\Job;

use Zend\EventManager\EventInterface;

/**
 * Purges all expired validations, triggers the validationExpired event for them.
 */
class PurgeValidations extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $manager = $this->getServiceLocator()->get('Vrok\Service\ValidationManager');
        $manager->purgeValidations();
    }

    /**
     * Adds himself to the jobqueue to purge all expired validations.
     * Listener is attached in the module onBootstap.
     *
     * @param EventInterface $e
     */
    public static function onCronDaily(EventInterface $e)
    {
        $controller = $e->getTarget();
        $qm         = $controller->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
        $queue      = $qm->get('jobs');
        $job        = $queue->getJobPluginManager()->get(__CLASS__);
        $queue->push($job);
    }
}
