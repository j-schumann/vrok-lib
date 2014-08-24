<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Owner\HasOwnerInterface;

/**
 * Represents a calendar entry that can be assigned to an user or other object.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\Table(name="calendar_entries", indexes={
 *     @ORM\Index(name="startDate", columns={"startDate"})
 *     @ORM\Index(name="endDate", columns={"endDate"})
 * })
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class AbstractCalendarEntry extends Entity implements HasOwnerInterface
{
    use \Vrok\Doctrine\Traits\AutoincrementId;
    use \Vrok\Doctrine\Traits\CreationDate;
    use \Vrok\Owner\HasOwnerTrait;

    const STATUS_OPEN      = 'open';      // ist noch keinem Nutzer zugewiesen (steht bei mehreren als offen!)
    const STATUS_ASSIGNED  = 'assigned';  // ist einem oder mehreren Nutzern zugewiesen
    const STATUS_COMPLETED = 'completed'; // ist insgesamt erledigt (Entscheidung der Business Logic)
    const STATUS_CANCELLED = 'cancelled'; // wurde abgebrochen (durch Aktion eines Nutzers oder vom System)
    const STATUS_OVERDUE   = 'overdue';   // Deadline ist abgelaufen

    /**
     * @var \Vrok\Doctrine\Entity
     */
    protected $object = null;

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
     * @return bool
     */
    public function isUserAssigned(User $user)
    {
        foreach($this->getUserTodos() as $ut) {
            if ($ut->getUser() == $user
                && $ut->getStatus() === UserTodo::STATUS_ASSIGNED
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the object that is referenced by this todo (fetched from the OwnerService)
     * and the URL helper to generate the complete URLs for action/inspection.
     * Called by the TodoService.
     *
     * @param mixed $object
     * @param \Zend\View\Helper\Url $urlHelper
     */
    public function setHelpers($object, \Zend\View\Helper\Url $urlHelper)
    {
        $this->object = $object;
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
    abstract function getActionUrl();

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
     * @param User $user        the user for which the description is generated, to
     *     allow different URLs / texts for different user groups.
     * @return array
     */
    public function getDescription(\Vrok\Entity\User $user)
    {
        $params = array();

        // isAssigned also returns false if the user was assigned but the UserTodo
        // status is not "assigned", so completed todos will render as inspection for him.
        if ($this->isUserAssigned($user)) {
            $params['actionUrl'] = $this->getActionUrl();
            return array(self::ASSIGNEE_DESCRIPTION_PREFIX.$this->getShortName(), $params);
        }

        $params['inspectionUrl'] = $this->getInspectionUrl($user);
        return array(self::INSPECTION_DESCRIPTION_PREFIX.$this->getShortName(), $params);
    }

    /**
     * Returns the list of all states in which a todo is considered "open".
     *
     * @return array
     */
    public static function getOpenStates()
    {
        return array(
            self::STATUS_OPEN,
            self::STATUS_ASSIGNED,
            self::STATUS_OVERDUE,
        );
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
        if (strpos($className, "\\") === false) {
            return strtolower($className);
        }

        $parts = explode("\\", $className);
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
     * @param  \DateTime $deadline
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
     * @param  \DateTime $completedAt
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

    /**
     * Adds the userTodo to the collection..
     * Called by $userTodo->setTodo to keep the collection consistent.
     *
     * @param UserTodo $userTodo
     * @return boolean  false if the $userTodo was already in the collection, else true
     */
    public function addUserTodo(UserTodo $userTodo)
    {
        if ($this->userTodos->contains($userTodo)) {
            return false;
        }

        $this->userTodos[] = $userTodo;
        $userTodo->setTodo($this);
        return true;
    }

    /**
     * Removes the given UserTodo from the collection.
     *
     * @param UserTodo $userTodo
     * @return boolean     true if the UserTodo was in the collection and was
     *     removed, else false
     */
    public function removeUserTodo(UserTodo $userTodo)
    {
        if (!$this->userTodos->contains($userTodo)) {
            return false;
        }

        $this->userTodos->removeElement($userTodo);
        return true;
    }

    /**
     * Proxies to addUserTodo for multiple elements.
     *
     * @param Collection $userTodos
     */
    public function addUserTodos(Collection $userTodos)
    {
        foreach($userTodos as $userTodo) {
            $this->addUserTodo($userTodo);
        }
    }

    /**
     * Proxies to removeUserTodo for multiple elements.
     *
     * @param Collection $userTodos
     */
    public function removeGroups(Collection $userTodos)
    {
        foreach($userTodos as $userTodo) {
            $this->removeUserTodo($userTodo);
        }
    }
// </editor-fold>
}
