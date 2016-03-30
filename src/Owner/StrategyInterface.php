<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

use Vrok\Doctrine\EntityInterface;

/**
 * Describes the functionality a strategy to represent an owner type has to implement.
 */
interface StrategyInterface
{
    /**
     * Returns the URL to which the XHR with the pattern to search for owners is
     * sent to, The action must return the result in uniform format for all types.
     *
     * @return string
     */
    public function getOwnerSearchUrl();

    /**
     * Returns the URL to the admin page to view or edit the owner.
     *
     * @param object $owner
     *
     * @return string
     */
    public function getOwnerAdminUrl(EntityInterface $owner);

    /**
     * Returns a string with identifying information about the owner object,
     * e.g. username + email; account number etc.
     *
     * @param object $owner
     *
     * @return object
     */
    public function getOwnerPresentation(EntityInterface $owner);

    /**
     * Checks if the given object is a valid owner supported by this strategy.
     *
     * @param object $owner
     *
     * @return bool
     */
    public function isValidOwner(EntityInterface $owner);
}
