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
 * Log entry for all actions that occur in the system.
 * Used to show the logbook for one user and generate statistics.
 * Allows to log reaction times showing how long it took the user from a notification
 * till a corresponding action occured.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="actionlog")
 */
class ActionLog extends Entity
{
    use AutoincrementId;
    use CreationDate;

// <editor-fold defaultstate="collapsed" desc="action">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $action;

    /**
     * Returns the action name.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the action name.
     *
     * @param string $value
     *
     * @return self
     */
    public function setAction($value)
    {
        $this->action = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="content">
    /**
     * @var string
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    protected $content;

    /**
     * Returns the content to be validated (e.g. new email address).
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the content to be validated (e.g. new email address).
     *
     * @param string $value
     *
     * @return self
     */
    public function setContent($value)
    {
        $this->content = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="ipAddress">
    /**
     * IPV6 = 32 characters + 5 colons.
     *
     * @var string
     * @ORM\Column(type="string", length=39, nullable=true)
     */
    protected $ipAddress;

    /**
     * Returns the IP address from which the questionaire was created.
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Sets the IP address from which the questionare was submitted.
     *
     * @param string $ipAddress
     *
     * @return self
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user">
    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", unique=false, referencedColumnName="id", nullable=true)
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
// <editor-fold defaultstate="collapsed" desc="reactionTime">
    /**
     * @var int
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    protected $reactionTime = 0;

    /**
     * Returns the reaction time used for this action.
     *
     * @return int
     */
    public function getReactionTime()
    {
        return $this->reactionTime;
    }

    /**
     * Sets reaction time for this action.
     *
     * @param int $value
     *
     * @return self
     */
    public function setReactionTime($value)
    {
        $this->reactionTime = $value;

        return $this;
    }
// </editor-fold>
}
