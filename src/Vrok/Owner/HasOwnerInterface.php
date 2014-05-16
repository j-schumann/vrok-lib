<?php

namespace Vrok\Owner;

/**
 * General interface for any entities that can be assigned to an owner.
 * If there are polymorphic owners the implementing class should be subclassed
 * for each owner type or use the strategy pattern.
 */
interface HasOwnerInterface
{
    /**
     * Returns the owner of this object.
     *
     * @return object
     */
    public function getOwner();

    /**
     * Sets the owner of this object.
     *
     * @param object $owner
     */
    public function setOwner($owner);

    /**
     * Returns the URL to which the XHR with the pattern to search for owners is
     * sent to, The action must return the result in uniform format for all types.
     *
     * @return string
     */
    public function getOwnerSearchUrl();

    /**
     * Returns the URL to the admin page to view or edit the owner of the current model.
     *
     * @return string
     */
    public function getOwnerAdminUrl();
}
