<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

/**
 * Allows the implementing entity to reference any given record without requiring an
 * explicit relation. Allows polymorphic references.
 */
trait ObjectReference
{
    /**
     * Retrieve the referenced object for the given entity.
     *
     * @param \Doctrine\ORM\EntityManager $em
     *
     * @return \Vrok\Doctrine\EntityInterface or null if no object referenced.
     */
    public function getReference(\Doctrine\ORM\EntityManager $em)
    {
        if (!$this->getReferenceClass() || !$this->getReferenceIdentifier()) {
            return;
        }

        $repo = $em->getRepository($this->getReferenceClass());

        return $repo->find(json_decode($this->getReferenceIdentifier(), true));
    }

    /**
     * Stores the reference to the given entity.
     *
     * @param \Doctrine\ORM\EntityManager    $em
     * @param \Vrok\Doctrine\EntityInterface $object
     */
    public function setReference(
        \Doctrine\ORM\EntityManager $em,
        \Vrok\Doctrine\EntityInterface $object
    ) {
        $this->setReferenceClass(get_class($object));
        $this->setReferenceIdentifier(json_encode($object->getIdentifiers($em)));
    }

// <editor-fold defaultstate="collapsed" desc="referenceClass">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $referenceClass = null;

    /**
     * Returns the class of the referenced object.
     *
     * @return string
     */
    public function getReferenceClass()
    {
        return $this->referenceClass;
    }

    /**
     * Sets the class of the referenced object.
     *
     * @param string $class
     *
     * @return self
     */
    public function setReferenceClass($class)
    {
        $this->referenceClass = $class;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="referenceIdentifier">
    /**
     * @var array
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $referenceIdentifier = null;

    /**
     * Returns the identifier values of the referenced object.
     * Will be returned as json to avoid errors when the column is used as key:
     * In the UnitOfWork Doctrine creates an idHash, if we would return an array here
     * the implode() would throw "Notice: Array to string conversion".
     *
     * @return string
     */
    public function getReferenceIdentifier()
    {
        return $this->referenceIdentifier;
    }

    /**
     * Sets the reference objects identifier(s).
     * The parameter must be a json-encoded array as retrieved by
     * $classMetaData->getIdentifiers($entity).
     *
     * @param string $identifier
     *                           return self
     */
    public function setReferenceIdentifier($identifier)
    {
        $this->referenceIdentifier = $identifier;

        return $this;
    }
// </editor-fold>
}
