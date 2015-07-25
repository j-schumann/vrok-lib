<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue\Job;

use SlmQueue\Job\AbstractJob;

/**
 * This job simply executes an exit which stops the executing worker.
 *
 * Can be used if workers are handled & restarted by supervisord to reload changed
 * configuration / changed code.
 * Only useful when there is only one worker assigned for the queue as we can not
 * guarantee that each worker processes one ExitWorker job even if we send multiple jobs
 * to the queue because there may be other long running processes so a restarted worker
 * catches another ExitWorker job again.
 */
class ExitWorker extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // status code != 0 triggers restart by supervisor,
        // 0 would be interpreted as expected exit (State: EXITED)
        exit(1);
    }
}
