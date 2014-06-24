<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class ValidationController extends AbstractActionController
{
    public function purgeAction()
    {
        $vm = $this->getServiceLocator()->get('Vrok\Validation\Manager');
        $vm->purgeValidations();
    }
}
