<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Vrok\Entity\User;
use Vrok\Entity\Todo as TodoEntity;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Handles todos for users (and the system), triggering an event when a deadline is
 * reached.
 */
class Todo implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ServiceLocatorAwareTrait;

    const EVENT_DEADLINEREACHED = 'deadlineReached';

    /**
     * List of timeouts in seconds until the deadline is reached and an event is triggered.
     * Types that don't have a timeout don't get a deadline set.
     *
     * @var int[]   array(type => timeout, ...)
     */
    protected $timeouts = array();

    /**
     * Creates a todo of the given type.
     * The Todo may be assigned to a user and may reference an object.
     *
     * @todo timeout als param
     * @param string $type
     * @param User $user
     * @param mixed $object
     * @param bool $flush   if true the current unitOfWork is committed to the DB
     */
    public function createTodo($type, User $user = null, $object = null, $flush = false)
    {
        if ($this->getTodo($type, $user, $object)) {
            throw new \Vrok\Doctrine\Exception\RuntimeException('A todo for the type "'
                .$type.'" and user/object already exists!');
        }

        $todo = new TodoEntity();
        $todo->setType($type);

        $timeout = $this->getTimeout($type);
        if ($timeout) {
            $deadline = new \DateTime();
            $deadline->add(new \DateInterval('PT'.$timeout.'S'));
            $todo->setDeadline($deadline);
        }

        if ($user) {
            $todo->setUser($user);
        }

        if ($object) {
            $ownerService = $this->getServiceLocator()->get('OwnerService');
            $ownerService->setOwner($todo, $object);
        }

        $this->getEntityManager()->persist($todo);
        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $todo;
    }

    /**
     * Find an open todo by the unique combination of type, user and object.
     *
     * @param string $type
     * @param \Vrok\Entity\User $user
     * @param object $object
     */
    public function getOpenTodo($type, User $user = null, $object = null)
    {
        $qb = $this->getTodoRepository()->createQueryBuilder('t');
        $qb->where('type', ':type')
           ->andWhere('user', ':user')
           ->andWhere('status', ':status')
           ->setParameter('type', $type)
           ->setParameter('user', $user)
           ->setParameter('status', TodoEntity::STATUS_OPEN);

        if ($object) {
            $ownerService = $this->getServiceLocator()->get('OwnerService');
            $qb->andWhere('t.ownerClass = :ownerClass')
               ->andWhere('t.ownerIdentifier = :ownerIdentifier')
               ->setParameter('ownerClass', get_class($object))
               ->setParameter('ownerIdentifier', $ownerService->getOwnerIdentifier($object));
        }
        else {
            // explicitly match NULL or every object matches
            $qb->andWhere($qb->expr()->isNull('t.ownerClass'))
               ->andWhere($qb->expr()->isNull('t.ownerIdentifier'));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieve all todos for the given owner.
     *
     * @param \Vrok\Entity\User $user
     * @param string $status    if not null only Todos with the given status are returned
     * @return TodoEntity[]
     */
    public function getUserTodos(User $user, $status = TodoEntity::STATUS_OPEN)
    {
        $criteria = array('user' => $user);
        if ($status) {
            $criteria['status'] = $status;
        }

        $repository = $this->getTodoRepository();
        return $repository->findBy($criteria);
    }

    /**
     * Retrieve all todos referenced to the given object.
     *
     * @param object $object
     * @param string $status    if not null only Todos with the given status are returned
     * @return TodoEntity[]
     */
    public function getObjectTodos($object, $status = TodoEntity::STATUS_OPEN)
    {
        $qb = $this->getTodoRepository()->createQueryBuilder('t');

        if ($object) {
            $ownerService = $this->getServiceLocator()->get('OwnerService');
            $qb->where('t.ownerClass = :ownerClass')
               ->andWhere('t.ownerIdentifier = :ownerIdentifier')
               ->setParameter('ownerClass', get_class($object))
               ->setParameter('ownerIdentifier', $ownerService->getOwnerIdentifier($object));
        }
        else {
            // explicitly match NULL or every object matches
            $qb->andWhere($qb->expr()->isNull('t.ownerClass'))
               ->andWhere($qb->expr()->isNull('t.ownerIdentifier'));
        }

        if ($status) {
           $qb->andWhere('status', ':status')
              ->setParameter('status', TodoEntity::STATUS_OPEN);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Remove a todo by the unique combination of type, user and object.
     *
     * @param string $type
     * @param \Vrok\Entity\User $user
     * @param object $object
     * @return bool     true if the todo was found and removed, else false
     */
    public function deleteTodo($type, User $user = null, $object = null)
    {
        $todo = $this->getTodo($type, $user, $object);
        if ($todo) {
            $this->getEntityManager()->remove($todo);
            return true;
        }

        return false;
    }

    /**
     * Retrieve the timeout in seconds configured for the given type or
     * null if no timeout was set (todo will never expire).
     *
     * @param string $type
     * @return int|null
     */
    public function getTimeout($type)
    {
        return isset($this->timeouts[$type])
            ? $this->timeouts[$type]
            : null;
    }

    /**
     * Sets the type timeout in seconds.
     *
     * @todo validate args
     * @param string $type
     * @param int $timeout
     */
    public function setTimeout($type, $timeout)
    {
        $this->timeouts[$type] = $timeout;
    }

    /**
     * Sets multiple timeouts at once.
     *
     * @todo use Zend Guard to check for array etc
     * @param array $timeouts
     */
    public function setTimeouts($timeouts)
    {
        foreach($timeouts as $type => $timeout) {
            $this->setTimeout($type, $timeout);
        }
    }

    /**
     * Retrieve the repository for the action log entries.
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getTodoRepository()
    {
        return $this->getEntityManager()->getRepository('Vrok\Entity\Todo');
    }

    /**
     * Retrieve the entity manager.
     *
     * @return ObjectManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    }
}
