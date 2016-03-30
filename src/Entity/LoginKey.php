<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;

/**
 * LoginKeys are stored in the users cookies to automatically log him in when
 * he visits the site.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="login_keys")
 */
class LoginKey extends Entity
{
// <editor-fold defaultstate="collapsed" desc="expirationDate">
    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $expirationDate;

    /**
     * Returns the expirationDate for this loginKey.
     *
     * @return DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * Sets the expirationDate for this loginKey.
     *
     * @param DateTime $dateTime
     *
     * @return self
     */
    public function setExpirationDate(DateTime $dateTime = null)
    {
        $this->expirationDate = $dateTime;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="token">
    /**
     * @var string
     * @Orm\Id
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    protected $token;

    /**
     * Returns the token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the token.
     *
     * @param string $value
     *
     * @return self
     */
    public function setToken($value)
    {
        $this->token = (string) $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user">
    /**
     * @var User
     * @Orm\Id
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\User", inversedBy="loginKeys", cascade={"persist"})
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
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }
// </editor-fold>
}
