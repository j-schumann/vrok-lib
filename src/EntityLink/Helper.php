<?php

/**
 * @copyright   (c) 2014-18, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\EntityLink;

use Doctrine\ORM\EntityManagerInterface;
use Vrok\Exception;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service to retrieve and set the referenced entities for the owned entities.
 */
class Helper
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager = null;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceManager = null;

    /**
     * Cache containing all used strategies
     *
     * @var array
     */
    protected $strategies = [];

    /**
     * List of all known strategies.
     *
     * @var array
     */
    protected $strategyConfig = [];

    /**
     * Class constructor - stores the dependencies.
     *
     * @param EntityManagerInterface $em
     * @param ServiceLocatorInterface $sm
     */
    public function __construct(EntityManagerInterface $em, ServiceLocatorInterface $sm)
    {
        $this->entityManager = $em;
        $this->serviceManager = $sm;
    }

    /**
     * Returns the URL to which the XHR with the pattern to search for entities is
     * sent to, the action must return the result in uniform format for all types.
     *
     * @param string $class
     *
     * @return string
     */
    public function getSearchUrl(string $class) : string
    {
        $strategy = $this->getStrategy($class, true);

        return $strategy->getSearchUrl();
    }

    /**
     * Returns the URL to the admin page to view or edit the entity.
     *
     * @param object $entity
     *
     * @return string
     */
    public function getEditUrl(/*object*/ $entity) : string
    {
        $strategy = $this->getStrategy(get_class($entity), true);

        return $strategy->getEditUrl($entity);
    }

    /**
     * Returns a string with identifying information about the object,
     * e.g. username + email; account number etc.
     *
     * @param object $entity
     *
     * @return string
     */
    public function getPresentation(/*object*/ $entity) : string
    {
        $strategy = $this->getStrategy(get_class($entity), true);

        return $strategy->getPresentation($entity);
    }

    /**
     * Retrieve the strategy object to use for the given entitiy class.
     *
     * @param string $class
     * @param bool   $throwException
     *
     * @return StrategyInterface or null if none found
     *
     * @throws Exception\RuntimeException
     */
    public function getStrategy(string $class, bool $throwException = false) : ?StrategyInterface
    {
        if (isset($this->strategies[$class])) {
            return $this->strategies[$class];
        }

        // check for exists or class_parents will trigger a warning
        if (! class_exists($class)) {
            if ($throwException) {
                throw new Exception\RuntimeException("Class $class does not exist!");
            }

            return null;
        }

        $classes   = class_parents($class);
        array_unshift($classes, $class);

        $found = null;
        // iterate over the classes separately up the inheritance tree, maybe we
        // have a strategy for a (child) class and another strategy for one of
        // its parents
        foreach ($classes as $search) {
            foreach ($this->strategyConfig as $strategy => $supported) {
                if (in_array($search, $supported)) {
                    $found = $strategy;
                    break;
                }
            }
            if ($found) {
                break;
            }
        }

        if (! $found) {
            if ($throwException) {
                throw new Exception\RuntimeException(
                    "No EntityLinkstrategy found for $class!"
                );
            }

            return null;
        }

        if (! $this->serviceManager->has($found)) {
            if ($throwException) {
                throw new Exception\RuntimeException(
                    "Strategy service $class is unknown!"
                );
            }

            return null;
        }

        $service = $this->serviceManager->get($found);
        if (! $service || ! $service instanceof StrategyInterface) {
            if ($throwException) {
                throw new Exception\RuntimeException(
                    "Service $found could not be fetched or does not implement the StrategyInterface!"
                );
            }

            return null;
        }

        // only add cache entry for the requested class, a parent class may get
        // another strategy
        $this->strategies[$class] = $service;

        return $service;
    }

    /**
     * Adds a strategy to the config.
     *
     * @param string $strategy  service name for the strategy
     * @param array $classes    all classes the strategy supports
     */
    public function addStrategy(string $strategy, array $classes)
    {
        $this->strategyConfig[$strategy] = array_unique(array_merge(
            $this->strategyConfig[$strategy] ?? [],
            $classes
        ));
    }

    /**
     * Sets multiple strategy configurations at once.
     *
     * @param array $strategies [strategyServiceName => [supportedClass1, ...], ...]
     */
    public function addStrategies(array $strategies)
    {
        foreach ($strategies as $strategy => $classes) {
            $this->addStrategy($strategy, $classes);
        }
    }

    /**
     * Sets all configuration options for this service.
     *
     * @param array $config [strategyServiceName => [supportedClass1, ...], ...]
     */
    public function setOptions(array $config)
    {
        if (isset($config['strategies'])) {
            $this->strategyConfig = $config['strategies'];
        }
    }

    /**
     * Returns the currently configured strategies with the classes they support.
     *
     * @return array
     */
    public function getStrategyConfig() : array
    {
        return $this->strategyConfig;
    }
}
