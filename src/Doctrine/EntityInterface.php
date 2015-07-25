<?php

/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use Doctrine\ORM\EntityManager;

/**
 * Basic interface for ORM entities.
 * Required for the ObjectReference trait.
 */
interface EntityInterface
{
    /**
     * Retrieve the identifier values (e.g. the ID field) of the current record.
     *
     * @param EntityManager $em
     *
     * @return array
     */
    public function getIdentifiers(EntityManager $em);
}
