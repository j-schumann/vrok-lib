<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue;

/**
 * For all module classes that want to inject factories into the slmQueue
 * JobManager via method instead of using the config (as config with closures
 * can not be cached).
 */
interface JobProviderInterface
{
    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|\Zend\ServiceManager\Config
     */
    public function getJobManagerConfig();
}
