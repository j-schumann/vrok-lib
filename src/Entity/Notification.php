<?php

/**
 * @copyright   (c) 2014-17, Vrok
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
 * @ORM\EntityListeners({"Vrok\Entity\Listener\NotificationListener"})
 */
class Notification extends Entity
{
    use AutoincrementId;
    use CreationDate;

    /**
     * Returns an array representation of this notification.
     * This will be returned when notifications are pulled from the API and
     * pushed via HTTP.
     */
    public function toArray()
    {
        return [
            'html'      => $this->getHtml(),
            'textLong'  => $this->getTextLong(),
            'textShort' => $this->getTextShort(),
            'title'     => $this->getTitle(),
            'timestamp' => $this->getCreatedAt()->format('U'),
            'type'      => $this->getType(),
        ];
    }

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
    public function setDismissed(bool $dismissed = true) : Notification
    {
        $this->dismissed = $dismissed;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="html">
    /**
     * @var string
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    protected $html = null;

    /**
     * Returns the notification html.
     *
     * @return string
     */
    public function getHtml() : string
    {
        if (!$this->html) {
            return $this->getTextLong();
        }

        return $this->html;
    }

    /**
     * Sets the notification html.
     *
     * @param string $value
     *
     * @return self
     */
    public function setHtml(?string $value) : Notification
    {
        $this->html = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="mailable">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $mailable = false;

    /**
     * Returns whether or not the message can be pulled via the API.
     * Overridden by forceMail.
     *
     * @return bool
     */
    public function isMailable() : bool
    {
        return $this->mailable;
    }

    /**
     * Sets whether or not the message can be pulled via the API.
     * Overridden by forceMail.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setMailable(bool $value) : Notification
    {
        $this->mailable = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="mailForced">
    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default" = false})
     */
    protected $mailForced = false;

    /**
     * Returns whether this notification will ignore if the user has email
     * notifications disabled and will always be sent by email.
     *
     * @return bool
     */
    public function isMailForced() : bool
    {
        return $this->mailForced;
    }

    /**
     * Sets whether this notification will ignore if the user has email
     * notifications disabled and will always be sent by email.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setMailForced(bool $value) : Notification
    {
        $this->mailForced = $value;

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
     * Sets the parameters used to construct the notification text.
     * These can be entity IDs but the formatter should not rely on existence
     * of these entities, instead all required parameters should be given.
     *
     * @param array $params
     *
     * @return self
     */
    public function setParams(array $params) : Notification
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
    public function setPullable(bool $value) : Notification
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
    public function setPushable(bool $value) : Notification
    {
        $this->pushable = $value;

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
    public function getUser() : User
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
    public function setUser(User $user) : Notification
    {
        $this->user = $user;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="textLong">
    /**
     * @var string
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    protected $textLong = null;

    /**
     * Returns the notification short text.
     *
     * @return string
     */
    public function getTextLong() : string
    {
        if (!$this->textLong) {
            return $this->getTextShort();
        }

        return $this->textLong;
    }

    /**
     * Sets the notification short text.
     *
     * @param string $value
     *
     * @return self
     */
    public function setTextLong(?string $value) : Notification
    {
        $this->textLong = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="textShort">
    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $textShort = '';

    /**
     * Returns the notification short text.
     *
     * @return string
     */
    public function getTextShort() : string
    {
        return $this->textShort;
    }

    /**
     * Sets the notification short text.
     *
     * @param string $value
     *
     * @return self
     */
    public function setTextShort(string $value) : Notification
    {
        $this->textShort = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="title">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $title = null;

    /**
     * Returns the notification title.
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * Sets the notification title.
     *
     * @param string $value
     *
     * @return self
     */
    public function setTitle(?string $value) : Notification
    {
        $this->title = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="type">
    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=false, options={"default" = "default"})
     */
    protected $type = 'default';

    /**
     * Returns the validation type.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * Sets the validation type.
     *
     * @param string $value
     *
     * @return self
     */
    public function setType(string $value) : Notification
    {
        $this->type = $value;

        return $this;
    }
// </editor-fold>
}
