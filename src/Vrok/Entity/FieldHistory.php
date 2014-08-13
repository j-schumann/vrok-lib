<?php

namespace Vrok\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vrok\Doctrine\Entity;

/**
 * Holds the last values of fields of another entity.
 * Used to provide a history to view which fields changed last and what's the previous
 * value. Which fields are monitored is decided by the LiveCycleArgs listener.
 *
 * @ORM\Entity(repositoryClass="Vrok\Doctrine\EntityRepository")
 * @ORM\Table(name="field_history")
 */
class FieldHistory extends Entity
{
    use \Vrok\Doctrine\Traits\ModificationDate;

    /**
     * Retrieve the stored value as object of the given class.
     *
     * @todo Extremely hacky.
     * @param type $className
     * @return type
     */
    public function getObjectValue($className)
    {
        // the value is stored as JSON string, originating from json_encode($object)
        // getValue always returns the value decoded in associative mode
        // json_decode in object mode always returns an instance of stdClass

        // se we first need to recreate the json from the database, create the stdObject
        // and finally cast it to the desired class which serializes the object and
        // unserializes with the new class name...
        $json = json_encode($this->getValue());
        $object = json_decode($json, false);
        return \Vrok\Stdlib\Convert::objectToObject($object, $className);
    }

// <editor-fold defaultstate="collapsed" desc="entity">
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", nullable=false)
     */
    protected $entity;

    /**
     * Returns the entity class  name.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Sets the entity class name.
     *
     * @param string $entity
     * @return self
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="identifier">
    /**
     * Stores the primary key of the record.
     * Must be a string to allow int/string and composite keys (those are json-encoded).
     * Cannot be declared as type="json_array" as this would be stored as LONGTEXT and
     * MySQL would fail creating the PK index with "no key length specified for TEXT/BLOB"
     *
     * @var array
     * @ORM\Id
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $identifier;

    /**
     * Returns the entity identifier (primary key).
     *
     * @return string
     */
    public function getIdentifier()
    {
        return json_decode($this->identifier, true);
    }

    /**
     * Sets the entity identifier (primary key).
     *
     * @param mixed $value
     * @return self
     */
    public function setIdentifier($value)
    {
        $this->identifier = json_encode($value);
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
     * @return integer
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Sets the field name.
     *
     * @param string $field
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
        return $this->value;
    }

    /**
     * Sets the previous field value.
     *
     * @param mixed $value
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
     * @return self
     */
    public function setUser(\Vrok\Entity\User $user)
    {
        $this->user = $user;
        return $this;
    }
// </editor-fold>
}
