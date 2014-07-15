<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
use Vrok\Entity\ObjectMeta;
use Vrok\Entity\SystemMeta;

/**
 * Service for retrieving and setting meta values for objects or system-wide.
 */
class Meta
{
    /**
     * @var \Doctrine\Orm\EntityManager
     */
    protected $entityManager = null;

    /**
     * List of default values.
     * Not all keys may have a default, null is returned if no value is in the datebase
     * and no default is set.
     *
     * @var array
     */
    protected $defaults = array();

    /**
     * Internal cache, to reduce database traffic for often required elements.
     * Only used for system meta.
     *
     * @var array
     */
    protected $internalCache = array();

    /**
     * Sets the required dependency.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Sets the default values to use for system meta.
     *
     * @param array $defaults
     * @return self
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
        return $this;
    }

    /**
     * Returns the default value for the given meta property or null if none set.
     * Used for system meta. If given an owner type for object meta all defaults for
     * this type are returned.
     *
     * @param string $name
     * @return mixed
     */
    public function getDefault($name)
    {
        return isset($this->defaults[$name])
            ? $this->defaults[$name]
            : null;
    }

    /**
     * Returns the default value for the given object meta property or null if none set.
     *
     * @param string $ownerType
     * @param string $name
     * @return array
     */
    public function getObjectDefault($ownerType, $name)
    {
        return isset($this->defaults[$ownerType][$name])
            ? $this->defaults[$ownerType][$name]
            : null;
    }

    /**
     * Tries to load the value for the given meta key.
     * Returns null if not found.
     *
     * First checks the internal cache for this page view (if skipInternal is not true),
     * then queries the database.
     * Values are JSON encoded in the database and decoded by default (to avoid that set
     * $decode to false).
     *
     * @param string $name          meta value name
     * @param bool $decode          set to false if the value should not be decoded, this
     *     is to allow custom decode param, use when setting custom encoded values
     * @param type $skipInternal    set to true if the internal cache should not be used
     *     but the database queried, the internal cache will be updated afterwards
     * @return mixed
     */
    public function getValue($name, $decode = true, $skipInternal = false)
    {
        if (!$skipInternal && isset($this->internalCache[$name])) {
            $value = $this->internalCache[$name];
            return $decode ? json_decode($value, true) : $value;
        }

        $sr = $this->getSystemRepository();
        $sm = $sr->find($name);
        $value = $sm ? $sm->getValue() : json_encode($this->getDefault($name));

        $this->internalCache[$name] = $value;
        return $decode ? json_decode($value, true) : $value;
    }

    /**
     * Sets the given meta property.
     * Attention: does not flush the entityManager to avoid too much DB queries.
     *
     * @param string $name      meta value name
     * @param mixed $value      the value to store, will be JSON encoded, make sure no
     *     objects are given
     * @param bool $encoded     set to true if the value is already JSON encoded
     */
    public function setValue($name, $value, $encoded = false)
    {
        $value = $encoded ? $value : json_encode($value);
        $sr = $this->getSystemRepository();

        $sm = $sr->find($name);
        if (!$sm) {
            $sm = new SystemMeta($name);
            $sr->persist($sm);
        }

        $sm->setValue($value);

        $this->internalCache[$name] = $value;
    }

    /**
     * Tries to load the value for the given meta key.
     * Returns null if not found.
     *
     * Values are JSON encoded in the database and decoded by default (to avoid that set
     * $decode to false).
     *
     * @param string $class         meta owner class
     * @param int $identifier       meta owner identifier
     * @param string $name          meta field name
     * @param bool $decode          set to false if the value should not be decoded,
     *     this is to allow custom decode param, use when setting custom encoded values
     * @param type $skipInternal    set to true if the internal cache should not be
     *     used but the database queried, the internal cache will be updated afterwards
     * @return mixed
     */
    public function getObjectValue($class, $identifier, $name, $decode = true, $skipInternal = false)
    {
        if (!$skipInternal && isset($this->internalCache[$class][$identifier][$name])) {
            $value = $this->internalCache[$class][$identifier][$name];
            return $decode ? json_decode($value, true) : $value;
        }

        $or = $this->getObjectRepository();
        $om = $or->findOneBy(array(
            'ownerClass'      => $class,
            'ownerIdentifier' => $identifier,
            'name'            => $name,
        ));

        $value = $om
            ? $om->getValue()
            : json_encode($this->getObjectDefault($class, $name));

        $this->internalCache[$class][$identifier][$name] = $value;
        return $decode ? json_decode($value, true) : $value;
    }

    /**
     * Sets the given object meta property.
     * Attention: does not flush the entityManager to avoid too much DB queries.
     *
     * @param string $class         meta owner class
     * @param int $identifier       meta owner identifier
     * @param string $name          meta value name
     * @param mixed $value          the value to store, will be JSON encoded,
     *     make sure no objects are given
     * @param bool $encoded         set to true if the value is already JSON encoded
     */
    public function setObjectValue($class, $identifier, $name, $value, $encoded = false)
    {
        $value = $encoded ? $value : json_encode($value);
        $or = $this->getObjectRepository();

        $om = $or->findOneBy(array(
            'ownerClass'      => $class,
            'ownerIdentifier' => $identifier,
            'name'            => $name,
        ));
        if (!$om) {
            $om = new ObjectMeta();
            $om->setOwnerClass($class);
            $om->setOwnerIdentifier($identifier);
            $om->setName($name);

            $or->persist($om);
        }

        $om->setValue($value);
        $this->internalCache[$class][$identifier][$name] = $value;
    }

    /**
     * Retrieve the used entityManager instance.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Sets the used entityManager instance.
     *
     * @param EntityManager $entityManager
     * @return self
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Retrieve the repository for the object meta
     *
     * @return Vrok\Doctrine\EntityRepository
     */
    public function getObjectRepository()
    {
        return $this->entityManager->getRepository('Vrok\Entity\ObjectMeta');
    }

    /**
     * Retrieve the repository for the system meta
     *
     * @return Vrok\Doctrine\EntityRepository
     */
    public function getSystemRepository()
    {
        return $this->entityManager->getRepository('Vrok\Entity\SystemMeta');
    }
}