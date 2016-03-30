<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use Doctrine\ORM\EntityManager;

/**
 * Base class for ORM entities.
 */
class Entity implements EntityInterface
{
    /**
     * Implement EntityInterface.
     *
     * {@inheritdoc}
     */
    public function getIdentifiers(EntityManager $em)
    {
        $cm = $em->getClassMetadata(get_class($this));

        return $cm->getIdentifierValues($this);
    }
}
