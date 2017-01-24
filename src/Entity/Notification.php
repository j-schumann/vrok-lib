<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Doctrine\Traits\AutoincrementId;
use Vrok\Doctrine\Traits\CreationDate;
use Zend\View\HelperPluginManager;

/**
 * Stores a notification that can be displayed to the user after login or
 * can be pushed / pulled to/from a smartphone for things that happened since
 * his last login/etc.
 *
 * This class implements a very simple system notification that won't be pushed
 * or sent by email and cannot be pulled, only displayed on the website.
 * Create a subclass to change the flags as needed and supply different content,
 * e.g. by using partials for the (HTML) output.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="notifications")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\EntityListeners({"Vrok\Entity\Listener\NotificationListener"})
 */
class Notification extends Entity
{
    use AutoincrementId;
    use CreationDate;

    // Wether or not the user can "opt out" of this notification: If the user
    // has disabled email notifications in his profil but forceMail is true
    // the message will be mailed anyways.
    const FORCE_MAIL = false;

    const PUSHABLE = false;
    const PULLABLE = false;
    const MAILABLE = false;

    /**
     * EntityManager for fetching entities from the parameters.
     * Must be injected before calling the getters.
     *
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * View Helper Manager for translating and formatting the message.
     * Must be injected before calling the getters.
     *
     * @var HelperPluginManager
     */
    protected $viewHelperManager = null;

    /**
     * Sets the EntityManager instance to use.
     *
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Sets the ViewHelperManager instance to use.
     *
     * @param HelperPluginManager $em
     */
    public function setViewHelperManager(HelperPluginManager $em)
    {
        $this->viewHelperManager = $em;
    }

    /**
     * Returns wether this notification overrides disabled email notification
     * of the user.
     *
     * @return bool
     */
    public function forceMail() : bool
    {
        return static::FORCE_MAIL;
    }

    /**
     * Retrieve the subject to use for emails.
     *
     * @return string
     */
    public function getMailSubject() : string
    {
        return $this->getTitle();
    }

    /**
     * Retrieve the text body to use for emails.
     *
     * @return string
     */
    public function getMailBodyText() : string
    {
        return $this->getMessageTextLong();
    }

    /**
     * Retrieve the HTML body to use for emails.
     *
     * @return string
     */
    public function getMailBodyHTML() : string
    {
        return $this->getMessageHtml();
    }

    /**
     * Retrieve the (short) text to use for this notification, should be simple,
     * e.g. for text-to-speech output.
     *
     * @return string
     */
    public function getMessageTextShort() : string
    {
        $translate = $this->viewHelperManager->get('translate');
        $params = $this->getParams();
        $message = empty($params['message'])
            ? 'Notification has no message set!'
            : $params['message'];
        return $translate([$message, $params]);
    }

    /**
     * Retrieve the (long) text to use for this notification, could contain URLs,
     * e.g if the notification is sent to a chat/messenger/etc.
     *
     * @return string
     */
    public function getMessageTextLong() : string
    {
        return $this->getMessageTextShort();
    }

    /**
     * Retrieve the HTML to use for this notification, e.g. for display on the
     * website, can contain markup/links/buttons.
     *
     * @return string
     */
    public function getMessageHtml() : string
    {
        return $this->getMessageTextLong();
    }

    /**
     * Retrieve the title to use for this notification, may be empty.
     *
     * @return string
     */
    public function getTitle() : string
    {
        $translate = $this->viewHelperManager->get('translate');
        return $translate('message.system.notification');
    }

// <editor-fold defaultstate="collapsed" desc="mailable">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $mailable = false;

    /**
     * Returns whether or not the message can be pulled via the API.
     *
     * @return bool
     */
    public function isMailable() : bool
    {
        return $this->mailable;
    }

    /**
     * Sets whether or not the message can be pulled via the API.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setMailable(bool $value)
    {
        $this->mailable = $value;

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
// <editor-fold defaultstate="collapsed" desc="pullable">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $pullable = false;

    /**
     * Returns whether or not the message can be pulled via the API.
     *
     * @return bool
     */
    public function isPullable() : bool
    {
        return $this->pullable;
    }

    /**
     * Sets whether or not the message can be pulled via the API.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setPullable(bool $value)
    {
        $this->pullable = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="pushable">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $pushable = false;

    /**
     * Returns
     *
     * @return bool
     */
    public function isPushable() : bool
    {
        return $this->pushable;
    }

    /**
     * Sets whether or not the message should be pushed to the User, e.g. via
     * HTTP request.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setPushable(bool $value)
    {
        $this->pushable = $value;

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
