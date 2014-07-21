<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue\Job;

use SlmQueue\Job\AbstractJob as SlmJob;
use SlmQueue\Queue\QueueAwareInterface;
use SlmQueue\Queue\QueueAwareTrait;

/**
 * Simplify job usage as we don't care about dependencies and don't want to create
 * a factory for each single job -> make the serviceLocator available to each job.
 */
abstract class AbstractJob extends SlmJob implements QueueAwareInterface
{
    use QueueAwareTrait;

    /**
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected function getServiceLocator()
    {
        return $this->getQueue()->getJobPluginManager()->getServiceLocator();
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    }
}
