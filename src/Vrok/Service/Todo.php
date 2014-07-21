<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Vrok\Entity\User;
use Vrok\Entity\AbstractTodo as TodoEntity;
use Vrok\Entity\UserTodo;
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

    const EVENT_TODO_OVERDUE = 'todoOverdue';

    /**
     * Creates a todo of the given type.
     * Status and assigned users can be set afterwards.
     *
     * @param string $type              shortname of the todo class to create
     * @param mixed $object             the object this todo is meant for
     * @param int|\DateTime $timeout    number of seconds used to create the deadline,
     *     or the deadline as \DateTime, if not set no reminder/overdue event is triggered
     * @param User $creator             the user that created this todo,
     *     null for automatically created Todos
     * @return TodoEntity
     */
    public function createTodo(
        $type,
        $object = null,
        $timeout = null,
        User $creator = null
    ) {
        $em = $this->getEntityManager();
        $classMeta = $em->getClassMetadata('Vrok\Entity\AbstractTodo');
        if (!isset($classMeta->discriminatorMap[$type])) {
            throw new \RuntimeException('Requested Todo type '.$type.' not found!');
        }

        $className = $classMeta->discriminatorMap[$type];
        $todo = new $className();
        $em->persist($todo);

        if ($object) {
            $ownerService = $this->getServiceLocator()->get('OwnerService');
            $ownerService->setOwner($todo, $object);
        }

        if ($timeout) {
            if ($timeout instanceof \DateTime) {
                $deadline = $timeout;
            } else {
                $deadline = new \DateTime();
                $deadline->add(new \DateInterval('PT'.$timeout.'S'));
            }
            $todo->setDeadline($deadline);
        }

        if ($creator) {
            $todo->setCreator($creator);
        }

        // we need to flush before we can reference UserTodos, they need the ID.
        $em->flush();
        return $todo;
    }

    /**
     * Adds the reference to the given User to the given Todo (as UserTodo).
     *
     * @param TodoEntity $todo
     * @param User $user
     * @param string $status
     * @return UserTodo
     */
    public function referenceUser(
        TodoEntity $todo,
        User $user,
        $status = UserTodo::STATUS_ASSIGNED
    ) {
        $userTodo = new UserTodo();
        $userTodo->setUser($user);
        $userTodo->setStatus($status);
        $userTodo->setTodo($todo);

        $this->getEntityManager()->persist($userTodo);
        return $userTodo;
    }

    /**
     * Retrieve all (open) todos for the given User.
     * This includes todos that are not completed and not cancelled and the user can take
     * over (no one is assigned), he is assigned to or he needs to confirm.
     *
     * @param User $user
     * @return TodoEntity[]
     */
    public function getOpenUserTodos(User $user)
    {
        $qb = $this->getTodoRepository()->createQueryBuilder('t');
        $qb->leftJoin('t.userTodos ut')
           ->where('ut.user = :user')
           ->andWhere($qb->expr()->notIn('t.status', ':todoStatus'))
           ->andWhere($qb->expr()->in('ut.status', ':userStatus'))
           ->orderBy('t.deadline', 'ASC');

        $qb->setParameter('user', $user)
           ->setParameter('todoStatus', array(
               TodoEntity::STATUS_COMPLETED,
               TodoEntity::STATUS_CANCELLED,
           ))
           ->setParameter('userStatus', array(
                UserTodo::STATUS_ASSIGNED,
                UserTodo::STATUS_OPEN,
     // @todo implement confirmation of todos with changed state
     //           UserTodo::STATUS_UNCONFIRMED,
           ));

        return $qb->getQuery()->getResult();
    }

    /**
     * Retrieve all todos referenced to the given object.
     * This may include also cancelled or completed todos that are not yet deleted!
     *
     * @param object $object
     * @param string $status    if not null only Todos with the given status are returned
     * @return TodoEntity[]
     */
    public function getObjectTodos($object, $status = null)
    {
        $qb = $this->getTodoRepository()->createQueryBuilder('t');
        $qb->orderBy('t.deadline', 'ASC');

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
           $qb->andWhere('t.status = :status')
              ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function getUserTodoList(User $assignee, User $user)
    {
        $todos = $this->getOpenUserTodos($assignee);
        return $this->buildTodoList($todos, $user);
    }

    /**
     * Retrieve the title translation string, description translation string including
     * parameters and the deadline for the given Todos.
     *
     * @param TodoEntity[] $todos
     * @param \Vrok\Entity\User $user
     * @return array
     */
    public function buildTodoList($todos, User $user)
    {
        $list = array();
        foreach($todos as $todo) {
            $list[] = array(
                'title'       => $todo->getTitle(),
                'actionUrl'   => $todo->isUserAssigned($user)
                    ? $todo->getActionUrl()
                    : null,
                'description' => $this->getDescription($todo, $user),
                'deadline'    => $todo->getDeadline(),
            );
        }

        return $list;
    }

    /**
     * Retrieve the referenced object for the given todo.
     *
     * @param TodoEntity $todo
     * @return object   or null if no object referenced.
     */
    public function getReferencedObject(TodoEntity $todo)
    {
        $r = $this->getTodoRepository();
        $ownerService = $this->getServiceLocator()->get('OwnerService');
        return $ownerService->getOwner($todo);
    }

    /**
     * Renders the description of the given Todo for the given user.
     * This can contain a link to the action to complete the Todo if this user
     * is assigned to it or inspect a Todo by other users or after completion.
     *
     * @param \Vrok\Entity\AbstractTodo $todo
     * @param \Vrok\Entity\User $user
     * @return string
     */
    public function getDescription(TodoEntity $todo, User $user)
    {
        $todo->setHelpers(
            $this->getReferencedObject($todo),
            $this->getServiceLocator()->get('viewhelpermanager')->get('url')
        );

        return $todo->getDescription($user);
    }

    /**
     * Checks all open or assigned todos if the deadline has been reached, if yes
     * the event is triggered.
     *
     * Todos already marked as STATUS_OVERDUE are ignored, their event was triggered
     * before, it is task of the listeners to set the STATUS_OVERDUE if they don't want to
     * receive any further notifications.
     * Todos without a deadline will never trigger the event.
     *
     * As this probably triggers notification emails this should be called only once per
     * day via cronjob.
     *
     * @triggers todoOverdue
     */
    public function checkTodos()
    {
        $qb = $this->getTodoRepository()->createQueryBuilder('t');
        // this only queries todos that have a deadline set
        $qb->where('t.deadline < :deadline')
           ->andWhere($qb->expr()->in('t.status', ':todoStatus'))
           ->setParameter('deadline', new \DateTime())
           ->setParameter('todoStatus', array(
               TodoEntity::STATUS_OPEN,
               TodoEntity::STATUS_ASSIGNED,
           ));

        $todos = $qb->getQuery()->getResult();
        foreach($todos as $todo) {
            $this->getEventManager()->trigger(
                self::EVENT_TODO_OVERDUE,
                $todo
            );
        }

        // flush here, the event listeners may have changed the status of the todos etc.,
        // we don't want each single one to flush() if not necessary.
        $this->getEntityManager()->flush();
    }

    /**
     * Retrieve the repository for the action log entries.
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getTodoRepository()
    {
        return $this->getEntityManager()->getRepository('Vrok\Entity\AbstractTodo');
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
