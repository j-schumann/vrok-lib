<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Doctrine\EntityInterface;
use Vrok\Doctrine\HasReferenceInterface;
use Vrok\Doctrine\Traits\AutoincrementId;
use Vrok\Doctrine\Traits\CreationDate;
use Vrok\Doctrine\Traits\ObjectReference;

/**
 * Represents a single task to be done. The target could be fulfilled by different
 * actions, it is responsibilty of the business logic to create the Todo and decide
 * when it is completed.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="todo", indexes={@ORM\Index(name="status_idx", columns={"status"})})
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\EntityListeners({"Vrok\Entity\Listener\TodoListener"})
 */
abstract class AbstractTodo extends Entity implements HasReferenceInterface
{
    use AutoincrementId;
    use CreationDate;
    use ObjectReference;

    const STATUS_OPEN      = 'open';      // ist noch keinem Nutzer zugewiesen (steht bei mehreren als offen!)
    const STATUS_ASSIGNED  = 'assigned';  // ist einem oder mehreren Nutzern zugewiesen
    const STATUS_COMPLETED = 'completed'; // ist insgesamt erledigt (Entscheidung der Business Logic)
    const STATUS_CANCELLED = 'cancelled'; // wurde abgebrochen (durch Aktion eines Nutzers oder vom System)
    const STATUS_OVERDUE   = 'overdue';   // Deadline ist abgelaufen

    // used to build the translation string for each Todo type
    const ASSIGNEE_DESCRIPTION_PREFIX   = 'todo.assigneeDescription.';
    const INSPECTION_DESCRIPTION_PREFIX = 'todo.inspectionDescription.';
    const TITLE_PREFIX                  = 'todo.title.';

    /**
     * @var EntityInterface
     */
    protected $object = null;

    /**
     * @var \Zend\View\Helper\Url
     */
    protected $urlHelper = null;

    /**
     * Initialize collection for lazy loading.
     */
    public function __construct()
    {
        $this->userTodos = new ArrayCollection();
    }

    /**
     * Checks if the given User is assigned to this Todo.
     *
     * @param \Vrok\Entity\User $user
     *
     * @return bool
     */
    public function isUserAssigned(User $user)
    {
        foreach ($this->getUserTodos() as $ut) {
            if ($ut->getUser() == $user
                && $ut->getStatus() === UserTodo::STATUS_ASSIGNED
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the object that is referenced by this todo (as the entity would need the
     * EntityManager to fetch it itself).
     * Also sets the URL helper to generate the complete URLs for action/inspection.
     * Called by the TodoService.
     *
     * @param EntityInterface       $object
     * @param \Zend\View\Helper\Url $urlHelper
     */
    public function setHelpers(EntityInterface $object, \Zend\View\Helper\Url $urlHelper)
    {
        $this->object    = $object;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Retrieve the translation message for the short title of this task.
     *
     * @return string
     */
    public function getTitle()
    {
        return self::TITLE_PREFIX.$this->getShortName();
    }

    /**
     * Retrieve the URL to the action that leads to the comletion of this todo.
     * Displayed the user(s) that are assigned to this todo.
     *
     * Uses the urlHelper set with {@link setHelpers} to render the route with the
     * necessary parameters.
     *
     * @return string
     */
    abstract public function getActionUrl();

    /**
     * Retrieve the description of the todo for the given user.
     * This probably differs for the assignee and for any other user that is not
     * assigned to this todo, e.g. for inspection of Todos for other users by admins or
     * after completion. The inspection description may also differ for different user
     * groups.
     * Can contain links and other HTML markup.
     *
     * Uses the helpers set with {@link setHelpers} to retrieve the necessary URLs and
     * parameters and return the translation message.
     *
     * @param User $user the user for which the description is generated, to
     *                   allow different URLs / texts for different user groups.
     *
     * @return array
     */
    public function getDescription(\Vrok\Entity\User $user)
    {
        $params = [];

        // isAssigned also returns false if the user was assigned but the UserTodo
        // status is not "assigned", so completed todos will render as inspection for him.
        if ($this->isUserAssigned($user)) {
            $params['actionUrl'] = $this->getActionUrl();

            return [self::ASSIGNEE_DESCRIPTION_PREFIX.$this->getShortName(), $params];
        }

        $params['inspectionUrl'] = $this->getInspectionUrl($user);

        return [self::INSPECTION_DESCRIPTION_PREFIX.$this->getShortName(), $params];
    }

    /**
     * Returns the list of all states in which a todo is considered "open".
     *
     * @return array
     */
    public static function getOpenStates()
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_ASSIGNED,
            self::STATUS_OVERDUE,
        ];
    }

    /**
     * Returns true if this todo is still open, else false.
     *
     * @return bool
     */
    public function isOpen()
    {
        return in_array($this->getStatus(), self::getOpenStates());
    }

    /**
     * Gets the lower-case short name of the todo class as used in the discriminatorMap.
     * Copied from Doctrine\ORM\Mapping\ClassMetadataFactory, used to construct the
     * translation strings.
     *
     * @return string
     */
    final public function getShortName()
    {
        $className = get_class($this);
        if (strpos($className, '\\') === false) {
            return strtolower($className);
        }

        $parts = explode('\\', $className);

        return strtolower(end($parts));
    }

// <editor-fold defaultstate="collapsed" desc="status">
    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    protected $status = self::STATUS_ASSIGNED;

    /**
     * Returns the status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status.
     *
     * @param string $status
     *
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="creator">
    /**
     * @var \Vrok\Entity\User
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="creator_id", unique=false, referencedColumnName="id", nullable=true)
     */
    protected $creator = null;

    /**
     * Returns the creator user account.
     *
     * @return \Vrok\Entity\User|null
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Sets the creator user account.
     *
     * @param \Vrok\Entity\User $user
     *
     * @return self
     */
    public function setCreator(\Vrok\Entity\User $user = null)
    {
        $this->creator = $user;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="deadline">
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $deadline;

    /**
     * Returns the completion date.
     *
     * @return \DateTime
     */
    public function getDeadline()
    {
        return $this->deadline;
    }

    /**
     * Sets the completion date.
     *
     * @param \DateTime $deadline
     *
     * @return self
     */
    public function setDeadline(\DateTime $deadline)
    {
        $this->deadline = $deadline;

        return $this;
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="completedAt">
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $completedAt;

    /**
     * Returns the completion date.
     *
     * @return \DateTime
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    /**
     * Sets the completion date.
     *
     * @param \DateTime $completedAt
     *
     * @return self
     */
    public function setCompletedAt(\DateTime $completedAt)
    {
        $this->completedAt = $completedAt;

        return $this;
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="userTodos">
    /**
     * @ORM\OneToMany(targetEntity="Vrok\Entity\UserTodo", mappedBy="todo", cascade={"persist"})
     **/
    protected $userTodos;

    /**
     * Returns the list of all referenced UserTodos.
     *
     * @return UserTodo[]
     */
    public function getUserTodos()
    {
        return $this->userTodos;
    }
// </editor-fold>
}
