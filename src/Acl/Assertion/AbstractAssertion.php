<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Acl\Assertion;

use Vrok\Service\UserManager;
use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Assertion\AssertionInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\Permissions\Acl\Role\RoleInterface;

/**
 * Base class for assertions that should be cached but require dependencies.
 * The dependencies can be fetched using a workaround with a static helper
 * which is initialized with a service manager instance onBootstrap.
 */
abstract class AbstractAssertion implements AssertionInterface
{
    /**
     * Retrieve the service manager instance.
     *
     * @return Zend\ServiceManager\ServiceLocatorInterface
     */
    protected function getServiceLocator()
    {
        return AssertionHelper::getServiceLocator();
    }

    /**
     * Retrieve the user manager.
     *
     * @return Vrok\Service\UserManager
     */
    protected function getUserManager()
    {
        return $this->getServiceLocator()->get(UserManager::class);
    }

    /**
     * Retrieve the currently logged in User or null.
     *
     * @return Vrok\Entity\User
     */
    protected function getIdentity()
    {
        return $this->getUserManager()->getCurrentUser();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function assert(
        Acl $acl,
        RoleInterface $role = null,
        ResourceInterface $resource = null,
        $privilege = null
    );
}
