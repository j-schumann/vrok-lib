<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Doctrine\ORM\EntityManager;
use Vrok\Doctrine\EntityInterface;
use Vrok\Doctrine\HasReferenceInterface;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

/**
 * Service to retrieve and set the referenced entities for the owned entities.
 */
class Owner implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    const EVENT_GET_OWNER_STRATEGY = 'getOwnerStrategy';

    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * Do not only trigger under the identifier \Vrok\Service\Owner but also
     * use the short name used as serviceManager alias.
     *
     * @var string
     */
    protected $eventIdentifier = 'OwnerService';

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
     * Class constructor - stores the dependency.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
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
     * @param EntityInterface $owner
     * @return string
     */
    public function getOwnerAdminUrl(EntityInterface $owner)
    {
        $strategy = $this->getOwnerStrategy(get_class($owner));
        return $strategy->getOwnerAdminUrl($owner);
    }

    /**
     * Returns a string with identifying information about the owner object,
     * e.g. username + email; account number etc.
     *
     * @param EntityInterface $owner
     * @return string
     */
    public function getOwnerPresentation(EntityInterface $owner)
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
     * @return StrategyInterface   or null if none found
     * @throws Exception\RuntimeException if no strategy was found and throwException is true
     */
    public function getOwnerStrategy($ownerClass, $throwException = false)
    {
        if (isset($this->strategies[$ownerClass])) {
            return $this->strategies[$ownerClass];
        }

        // check for exists or class_parents will trigger a warning
        if (!class_exists($ownerClass)) {
            if ($throwException) {
                throw new Exception\RuntimeException("Class $ownerClass does not exist!");
            }

            return null;
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
                return $result instanceof StrategyInterface;
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
     * @param HasReferenceInterface $entity
     * @param EntityInterface $owner
     * @return bool
     */
    public function isAllowedOwner(HasReferenceInterface $entity, EntityInterface $owner)
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
     * @todo use zend guard for traversable
     * @param array $allowedOwners  array(entity => array(owner, ...), ...)
     */
    public function setAllowedOwners(array $allowedOwners)
    {
        $this->allowedOwners = array_merge($this->allowedOwners, $allowedOwners);
    }
}
