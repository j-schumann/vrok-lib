<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

/**
 * Describes the functionality a strategy to represent an owner type has to implement.
 */
interface StrategyInterface
{
    /**
     * Returns the owner instance.
     *
     * @param mixed $ownerIdentifier    primary key for fetching the owner object
     * @return object
     */
    public function getOwner($ownerIdentifier);

    /**
     * Returns the identifier to use in the owned entity.
     * Only scalars are allowed, type should match the column type for the
     * ownerIdentifier in the owned entity. Composite identifiers aren't supported.
     *
     * @param object $owner
     */
    public function getOwnerIdentifier($owner);

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
     * @return string
     */
    public function getOwnerAdminUrl($owner);

    /**
     * Returns a string with identifying information about the owner object,
     * e.g. username + email; account number etc.
     *
     * @param object $owner
     * @return object
     */
    public function getOwnerPresentation($owner);

    /**
     * Checks if the given object is a valid owner supported by this strategy.
     *
     * @param object $owner
     * @return bool
     */
    public function isValidOwner($owner);
}
