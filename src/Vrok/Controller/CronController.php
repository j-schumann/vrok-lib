<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Controller;

use Vrok\Mvc\Controller\AbstractActionController;

/**
 * Console router to trigger the CRON events.
 */
class CronController extends AbstractActionController
{
    public function cronHourlyAction()
    {
        $log = $this->getServiceLocator()->get('ZendLog');
        /* @var $log \Zend\Log\Logger */
        $log->debug('cron-hourly');
    }

    public function cronDailyAction()
    {
        $log = $this->getServiceLocator()->get('ZendLog');
        /* @var $log \Zend\Log\Logger */
        $log->debug('cron-daily');
    }

    public function cronMonthlyAction()
    {
        $log = $this->getServiceLocator()->get('ZendLog');
        /* @var $log \Zend\Log\Logger */
        $log->debug('cron-monthly');
    }
}
