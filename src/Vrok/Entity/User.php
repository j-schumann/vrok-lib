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
use Gedmo\Mapping\Annotation as Gedmo;
use Vrok\Doctrine\Entity;
use ZfcUser\Entity\UserInterface;

/**
 * User object holding the identity and credential information.
 *
 * @ORM\Entity(repositoryClass="Vrok\Entity\UserRepository")
 * @ORM\Table(name="users")
 */
class User extends Entity implements UserInterface
{
    use \Vrok\Doctrine\Traits\AutoincrementId;
    use \Vrok\Doctrine\Traits\CreationDate;
    use \Vrok\Doctrine\Traits\DeletionDate;

    /**
     * Initialize collection for lazy loading.
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    /**
     * Returns true if the user is flagged as deleted (a deletion date is set).
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deletedAt != null;
    }

    /**
     * Marks the user as deleted by removing his state bits and clearing his
     * personal data.
     * We keep the email to avoid re-registration, to allow this either clear
     * the email too or delete the record completely.
     */
    public function delete()
    {
        $this->state       = 0;
        $this->username    = $this->email;
        $this->displayName = null;
        $this->password    = '';

        $this->setDeletedAt(new \DateTime());
    }

    /**
     * Sets the users password to a random token of the given length.
     * Also sets the isRandomPassword flag to true.
     *
     * @return string   the random password
     */
    public function setRandomPassword($length = 10)
    {
        $password = \Vrok\Stdlib\Random::getRandomToken((int)$length);
        $this->setPassword($password);
        $this->setIsRandomPassword(true);
        return $password;
    }

    /**
     * Checks if the given password matches the stored one.
     *
     * @param string $password
     * @return boolean
     */
    public function checkPassword($password)
    {
        // password_verify implemented by ircmaxell/password-compat or natively
        // on PHP >= 5.5.0
        if (!password_verify($password, $this->password)) {
            return false;
        }
        return true;
    }

    /**
     * Required for ZfcUserInterface.
     * @return int
     */
    public function getState()
    {
        return 0;
    }

    /**
     * Required for ZfcUserInterface.
     * @param int $state
     * @return self
     */
    public function setState($state)
    {
        return $this;
    }

// <editor-fold defaultstate="collapsed" desc="username">
    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    protected $username;

    /**
     * Returns the users username (for login, semi-anonymous presentation etc.).
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the users username.
     * Must be unique.
     *
     * @param string $username
     * @return self
     */
    public function setUsername($username)
    {
        if ($this->displayName === $this->username) {
            $this->displayName = null;
        }
        $this->username = (string) $username;

        // displayname defaults to the username
        if (!$this->displayName) {
            $this->setDisplayName($username);
        }
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="displayName">
    /**
     * @var string
     * @ORM\Column(type="string", unique=false, nullable=true)
     */
    protected $displayName;

    /**
     * Returns the users displayName.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the users displayName.
     *
     * @param string $displayName
     * @return self
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = (string) $displayName;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="email">
    /**
     * @var string
     * @ORM\Column(type="string", length=70)
     */
    protected $email;

    /**
     * Returns the users email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the users email.
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        if ($this->username === $this->email) {
            $this->username = null;
        }
        $this->email = (string) $email;

        // an username is required, default to the mail address
        if (!$this->username) {
            $this->setUsername($email);
        }

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="password">
    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $password;


    /**
     * Returns the users (encrypted) password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Encrypts and stores the given password.
     * Sets the isRandomPassword flag to false.
     *
     * @param string $password
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->setIsRandomPassword(false);
        $this->setPasswordDate(new \DateTime());
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="isRandomPassword">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $isRandomPassword = false;

    /**
     * Returns true if the users password is a automatically generated password,
     * else false.
     *
     * @return bool
     */
    public function getIsRandomPassword()
    {
        return $this->isRandomPassword;
    }

    /**
     * Sets wether or not the users password is a automatically generated one.
     *
     * @param bool $isRandomPassword
     * @return self
     */
    public function setIsRandomPassword($isRandomPassword = true)
    {
        $this->isRandomPassword = (bool) $isRandomPassword;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="lastSession">
    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastSession;

    /**
     * Returns the last session date.
     *
     * @return \DateTime
     */
    public function getLastSession()
    {
        return $this->lastSession;
    }

    /**
     * Sets the last session date.
     *
     * @param \DateTime $lastSession
     * @return self
     */
    public function setLastSession(\DateTime $lastSession)
    {
        $this->lastSession = $lastSession;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="lastLogin">
    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * Returns the last login date.
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Sets the last login date.
     *
     * @param \DateTime $lastLogin
     * @return self
     */
    public function setLastLogin(\DateTime $lastLogin)
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="passwordDate">
    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $passwordDate;

    /**
     * Returns the date of the last password change.
     *
     * @return \DateTime
     */
    public function getPasswordDate()
    {
        return $this->passwordDate;
    }

    /**
     * Sets the date of the last password change.
     *
     * @param \DateTime $passwordDate
     * @return self
     */
    public function setPasswordDate(\DateTime $passwordDate)
    {
        $this->passwordDate = $passwordDate;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="isActive">
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    protected $isActive = false;

    /**
     * Gets wether the user is active or not.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Sets wether the user is active or not.
     *
     * @param bool $isActive
     * @return self
     */
    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="isValidated">
    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    protected $isValidated = false;

    /**
     * Gets wether the user is validated or not.
     *
     * @return bool
     */
    public function getIsValidated()
    {
        return $this->isValidated;
    }

    /**
     * Sets wether the user is validated or not.
     *
     * @param bool $isValidated
     * @return self
     */
    public function setIsValidated($isValidated)
    {
        $this->isValidated = (bool) $isValidated;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="groups">
    /**
     * @ORM\ManyToMany(targetEntity="Group", mappedBy="members")
     **/
    protected $groups;

    /**
     * Returns the list of all groups this user is a member of.
     *
     * @return Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Adds the user as member of the given group.
     * Called by $group->addMember to keep the collection consistent.
     *
     * @param \Ellie\Entity\Group $group
     * @return boolean  false if the user was already a member of the group, else true
     */
    public function addGroup(Group $group)
    {
        if ($this->groups->contains($group)) {
            return false;
        }

        $this->groups[] = $group;
        return true;
    }

    /**
     * Removes the given Group from the users groups.
     * Called by $group->removeMember to keep the collection consistent.
     *
     * @param Group $group
     * @return boolean     true if the Group was in the collection and was
     *     removed, else false
     */
    public function removeGroup(Group $group)
    {
        return $this->groups->removeElement($group);
    }
// </editor-fold>
}
