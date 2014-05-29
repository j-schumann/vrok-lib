<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

// required in the using class:
//use Doctrine\ORM\Mapping as ORM;

/**
 * Implementation for the HasOwnerInterface.
 * Defaults to an integer as owner identifier.
 */
trait HasOwnerTrait
{
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $ownerClass = null;

    /**
     * Returns the class of the owner of this object.
     *
     * @return string
     */
    public function getOwnerClass()
    {
        return $this->ownerClass;
    }

    /**
     * Sets the owner class of this object.
     *
     * @param string $class
     * @return self
     */
    public function setOwnerClass($class)
    {
        $this->ownerClass = $class;
        return $this;
    }

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $ownerIdentifier = null;

    /**
     * Returns the value that identifies to owner of this object.
     *
     * @return mixed
     */
    public function getOwnerIdentifier()
    {
        return $this->ownerIdentifier;
    }

    /**
     * Sets the owner identifier.
     *
     * @param mixed $identifier
     * return self
     */
    public function setOwnerIdentifier($identifier)
    {
        $this->ownerIdentifier = $identifier;
        return $this;
    }
}
