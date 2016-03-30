<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;

/**
 * References a single user that can be assigned the Todo.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="todo_users", indexes={@ORM\Index(name="status_idx", columns={"status"})})
 */
class UserTodo extends Entity
{
    const STATUS_OPEN        = 'open';        // steht diesem Benutzer zur Übernahme/Erledigung zur Verfügung
    const STATUS_ASSIGNED    = 'assigned';    // ist diesem Benutzer zugewiesen (evtl auch weiteren)
    const STATUS_COMPLETED   = 'completed';   // ist für/durch diesen Benutzer erledigt
    const STATUS_UNCONFIRMED = 'unconfirmed'; // Eine Statusänderung muss von diesem Benutzer noch zur Kenntnis genommen/bestätigt werden
    const STATUS_CONFIRMED   = 'confirmed';   // Eine Statusänderung (durch einen anderen Nutzer) muss von diesem Nutzer noch zur Kenntnis genommen/bestätigt werden

// <editor-fold defaultstate="collapsed" desc="todo">
    /**
     * @var AbstractTodo
     * @Orm\Id
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\AbstractTodo", cascade={"persist"}, inversedBy="userTodos")
     * @ORM\JoinColumn(name="todo_id", unique=false, referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $todo;

    /**
     * Returns the referenced Todo.
     *
     * @return \Vrok\Entity\AbstractTodo
     */
    public function getTodo()
    {
        return $this->todo;
    }

    /**
     * Sets the referenced Todo.
     *
     * @param \Vrok\Entity\AbstractTodo $todo
     *
     * @return self
     */
    public function setTodo(\Vrok\Entity\AbstractTodo $todo)
    {
        $this->todo = $todo;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user">
    /**
     * @var User
     * @Orm\Id
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", unique=false, referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * Returns the assigned user account.
     *
     * @return \Vrok\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the assigned user account.
     *
     * @param \Vrok\Entity\User $user
     *
     * @return self
     */
    public function setUser(\Vrok\Entity\User $user)
    {
        $this->user = $user;

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
     *
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
// </editor-fold>
}
