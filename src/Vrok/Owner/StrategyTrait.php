<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Owner;

trait StrategyTrait
{
    /**
     * Returns the owner instance.
     *
     * @return object
     */
    public function getOwner()
    {
        $strategy = $this->getStrategy();
        return $strategy->getOwner($this->ownerIdentifier);
    }

    /**
     * Sets the owner of this object.
     *
     * @param object $owner
     */
    public function setOwner($owner)
    {
        $strategy = $this->getStrategy();
        if (!$strategy->isValidOwner($owner)) {
            return false;
        }

        $thi
    }

    /**
     * Checks if the given instance is a valid owner for the entity.
     *
     * @param object $owner
     * @return bool     true if valid, else false
     */
    public function isValidOwner($owner);

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
     * @return string
     */
    public function getOwnerAdminUrl();
}
