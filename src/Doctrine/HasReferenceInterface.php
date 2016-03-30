<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use Doctrine\ORM\EntityManager;

/**
 * General interface for entities that support polymorphic references.
 */
interface HasReferenceInterface
{
    /**
     * Retrieve the referenced object for the given entity.
     *
     * @param EntityManager $em
     *
     * @return EntityInterface or null if no object referenced.
     */
    public function getReference(EntityManager $em);

    /**
     * Stores the reference to the given entity.
     *
     * @param EntityManager   $em
     * @param EntityInterface $object
     */
    public function setReference(EntityManager $em, EntityInterface $object);

    /**
     * Returns the class of the referenced object.
     *
     * @return string
     */
    public function getReferenceClass();

    /**
     * Sets the class of the referenced object.
     *
     * @param string $class
     *
     * @return self
     */
    public function setReferenceClass($class);

    /**
     * Returns the identifier values of the referenced object.
     * Will be returned as json to avoid errors when the column is used as key:
     * In the UnitOfWork Doctrine creates an idHash, if we would return an array here
     * the implode() would throw "Notice: Array to string conversion".
     *
     * @return string
     */
    public function getReferenceIdentifier();

    /**
     * Sets the reference objects identifier(s).
     * The parameter must be a json-encoded array as retrieved by
     * $classMetaData->getIdentifiers($entity).
     *
     * @param string $identifier
     *                           return self
     */
    public function setReferenceIdentifier($identifier);
}
