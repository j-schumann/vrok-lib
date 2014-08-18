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

    protected $partial = 'vrok/partials/todo-list';

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
        $filter = $this->getTodoFilter('t');
        $filter->byUser($user, array(
                UserTodo::STATUS_ASSIGNED,
                UserTodo::STATUS_OPEN,
                // @todo implement confirmation of todos with changed state
                //UserTodo::STATUS_UNCONFIRMED,
            ))
            ->byStatusNot(array(
                TodoEntity::STATUS_COMPLETED,
                TodoEntity::STATUS_CANCELLED,
            ))
            ->orderBy('t.deadline', 'ASC');

        return $filter->getResult();
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
        $filter = $this->getTodoFilter('t');
        $filter->byObject($object)
               ->orderBy('t.deadline', 'ASC');

        if ($status) {
           $filter->byStatus($status);
        }

        return $filter->getResult();
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
            $todo->setHelpers(
                $this->getReferencedObject($todo),
                $this->getServiceLocator()->get('viewhelpermanager')->get('url')
            );

            $list[] = array(
/* aktuell nicht nötig/benutzt:
                'title'       => $todo->getTitle(),
                'actionUrl'   => $todo->isUserAssigned($user)
                    ? $todo->getActionUrl()
                    : null,
 */
                'description' => $todo->getDescription($user),
                'deadline'    => $todo->getDeadline(),
            );
        }

        return $list;
    }

    /**
     * Retrieve the list of all Todos assigned to $assignee to be shown to $user.
     *
     * @param \Vrok\Entity\User $assignee
     * @param \Vrok\Entity\User $user
     * @return array
     */
    public function getUserTodoList(User $assignee, User $user = null)
    {
        $todos = $this->getOpenUserTodos($assignee);
        return $this->buildTodoList($todos, $user ?: $assignee);
    }

    /**
     * Retrieve the rendered list of all Todos assigned to $assignee to be shown to $user.
     *
     * @param \Vrok\Entity\User $assignee
     * @param \Vrok\Entity\User $user
     * @return string
     */
    public function renderUserTodoList(User $assignee, User $user = null)
    {
        $todos = $this->getUserTodoList($assignee, $user);
        $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');

        return $partial($this->getPartial(), array(
            'todos' => $todos,
        ));
    }

    /**
     * Retrieve the referenced object for the given todo.
     *
     * @param TodoEntity $todo
     * @return object   or null if no object referenced.
     */
    public function getReferencedObject(TodoEntity $todo)
    {
        $ownerService = $this->getServiceLocator()->get('OwnerService');
        return $ownerService->getOwner($todo);
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
        $filter = $this->getTodoFilter();
        $filter->byDeadline(new \DateTime(), '<')
               ->byStatus(array(TodoEntity::STATUS_OPEN, TodoEntity::STATUS_ASSIGNED));

        $todos = $filter->getResult();
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
     * Retrieve a new todo filter instance.
     *
     * @param string $alias     the alias for the Todo record
     * @return \Vrok\Entity\Filter\TodoFilter
     */
    public function getTodoFilter($alias = 't', $class = 'Vrok\Entity\AbstractTodo')
    {
        $qb = $this->getTodoRepository($class)->createQueryBuilder($alias);
        $ownerService = $this->getServiceLocator()->get('OwnerService');

        $filter = new \Vrok\Entity\Filter\TodoFilter($qb, $ownerService);
        return $filter;
    }

    /**
     * Retrieve the repository for Todos.
     *
     * @param string $class     give a subclass here to only query for this todo type
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getTodoRepository($class = 'Vrok\Entity\AbstractTodo')
    {
        if (strpos('\\', $class) === false) {
            $meta = $this->getEntityManager()->getClassMetadata('Vrok\Entity\AbstractTodo');
            if (isset($meta->discriminatorMap[$class])) {
                $class = $meta->discriminatorMap[$class];
            }
        }
        return $this->getEntityManager()->getRepository($class);
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

    /**
     * Retrieve the partial file name that is used to render the todo list.
     *
     * @return string
     */
    public function getPartial()
    {
        return $this->partial;
    }

    /**
     * Sets the partial to use for rendering the todo list.
     *
     * @param string $partial
     */
    public function setPartial($partial)
    {
        $this->partial = $partial;
    }
}
