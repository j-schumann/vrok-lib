<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

/**
 * General interface for entities that can be assigned to polymorphic owners.
 */
interface HasOwnerInterface
{
    /**
     * Returns the class of the owner of this object.
     *
     * @return string
     */
    public function getOwnerClass();

    /**
     * Sets the owner class of this object.
     *
     * @param string $class
     * @return self
     */
    public function setOwnerClass($class);

    /**
     * Returns the value that identifies to owner of this object.
     *
     * @return mixed
     */
    public function getOwnerIdentifier();

    /**
     * Sets the owner identifier.
     *
     * @param mixed $identifier
     * return self
     */
    public function setOwnerIdentifier($identifier);
}
