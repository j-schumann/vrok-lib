<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Controller;

use Zend\Mvc\Controller\AbstractActionController;

/**
 * Holds validation management functions.
 */
class ValidationController extends AbstractActionController
{
    /**
     * Console route to allow purging via CLI or cron job.
     */
    public function purgeAction()
    {
        $vm = $this->getServiceLocator()->get('ValidationManager');
        $vm->purgeValidations();
    }
}
