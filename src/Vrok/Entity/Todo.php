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
 * Represents a single task to be done. The target could be fulfilled by different
 * actions, it is responsibilty of the business logic to create the Todo and decide
 * when it is completed.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="todo", indexes={@ORM\Index(name="status_idx", columns={"status"})})
 */
class Todo extends Entity implements HasOwnerInterface
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
     * Initialize collection for lazy loading.
     */
    public function __construct()
    {
        $this->userTodos = new ArrayCollection();
    }

// <editor-fold defaultstate="collapsed" desc="type">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $type;

    /**
     * Returns the validation type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the validation type.
     *
     * @param string $value
     * @return self
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }
// </editor-fold>
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
     * @ORM\OneToMany(targetEntity="UserTodo", mappedBy="todo", cascade={"persist"})
     **/
    protected $userTodos;

    /**
     * Returns the list of all referenced UserTodos.
     *
     * @return Collection
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
