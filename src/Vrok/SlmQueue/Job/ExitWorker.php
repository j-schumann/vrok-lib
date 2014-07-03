<?php

namespace Vrok\SlmQueue\Job;

use SlmQueue\Job\AbstractJob;

/**
 * We have no access to the supervisorctl from the web interface or the console as we
 * are in a chrooted environment. But we still need a method to restart the application
 * to reload changed configuration / changed code.
 * This job simply executes an exit which stops the executing worker so it is restarted
 * by supervisord.
 * We need to send it to the queue multiple times so it is processed by hopefully all
 * available workers before the restarted ones take over again. So there is no guarantee
 * all workers are really reloaded.
 */
class ExitWorker extends AbstractJob
{
    public function execute()
    {
        exit;
    }
}
