<?php

/**
 * @copyright   (c) 2014-18, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\EntityLink;

/**
 * Describes the functionality for searching, presenting and linking to entities.
 */
interface StrategyInterface
{
    /**
     * Returns the URL to which the XHR with the pattern to search for entities is
     * sent to, The action must return the result in uniform format for all types.
     *
     * @return string
     */
    public function getSearchUrl() : string;

    /**
     * Returns the URL to the admin page to view or edit the entity.
     *
     * @param object $entity
     *
     * @return string
     */
    public function getEditUrl(object $entity) : string;

    /**
     * Returns a string with identifying information about the object,
     * e.g. username + email; account number etc.
     *
     * @param object $entity
     *
     * @return object
     */
    public function getPresentation(object $entity) : string;

    /**
     * Checks if the given object is a valid entity supported by this strategy.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isSupported(object $entity) : bool;
}
