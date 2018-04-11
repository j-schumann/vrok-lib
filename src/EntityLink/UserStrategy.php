<?php

/**
 * @copyright   (c) 2014-18, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\EntityLink;

use Vrok\Entity\User;
use Vrok\Exception;
use Vrok\Service\UserManager;

/**
 * Strategy for searching, presenting and linking to users.
 */
class UserStrategy implements StrategyInterface
{
    /**
     * UserManager instance.
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
     * Returns the URL to which the XHR with the pattern to search for entities is
     * sent to, the action must return the result in uniform format for all types.
     *
     * @return string
     */
    public function getSearchUrl() : string
    {
        return $this->manager->getUserSearchUrl();
    }

    /**
     * Returns the URL to the admin page to view or edit the user.
     *
     * @param User $user
     *
     * @return string
     */
    public function getEditUrl(/*User*/ $user) : string
    {
        if (! $this->isSupported($user)) {
            throw new Exception\InvalidArgumentException('Unsupported object for UserStrategy!');
        }
        return $this->manager->getUserAdminUrl($user->getId());
    }

    /**
     * Returns a string with identifying information about the object,
     * e.g. username + email; account number etc.
     *
     * @param User $user
     *
     * @return object
     */
    public function getPresentation(/*User*/ $user) : string
    {
        if (! $this->isSupported($user)) {
            throw new Exception\InvalidArgumentException('Unsupported object for UserStrategy!');
        }

        $name  = $user->getUsername();
        $email = $user->getEmail();

        return $name == $email
            ? $email
            : "$name ($email)";
    }

    /**
     * Checks if the given object is a valid entity supported by this strategy.
     *
     * @param object $user
     *
     * @return bool
     */
    public function isSupported(object $user) : bool
    {
        return $user instanceof User;
    }
}
