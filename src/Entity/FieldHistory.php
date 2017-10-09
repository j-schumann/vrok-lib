<?php

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;
use Vrok\Doctrine\HasReferenceInterface;
use Vrok\Doctrine\Traits\ModificationDate;

// @todo https://github.com/doctrine/doctrine2/issues/4131
//use Vrok\Doctrine\Traits\ObjectReference;

/**
 * Holds the last values of fields of another entity.
 * Used to provide a history to view which fields changed last and what's the previous
 * value. Which fields are monitored is decided by the LiveCycleArgs listener.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="field_history")
 */
class FieldHistory extends Entity implements HasReferenceInterface
{
    use ModificationDate;
    //use ObjectReference;

    /**
     * Retrieve the stored value as object of the given class.
     *
     * @todo Extremely hacky.
     *
     * @param string $className
     *
     * @return object
     */
    public function getObjectValue($className)
    {
        // the value is stored as JSON string, originating from json_encode($object)
        // getValue always returns the value decoded in associative mode
        // json_decode in object mode always returns an instance of stdClass

        // se we first need to recreate the json from the database, create the stdObject
        // and finally cast it to the desired class which serializes the object and
        // unserializes with the new class name...
        $json   = json_encode($this->getValue());
        $object = json_decode($json, false);

        return \Vrok\Stdlib\Convert::objectToObject($object, $className);
    }

    /**
     * Retrieve the referenced object for the given entity.
     *
     * @param \Doctrine\ORM\EntityManager $em
     *
     * @return \Vrok\Doctrine\EntityInterface or null if no object referenced.
     */
    public function getReference(\Doctrine\ORM\EntityManager $em)
    {
        if (! $this->getReferenceClass() || ! $this->getReferenceIdentifier()) {
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
     * @ORM\Id
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
     * @ORM\Id
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
// <editor-fold defaultstate="collapsed" desc="field">
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false)
     */
    protected $field;

    /**
     * Returns the field name.
     *
     * @return int
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets the field name.
     *
     * @param string $field
     *
     * @return self
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="value">
    /**
     * Holds the previous value of the referenced entity field.
     * Defined as json_array as this can store all data types and supports retrieval
     * as array too.
     *
     * @var mixed
     * @ORM\Column(type="json_array", length=65535, nullable=true)
     */
    protected $value;

    /**
     * Returns the previous field value.
     *
     * @return mixed
     */
    public function getValue()
    {
        // the datatype for the history field is json array so NULL is returned as array
        // -> fix here, there is most probably no semantic difference
        if (is_array($this->value) && ! count($this->value)) {
            return;
        }

        return $this->value;
    }

    /**
     * Sets the previous field value.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="user">
    /**
     * @var \Vrok\Entity\User
     * @ORM\ManyToOne(targetEntity="Vrok\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", unique=false, referencedColumnName="id", nullable=true)
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
    public function setUser(\Vrok\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }
// </editor-fold>
}
