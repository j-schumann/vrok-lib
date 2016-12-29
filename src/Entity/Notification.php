<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Doctrine\Traits\AutoincrementId;
use Vrok\Doctrine\Traits\CreationDate;

/**
 * Stores a notification that can be displayed to the user after login or
 * can be pushed / pulled to/from a smartphone for things that happened since
 * his last login/etc.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="notifications")
 */
class Notification extends Entity
{
    use AutoincrementId;
    use CreationDate;

// <editor-fold defaultstate="collapsed" desc="message">
    /**
     * @var string
     * @ORM\Column(type="text", length=255, nullable=false)
     */
    protected $message;

    /**
     * Returns the message.
     *
     * @return string
     */
    public function getMessage() : string
    {
        return $this->message;
    }

    /**
     * Sets the message.
     *
     * @param string $value
     *
     * @return self
     */
    public function setMessage(string $value)
    {
        $this->message = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="params">
    /**
     * Holds the parameters used in the notification
     *
     * @var mixed
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $params = [];

    /**
     * Returns the notification params
     *
     * @return mixed
     */
    public function getParams() : array
    {
        if (!is_array($this->params)) {
            return [];
        }

        return $this->params;
    }

    /**
     * Sets the previous field params.
     *
     * @param mixed $params
     *
     * @return self
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="dismissed">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $dismissed = false;

    /**
     * Returns true if the user has dismissed the notification via the web interface.
     * Dismissed status of notifications that are pulled via the API must be handled
     * in the corresponding application.
     *
     * @return bool
     */
    public function isDismissed() : bool
    {
        return $this->dismissed;
    }

    /**
     * Sets whether or not the user has dismissed the notification in the web
     * interface.
     *
     * @param bool $dismissed
     *
     * @return self
     */
    public function setDismissed(bool $dismissed = true)
    {
        $this->dismissed = $dismissed;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="type">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $type;

    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Sets the type.
     *
     * @param string $value
     *
     * @return self
     */
    public function setType(string $value)
    {
        $this->type = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user">
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", unique=false, referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * Returns the assigned user.
     *
     * @return \Vrok\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the assigned user.
     *
     * @param \Vrok\Entity\User $user
     *
     * @return self
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }
// </editor-fold>
}
