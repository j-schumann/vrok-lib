<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Allows to directly access the currently logged in user from the controller.
 */
class CurrentUser extends AbstractPlugin
{
    /**
     * Retrieve the currently logged in user or NULL.
     *
     * @return \Vrok\Entity\User
     */
    public function __invoke()
    {
        $um = $this->getController()->getServiceLocator()
                ->get('Vrok\Service\UserManager');

        return $um->getCurrentUser();
    }
}
