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
 * Stores a single meta value for an object.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="meta_objects")
 */
class ObjectMeta extends Entity implements HasOwnerInterface
{
    use \Vrok\Doctrine\Traits\CreationDate;
    use \Vrok\Doctrine\Traits\ModificationDate;

// <editor-fold defaultstate="collapsed" desc="ownerClass">
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false)
     */
    protected $ownerClass = null;

    /**
     * Returns the class of the owner of this object.
     *
     * @return string
     */
    public function getOwnerClass()     {
        return $this->ownerClass;
    }

    /**
     * Sets the owner class of this object.
     *
     * @param string $class
     * @return self
     */
    public function setOwnerClass($class)     {
        $this->ownerClass = $class;
        return $this;
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="ownerIdentifier">
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $ownerIdentifier = null;

    /**
     * Returns the value that identifies to owner of this object.
     *
     * @return mixed
     */
    public function getOwnerIdentifier()     {
        return $this->ownerIdentifier;
    }

    /**
     * Sets the owner identifier.
     *
     * @param mixed $identifier
     * return self
     */
    public function setOwnerIdentifier($identifier)     {
        $this->ownerIdentifier = $identifier;
        return $this;
    }

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="name">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=false)
     */
    protected $name;

    /**
     * Returns the meta name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the meta name.
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="value">
    /**
     * @var string
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    protected $value;

    /**
     * Returns the meta value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets the meta value.
     *
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = (string) $value;
        return $this;
    }
// </editor-fold>
}
