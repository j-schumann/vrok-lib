<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Service to retrieve and set the owner entities for the owned entities.
 */
class OwnerService implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ServiceLocatorAwareTrait;

    const EVENT_GET_OWNER_STRATEGY = 'getOwnerStrategy';

    /**
     * Hash of all registered entities that may have an owner and the possible owning
     * classes.
     *
     * @var array
     */
    protected $allowedOwners = array();

    /**
     * Hash containing all used strategies to avoid triggering the event multiple times.
     *
     * @var array
     */
    protected $strategies = array();

    /**
     * Retrieve the owner assigned to the given entity.
     *
     * @param HasOwnerInterface $entity
     * @return object   or null if no owner is assigned or was not found
     */
    public function getOwner(HasOwnerInterface $entity)
    {
        if (!$entity->getOwnerClass() || !$entity->getOwnerIdentifier()) {
            return null;
        }

        $strategy = $this->getOwnerStrategy($entity->getOwnerClass());
        return $strategy->getOwner($entity->getOwnerIdentifier());
    }

    /**
     * Assigns the owner to the given entity
     *
     * @param HasOwnerInterface $entity
     * @param object $owner
     * @throws Exception\InvalidArgumentException if the owner is not allowed for the entity
     */
    public function setOwner(HasOwnerInterface $entity, $owner)
    {
        $ownerClass = get_class($owner);
        if (!$this->isAllowedOwner($entity, $owner)) {
            throw new Exception\InvalidArgumentException('Given owner ('
                    .$ownerClass.') is not allowed for '.get_class($entity));
        }

        $entity->setOwnerClass($ownerClass);
        $entity->setOwnerIdentifier($this->getOwnerIdentifier($owner));
    }

    /**
     * Retrieve the scalar identifier for the given owner entity.
     *
     * @param object $owner
     * @return mixed
     */
    public function getOwnerIdentifier($owner)
    {
        $strategy = $this->getOwnerStrategy(get_class($owner), true);
        return $strategy->getOwnerIdentifier($owner);
    }


    /**
     * Returns the URL to which the XHR with the pattern to search for owners is
     * sent to, the action must return the result in uniform format for all types.
     *
     * @param string $ownerClass
     * @return string
     */
    public function getOwnerSearchUrl($ownerClass)
    {
        $strategy = $this->getOwnerStrategy($ownerClass);
        return $strategy->getOwnerSearchUrl();
    }

    /**
     * Returns the URL to the admin page to view or edit the owner.
     *
     * @param object $owner
     * @return string
     */
    public function getOwnerAdminUrl($owner)
    {
        $strategy = $this->getOwnerStrategy(get_class($owner));
        return $strategy->getOwnerAdminUrl($owner);
    }

    /**
     * Returns a string with identifying information about the owner object,
     * e.g. username + email; account number etc.
     *
     * @param object $owner
     * @return string
     */
    public function getOwnerPresentation($owner)
    {
        $strategy = $this->getOwnerStrategy(get_class($owner));
        return $strategy->getOwnerPresentation($owner);
    }

    /**
     * Retrieve the strategy object to use for the given owner class. Stores the event
     * result in a local hash.
     *
     * @triggers getOwnerStrategy
     * @param string $ownerClass
     * @param bool $throwException
     * @return OwnerStrategyInterface   or null if none found
     * @throws Exception\RuntimeException if no strategy was found and throwException is true
     */
    public function getOwnerStrategy($ownerClass, $throwException = false)
    {
        if (isset($this->strategies[$ownerClass])) {
            return $this->strategies[$ownerClass];
        }

        $classes = class_parents($ownerClass);
        $classes[] = $ownerClass;

        // try to find a strategy that feels responsible for the given class or
        // one of its parents, first answer wins
        $results = $this->getEventManager()->triggerUntil(
            self::EVENT_GET_OWNER_STRATEGY,
            $this,
            array('classes' => $classes),
            function($result) {
                return $result instanceof OwnerStrategyInterface;
            }
        );

        if ($results->stopped()) {
            foreach ($classes as $class) {
                $this->strategies[$class] = $results->last();
            }
            return $this->strategies[$ownerClass];
        }

        if ($throwException) {
            throw new Exception\RuntimeException('No strategy for '.$ownerClass.' found!');
        }

        return null;
    }

    /**
     * Checks if the given owner is allowed for the given entity.
     *
     * @param object $entity
     * @param object $owner
     * @return bool
     */
    public function isAllowedOwner($entity, $owner)
    {
        foreach($this->allowedOwners as $entityClass => $owners) {
            if (!$entity instanceOf $entityClass && !is_subclass_of($entity, $entityClass)) {
                continue;
            }

            foreach($owners as $ownerClass) {
                if ($owner instanceof $ownerClass || is_subclass_of($owner, $ownerClass)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve all owner classes that are allowed for the given entity.
     * (Does not include the child classes of course.)
     *
     * @param string $entityClass
     * @return array
     */
    public function getAllowedOwners($entityClass)
    {
        return isset($this->allowedOwners[$entityClass])
            ? $this->allowedOwners[$entityClass]
            : array();
    }

    /**
     * Adds an owner class for the given entity.
     *
     * @param string $entityClass
     * @param string $ownerClass
     */
    public function addAllowedOwner($entityClass, $ownerClass)
    {
        if (!isset($this->allowedOwners[$entityClass])) {
            $this->allowedOwners[$entityClass] = array();
        }

        $this->allowedOwners[$entityClass][$ownerClass] = $ownerClass;
    }

    /**
     * Sets multiple entity=>owner relations at once.
     *
     * @todo validate arg
     * @param array $allowedOwners  array(entity => array(owner, ...), ...)
     */
    public function setAllowedOwners($allowedOwners)
    {
        $this->allowedOwners = array_merge($this->allowedOwners, $allowedOwners);
    }
}
