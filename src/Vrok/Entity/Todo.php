<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Owner\HasOwnerInterface;

/**
 * Represents a single task to be done. The target could be fulfilled by different
 * actions, it is responsibilty of the business logic to create the Todo and decide
 * when it is completed.
 *
 * The combination of type, user and referenced object is unique as it does not make sense
 * to have the same task (for the same object) to be done by the same user.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="todo")
 */
class Todo extends Entity implements HasOwnerInterface
{
    use \Vrok\Doctrine\Traits\AutoincrementId;
    use \Vrok\Doctrine\Traits\CreationDate;
    use \Vrok\Owner\HasOwnerTrait;

    const STATUS_OPEN      = 'open';   // ist noch keinem Nutzer zugewiesen (steht bei mehreren als offen!)
    const STATUS_ASSIGNED  = 'assigned'; // ist einem oder mehreren Nutzern zugewiesen
    const STATUS_COMPLETED = 'completed'; // ist insgesamt erledigt (Entscheidung der Business Logic)
    const STATUS_CANCELLED = 'cancelled'; // wurde abgebrochen (durch Aktion eines Nutzers oder vom System)

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
     * @ORM\Column(type="string", length=100, nullable=true)
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
}
