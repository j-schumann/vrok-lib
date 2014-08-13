<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Controller;

use SlmQueue\Queue\QueuePluginManager;
use SlmQueueDoctrine\Queue\DoctrineQueue;
use Vrok\Mvc\Controller\AbstractActionController;

/**
 * Allows administrators to inspect the jobs waiting in the configured (Doctrine) queues,
 * delete and restart them.
 * Can check for buried/long running jobs, e.g. via Cron, and triggers events if matching
 * jobs where found to send notifications etc.
 */
class SlmQueueController extends AbstractActionController
{
    const EVENT_BURIEDJOBSFOUND      = 'buriedJobsFound';
    const EVENT_LONGRUNNINGJOBSFOUND = 'longRunningJobsFound';

    /**
     * @return QueuePluginManager
     */
    public function getQueueManager()
    {
        return $this->getServiceLocator()->get('SlmQueue\Queue\QueuePluginManager');
    }

    /**
     * Retrieve all configured DoctrineQueues.
     *
     * @return DoctrineQueue[]
     */
    protected function getQueues()
    {
        $qm = $this->getQueueManager();

        // each queue must have an entry in [slm_queue][queue_manager][factories]
        // -> get those
        $services = $qm->getRegisteredServices();

        $queues = array();
        foreach($services['factories'] as $name) {
            $queue = $qm->get($name);

            // we support only Doctrine queues as we have no functionality to receive
            // a list of jobs for the other queue types.
            if ($queue instanceof DoctrineQueue) {
                $queues[] = $queue;
            }
        }

        return $queues;
    }

    /**
     * Shows a list of all configured (Doctrine) queues and the number of jobs.
     *
     * @return ViewModel
     */
    public function indexAction( )
    {
        $queues = $this->getQueues();
        foreach($queues as $key => $queue) {
            $connection = $queue->connection;
            /* @var $connection \Doctrine\DBAL\Connection */

            $sql = 'SELECT COUNT(*) FROM ' . $queue->getOptions()->getTableName()
                    .' WHERE status = ? AND queue = ?';
            $pendingCount = $connection->fetchColumn($sql, array(
                DoctrineQueue::STATUS_PENDING, $queue->getName()), 0);
            $runningCount = $connection->fetchColumn($sql, array(
                DoctrineQueue::STATUS_RUNNING, $queue->getName()), 0);
            $buriedCount = $connection->fetchColumn($sql, array(
                DoctrineQueue::STATUS_BURIED, $queue->getName()), 0);

            $queues[$key] = array(
                'name'    => $queue->getName(),
                'pending' => $pendingCount,
                'running' => $runningCount,
                'buried'  => $buriedCount,
            );
        }


        return $this->createViewModel(array(
            'queues' => $queues,
        ));
    }

    /**
     * Send a "recover" command to the queue to restart all jobs that are running longer
     * than the entered number of minutes.
     *
     * @return ViewModel|Response
     */
    public function recoverAction()
    {
        $name = $this->params('name');
        $qm = $this->getQueueManager();
        if (!$qm->has($name)) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.queueNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $form = $this->getServiceLocator()->get('FormElementManager')
                ->get('Vrok\SlmQueue\RecoverForm');

        $viewModel = $this->createViewModel(array(
            'form'     => $form,
            'name' => $name,
        ));

        if (!$this->request->isPost()) {
            return $viewModel;
        }

        $isValid = $form->setData($this->request->getPost())->isValid();
        if (!$isValid) {
            return $viewModel;
        }

        $queue = $qm->get($name);
        $count = $queue->recover($form->get('executionTime')->getValue());
        $this->flashMessenger()->addSuccessMessage('Recovered '.$count.' jobs in queue "'
                .$name.'"!');
        return $this->redirect()->toRoute('slm-queue');
    }

    /**
     * List buried jobs of the given queue.
     *
     * @return ViewModel
     */
    public function listBuriedAction()
    {
        $name = $this->params('name');
        $qm = $this->getQueueManager();
        if (!$qm->has($name)) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.queueNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $queue = $qm->get($name);
        $sql = 'SELECT * FROM ' . $queue->getOptions()->getTableName()
                .' WHERE status = ? AND queue = ?';
        $buriedJobs = $queue->connection->fetchAll($sql, array(
            DoctrineQueue::STATUS_BURIED, $queue->getName()));

        return $this->createViewModel(array(
            'name'       => $name,
            'buriedJobs' => $buriedJobs,
        ));
    }

    /**
     * List running jobs of the given queue.
     *
     * @return ViewModel
     */
    public function listRunningAction()
    {
        $name = $this->params('name');
        $qm = $this->getQueueManager();
        if (!$qm->has($name)) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.queueNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $queue = $qm->get($name);
        $sql = 'SELECT * FROM ' . $queue->getOptions()->getTableName()
                .' WHERE status = ? AND queue = ?';
        $runningJobs = $queue->connection->fetchAll($sql, array(
            DoctrineQueue::STATUS_RUNNING, $queue->getName()));

        return $this->createViewModel(array(
            'name'        => $name,
            'runningJobs' => $runningJobs,
        ));
    }

    /**
     * Allows to reset the state of a (running) process back to "pending".
     *
     * @return ViewModel|Response
     */
    public function releaseAction()
    {
        $name = $this->params('name');
        $qm = $this->getQueueManager();
        if (!$qm->has($name)) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.queueNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $id = $this->params('id');
        $queue = $qm->get($name);
        $job = $queue->peek($id);
        if (!$job) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.jobNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $form = $this->getServiceLocator()->get('FormElementManager')
                ->get('Vrok\Form\ConfirmationForm');

        $viewModel = $this->createViewModel(array(
            'form' => $form,
            'name' => $name,
            'id'   => $id,
            'job'  => $job,
        ));

        $form->setData($this->request->getPost());

        if (!$this->request->isPost() || !$form->isValid()) {
            $this->flashMessenger()
                    ->addInfoMessage('message.slmQueue.confirmRelease');
            return $viewModel;
        }

        $queue->release($job);
        $this->flashMessenger()->addSuccessMessage('message.slmQueue.jobReleased');
        return $this->redirect()->toRoute('slm-queue/list-running', array(
            'name' => $name,
        ));
    }

    /**
     * Allows to send a copy of a (buried) job to the queue for repeated processing.
     *
     * @return ViewModel|Response
     */
    public function unburyAction()
    {
        $name = $this->params('name');
        $qm = $this->getQueueManager();
        if (!$qm->has($name)) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.queueNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $id = $this->params('id');
        $queue = $qm->get($name);
        $job = $queue->peek($id);
        if (!$job) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.jobNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $form = $this->getServiceLocator()->get('FormElementManager')
                ->get('Vrok\Form\ConfirmationForm');

        $viewModel = $this->createViewModel(array(
            'form' => $form,
            'name' => $name,
            'id'   => $id,
            'job'  => $job,
        ));

        $form->setData($this->request->getPost());

        if (!$this->request->isPost() || !$form->isValid()) {
            $this->flashMessenger()
                    ->addInfoMessage('message.slmQueue.confirmUnbury');
            return $viewModel;
        }

        $queue->push($job);
        $this->flashMessenger()->addSuccessMessage('message.slmQueue.jobCopyPushed');
        return $this->redirect()->toRoute('slm-queue/delete', array(
            'name' => $name,
            'id'   => $id,
        ));
    }

    /**
     * Allows to delete a (buried) job from the queue.
     *
     * @return ViewModel|Response
     */
    public function deleteAction()
    {
        $name = $this->params('name');
        $qm = $this->getQueueManager();
        if (!$qm->has($name)) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.queueNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $id = $this->params('id');
        $queue = $qm->get($name);
        $job = $queue->peek($id);
        if (!$job) {
            $this->flashMessenger()->addErrorMessage('message.slmQueue.jobNotFound');
            return $this->redirect()->toRoute('slm-queue');
        }

        $form = $this->getServiceLocator()->get('FormElementManager')
                ->get('Vrok\Form\ConfirmationForm');

        $viewModel = $this->createViewModel(array(
            'form' => $form,
            'name' => $name,
            'id'   => $id,
            'job'  => $job,
        ));

        $form->setData($this->request->getPost());

        if (!$this->request->isPost() || !$form->isValid()) {
            $this->flashMessenger()
                    ->addInfoMessage('message.slmQueue.confirmDelete');
            return $viewModel;
        }

        $queue->connection->delete($queue->getOptions()->getTableName(), array(
            'id' => $job->getId()
        ));

        $this->flashMessenger()->addSuccessMessage('message.slmQueue.jobDeleted');
        return $this->redirect()->toRoute('slm-queue');
    }

    /**
     * Console route, checks for buried jobs and jobs running longer than 60min.
     *
     * @todo make interval configurable, give interval as param to the event
     * @triggers buriedJobsFound
     * @triggers longRunningJobsFound
     */
    public function checkJobsAction()
    {
        $config = $this->getServiceLocator()->get('Config');
        $threshold = isset($config['slm_queue']['runtime_threshold'])
            ? (int)$config['slm_queue']['runtime_threshold']
            : 60 * 60;

        $queues = $this->getQueues();
        foreach($queues as $queue) {
            $interval = new \DateInterval('PT'.$threshold.'S');
            $interval->invert = true;
            $maxAge = new \DateTime(null, new \DateTimeZone('UTC'));
            $maxAge->add($interval);

            $sql = 'SELECT COUNT(*) FROM '.$queue->getOptions()->getTableName()
                .' WHERE executed < ? AND status = ? AND queue = ? AND finished IS NULL';
            $runningCount = $queue->connection->fetchColumn($sql, array(
                    $maxAge->format('Y-m-d H:i:s'),
                    DoctrineQueue::STATUS_RUNNING,
                    $queue->getName()
                ), 0);

            if ($runningCount) {
                $this->getEventManager()->trigger(self::EVENT_LONGRUNNINGJOBSFOUND, $queue, array(
                    'count'     => $runningCount,
                    'threshold' => $threshold,
                ));
            }

            $sql = 'SELECT COUNT(*) FROM '.$queue->getOptions()->getTableName()
                    .' WHERE status = ? AND queue = ?';
            $buriedCount = $queue->connection->fetchColumn($sql, array(
                DoctrineQueue::STATUS_BURIED, $queue->getName()), 0);

            if ($buriedCount) {
                $this->getEventManager()->trigger(self::EVENT_BURIEDJOBSFOUND, $queue, array(
                    'count' => $buriedCount,
                ));
            }
        }
    }
}
