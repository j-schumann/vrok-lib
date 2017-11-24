<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Vrok\Doctrine\EntityInterface;
use Vrok\Entity\User;
use Vrok\Entity\AbstractTodo as TodoEntity;
use Vrok\Entity\UserTodo;
use Vrok\Entity\Filter\TodoFilter;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\View\HelperPluginManager as ViewHelperManager;

/**
 * Handles todos for users (and the system), triggering an event when a deadline is
 * reached.
 */
class Todo implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    const EVENT_TODO_COMPLETED = 'todoCompleted';
    const EVENT_TODO_OVERDUE   = 'todoOverdue';

    /**
     * Template for rendering the users todo list.
     *
     * @var string
     */
    protected $partial = 'vrok/partials/todo-list';

    /**
     * @var AuthenticationServiceInterface
     */
    protected $authenticationService = null;

    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * @var ViewHelperManager
     */
    protected $viewHelperManager = null;

    /**
     * Sets the AS instance to use.
     *
     * @param AuthenticationServiceInterface $as
     */
    public function setAuthenticationService(AuthenticationServiceInterface $as)
    {
        $this->authenticationService = $as;
    }

    /**
     * Sets the EM instance to use.
     *
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Sets the VHM instance to use.
     *
     * @param ViewHelperManager $vhm
     */
    public function setViewHelperManager(ViewHelperManager $vhm)
    {
        $this->viewHelperManager = $vhm;
    }

    /**
     * Creates a todo of the given type.
     * Status and assigned users can be set afterwards.
     *
     * @param string          $type    shortname of the todo class to create
     * @param EntityInterface $object  the object this todo is meant for
     * @param int|DateTime    $timeout number of seconds used to create the deadline,
     *                                 or the deadline as DateTime, if not set no reminder/overdue event is triggered
     * @param User            $creator the user that created this todo,
     *                                 null for automatically created Todos
     *
     * @return TodoEntity
     */
    public function createTodo(
        $type,
        EntityInterface $object = null,
        $timeout = null,
        User $creator = null
    ) {
        $classMeta = $this->entityManager->getClassMetadata('Vrok\Entity\AbstractTodo');
        if (! isset($classMeta->discriminatorMap[$type])) {
            throw new \RuntimeException('Requested Todo type '.$type.' not found!');
        }

        $className = $classMeta->discriminatorMap[$type];
        $todo      = new $className();
        /* @var $todo TodoEntity */
        $this->entityManager->persist($todo);

        if ($object) {
            $todo->setReference($this->entityManager, $object);
        }

        if ($timeout) {
            if ($timeout instanceof DateTime) {
                $deadline = $timeout;
            } else {
                $deadline = new DateTime();
                $deadline->add(new DateInterval('PT'.$timeout.'S'));
            }
            $todo->setDeadline($deadline);
        }

        if ($creator) {
            $todo->setCreator($creator);
        }

        // we need to flush before we can reference UserTodos, they need the ID.
        $this->entityManager->flush();

        return $todo;
    }

    /**
     * Marks all open todos of the given type for the given object as completed.
     * Marked as completed by the current user, confirmed for all others.
     *
     * @param string          $type
     * @param EntityInterface $object
     * @param bool            $flush  if true the entityManager is flushed
     * @triggers todoCompleted
     */
    public function completeObjectTodo($type, EntityInterface $object, $flush = true)
    {
        $identity = $this->authenticationService->getIdentity();

        $filter = $this->getTodoFilter('t', $type);
        $filter->byObject($object)
               ->areOpen();

        $todos = $filter->getResult();
        foreach ($todos as $todo) {
            /*@var $todo TodoEntity */
            $todo->setStatus(TodoEntity::STATUS_COMPLETED);
            $todo->setCompletedAt(new DateTime());

            foreach ($todo->getUserTodos() as $userTodo) {
                if ($userTodo->getUser() == $identity) {
                    $userTodo->setStatus(UserTodo::STATUS_COMPLETED);
                } else {
                    $userTodo->setStatus(UserTodo::STATUS_CONFIRMED);
                }
            }

            $this->getEventManager()->trigger(
                self::EVENT_TODO_COMPLETED,
                $todo
            );
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Marks all open todos of an object as cancelled, e.g. when an order is cancelled.
     *
     * @param EntityInterface $object
     * @param bool            $flush  if true the entityManager is flushed
     */
    public function cancelObjectTodos(EntityInterface $object, $flush = true)
    {
        $filter = $this->getTodoFilter();
        $filter->byObject($object)
               ->areOpen();

        $todos = $filter->getResult();
        foreach ($todos as $todo) {
            /*@var $todo TodoEntity */
            $todo->setStatus(TodoEntity::STATUS_CANCELLED);
            $todo->setCompletedAt(new DateTime());

            foreach ($todo->getUserTodos() as $userTodo) {
                $userTodo->setStatus(UserTodo::STATUS_CONFIRMED);
            }
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Adds the reference to the given User to the given Todo (as UserTodo).
     *
     * @param TodoEntity $todo
     * @param User       $user
     * @param string     $status
     *
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

        $this->entityManager->persist($userTodo);

        return $userTodo;
    }

    /**
     * Retrieve the title translation string, description translation string including
     * parameters and the deadline for the given Todos.
     *
     * @param TodoEntity[]      $todos
     * @param \Vrok\Entity\User $user
     *
     * @return array
     */
    public function buildTodoList($todos, User $user)
    {
        $list = [];
        foreach ($todos as $todo) {
            $todo->setHelpers(
                $todo->getReference($this->entityManager),
                $this->viewHelperManager->get('url')
            );

            $list[] = [
/* aktuell nicht nötig/benutzt:
                'title'       => $todo->getTitle(),
                'actionUrl'   => $todo->isUserAssigned($user)
                    ? $todo->getActionUrl()
                    : null,
 */
                'description' => $todo->getDescription($user),
                'deadline'    => $todo->getDeadline(),
                'isOpen'      => $todo->isOpen(),
                'status'      => $todo->getStatus(),
            ];
        }

        return $list;
    }

    /**
     * Retrieve the list of all Todos assigned to $assignee to be shown to $user.
     *
     * @param \Vrok\Entity\User $assignee
     * @param \Vrok\Entity\User $user
     *
     * @return array
     */
    public function getUserTodoList(User $assignee, User $user = null)
    {
        $filter = $this->getTodoFilter('t');
        $filter->byUser($assignee, [
                UserTodo::STATUS_ASSIGNED,
                UserTodo::STATUS_OPEN,
                // @todo implement confirmation of todos with changed state
                //UserTodo::STATUS_UNCONFIRMED,
            ])
            ->orderByField('deadline', 'ASC');

        $todos = $filter->getResult();

        return $this->buildTodoList($todos, $user ?: $assignee);
    }

    /**
     * Retrieve the rendered list of all Todos assigned to $assignee to be shown to $user.
     *
     * @param \Vrok\Entity\User $assignee
     * @param \Vrok\Entity\User $user
     *
     * @return string
     */
    public function renderUserTodoList(User $assignee, User $user = null)
    {
        $todos   = $this->getUserTodoList($assignee, $user);
        $partial = $this->viewHelperManager->get('partial');

        return $partial($this->getPartial(), [
            'todos' => $todos,
        ]);
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
               ->byStatus([TodoEntity::STATUS_OPEN, TodoEntity::STATUS_ASSIGNED]);

        $todos = $filter->getResult();
        foreach ($todos as $todo) {
            $this->getEventManager()->trigger(
                self::EVENT_TODO_OVERDUE,
                $todo
            );
        }

        // flush here, the event listeners may have changed the status of the todos etc.,
        // we don't want each single one to flush() if not necessary.
        $this->entityManager->flush();
    }

    /**
     * Retrieve a new todo filter instance.
     *
     * @param string $alias the alias for the Todo record
     * @param string $class the (sub)class for which should be filtered
     *
     * @return TodoFilter
     */
    public function getTodoFilter($alias = 't', $class = 'Vrok\Entity\AbstractTodo')
    {
        $qb = $this->getTodoRepository($class)->createQueryBuilder($alias);

        return new TodoFilter($qb);
    }

    /**
     * Retrieve the repository for Todos.
     *
     * @param string $class give a subclass here to only query for this todo type
     *
     * @return \Vrok\Doctrine\EntityRepository
     */
    public function getTodoRepository($class = 'Vrok\Entity\AbstractTodo')
    {
        if (strpos('\\', $class) === false) {
            $meta = $this->entityManager->getClassMetadata('Vrok\Entity\AbstractTodo');
            if (isset($meta->discriminatorMap[$class])) {
                $class = $meta->discriminatorMap[$class];
            }
        }

        return $this->entityManager->getRepository($class);
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
