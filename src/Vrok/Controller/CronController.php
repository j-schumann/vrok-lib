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
 * The console routes must be called by an actual cron script, the listeners should
 * execute short tasks directly or add their tasks as jobs to the job queue.
 */
class CronController extends AbstractActionController
{
    const EVENT_CRON_HOURLY  = 'cronHourly';
    const EVENT_CRON_DAILY   = 'cronDaily';
    const EVENT_CRON_MONTHLY = 'cronMonthly';

    /**
     * @triggers cronHourly
     */
    public function cronHourlyAction()
    {
        $this->getEventManager()->trigger(self::EVENT_CRON_HOURLY, $this);
    }

    /**
     * @triggers cronDaily
     */
    public function cronDailyAction()
    {
        $this->getEventManager()->trigger(self::EVENT_CRON_DAILY, $this);
    }

    /**
     * @triggers cronMonthly
     */
    public function cronMonthlyAction()
    {
        $this->getEventManager()->trigger(self::EVENT_CRON_MONTHLY, $this);
    }
}