<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

use Vrok\Doctrine\EntityInterface;
use Vrok\Entity\User;
use Vrok\Service\UserManager;

/**
 * Strategy enabling User entities to be used as owners.
 */
class UserStrategy implements StrategyInterface
{
    /**
     * UserManager instance
     *
     * @var UserManager
     */
    protected $manager = null;

    /**
     * Class constructor - stores the given manager instance.
     *
     * @param UserManager $manager
     */
    public function __construct(UserManager $manager)
    {
        $this->manager = $manager;
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
     * @todo validate $owner zend guard
     * @param User $owner
     * @return string
     */
    public function getOwnerAdminUrl(EntityInterface $owner)
    {
        return $this->manager->getUserAdminUrl($owner->getId());
    }

    /**
     * Returns a string with identifying information about the owner object,
     * e.g. username + email; account number etc.
     *
     * @todo validate $owner zend guard
     * @param User $owner
     * @return object
     */
    public function getOwnerPresentation(EntityInterface $owner)
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
    public function isValidOwner(EntityInterface $owner)
    {
        return $owner instanceof User;
    }
}
