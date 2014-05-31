<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

use Vrok\Entity\User;
use Vrok\User\Manager;
use Zend\EventManager\EventInterface;

/**
 * Strategy enabling User entities to be used as owners.
 */
class UserStrategy implements StrategyInterface
{
    /**
     * Stores the listeners attached to the eventmanager.
     *
     * @var \Zend\Stdlib\CallbackHandler[]
     */
    protected $listeners = array();

    /**
     * UserManager instance
     *
     * @var Manager
     */
    protected $manager = null;

    /**
     * Class constructor - stores the given manager instance.
     *
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Returns the owner instance.
     *
     * @param mixed $ownerIdentifier    primary key for fetching the owner object
     * @return User
     */
    public function getOwner($ownerIdentifier)
    {
        $repository = $this->manager->getUserRepository();
        return $repository->getById((int)$ownerIdentifier);
    }

    /**
     * Returns the identifier to use in the owned entity.
     * Only scalars are allowed, type should match the column type for the
     * ownerIdentifier in the owned entity. Composite identifiers aren't supported.
     *
     * @todo validate $owner
     * @param User $owner
     */
    public function getOwnerIdentifier(/*User*/ $owner)
    {
        return $owner->getId();
    }

    /**
     * Returns the URL to which the XHR with the pattern to search for owners is
     * sent to, the action must return the result in uniform format for all types.
     *
     * @return string
     */
    public function getOwnerSearchUrl()
    {
        return $this->manager->getUserSearchUrl();
    }

    /**
     * Returns the URL to the admin page to view or edit the owner.
     *
     * @todo validate $owner
     * @param User $owner
     * @return string
     */
    public function getOwnerAdminUrl(/*User*/ $owner)
    {
        return $this->manager->getUserAdminUrl($owner->getId());
    }

    /**
     * Returns a string with identifying information about the owner object,
     * e.g. username + email; account number etc.
     *
     * @todo validate $owner
     * @param User $owner
     * @return object
     */
    public function getOwnerPresentation(/*User*/ $owner)
    {
        $name = $owner->getUsername();
        $email = $owner->getEmail();
        return $name == $email
            ? $email
            : "$name ($email)";
    }

    /**
     * Checks if the given object is a valid owner supported by this strategy.
     *
     * @param object $owner
     * @return bool
     */
    public function isValidOwner($owner)
    {
        return $owner instanceof User;
    }

    /**
     * Checks if the we are responsible for the queried owner class
     * Attached to the shared eventmanager on module bootstrap.
     *
     * @param EventInterface $e
     * @return self     or null if no User (sub)class is requested
     */
    public static function onGetOwnerStrategy(EventInterface $e)
    {
        $classes = $e->getParam('classes');
        if (!in_array('Vrok\Entity\User', $classes)) {
            return null;
        }

        $ownerService = $e->getTarget();
        $serviceLocator = $ownerService->getServiceLocator();
        $userManager = $serviceLocator->get('UserManager');

        return new self($userManager);
    }
}
