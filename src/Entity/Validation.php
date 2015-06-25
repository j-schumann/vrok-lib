<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Doctrine\HasReferenceInterface;
use Vrok\Doctrine\Traits\AutoincrementId;
use Vrok\Doctrine\Traits\CreationDate;
use Vrok\Doctrine\Traits\ObjectReference;

/**
 * Validation object for confirming changes/action etc. via a token sent by
 * email.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="validations")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="referenceClass",
 *          column=@ORM\Column(type="string", nullable=false)
 *      ),
 *      @ORM\AttributeOverride(name="referenceIdentifier",
 *          column=@ORM\Column(type="string", length=255, nullable=false)
 *      ),
 * })
 */
class Validation extends Entity implements HasReferenceInterface
{
    use AutoincrementId;
    use CreationDate;
    use ObjectReference;

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
// <editor-fold defaultstate="collapsed" desc="content">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
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
     * @return self
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="token">
    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $token;

    /**
     * Returns the validation token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the validation token.
     *
     * @param string $value
     * @return self
     */
    public function setToken($value)
    {
        $this->token = $value;
        return $this;
    }

    /**
     * Sets a new random token with the given length.
     *
     * @param int $length
     * @return self
     */
    public function setRandomToken($length = 20)
    {
        $token = \Vrok\Stdlib\Random::getRandomToken((int)$length);
        return $this->setToken($token);
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="retryCount">
    /**
     * @var integer
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    protected $retryCount = 0;

    /**
     * Returns how often this validation was re-requested.
     *
     * @return integer
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * Sets how often this validation was re-requested.
     *
     * @param integer $value
     * @return self
     */
    public function setRetryCount($value)
    {
        $this->retryCount = $value;
        return $this;
    }
// </editor-fold>
}
