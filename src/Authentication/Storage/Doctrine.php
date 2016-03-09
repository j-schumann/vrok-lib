<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Authentication\Storage;

use Doctrine\Common\Persistence\ObjectManager;
use Zend\Authentication\Storage;
use Zend\Authentication\Storage\StorageInterface;

/**
 * Stores the user ID in the session, retrieves the object from the database.
 */
class Doctrine implements StorageInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var mixed
     */
    protected $resolvedIdentity;

    /**
     * @var ObjectManager
     */
    protected $entityManager = null;

    /**
     * Class constructor - stores the EntityManager instance.
     *
     * @param ObjectManager $entityManager
     */
    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;;
    }

    /**
     * Returns true if and only if storage is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        if ($this->getStorage()->isEmpty()) {
            return true;
        }
        $identity = $this->read();
        if ($identity === null) {
            $this->clear();

            return true;
        }

        return false;
    }

    /**
     * Returns the contents of the storage.
     */
    public function read()
    {
        if (null !== $this->resolvedIdentity) {
            return $this->resolvedIdentity;
        }

        $identity = $this->getStorage()->read();

        if (is_int($identity) || is_scalar($identity)) {
            $identity = $this->entityManager->getRepository('Vrok\Entity\User')->find($identity);
        }

        if ($identity) {
            $this->resolvedIdentity = $identity;
        } else {
            $this->resolvedIdentity = null;
        }

        return $this->resolvedIdentity;
    }

    /**
     * Writes $contents to storage.
     *
     * @param mixed $contents
     */
    public function write($contents)
    {
        $this->resolvedIdentity = null;
        $this->getStorage()->write($contents);
    }

    /**
     * Clears contents from storage.
     */
    public function clear()
    {
        $this->resolvedIdentity = null;
        $this->getStorage()->clear();
    }

    /**
     * Retrieve the current storage.
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        if (null === $this->storage) {
            $this->setStorage(new Storage\Session());
        }

        return $this->storage;
    }

    /**
     * Allows to set a custom storage.
     *
     * @param StorageInterface $storage
     *
     * @return self
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }
}
