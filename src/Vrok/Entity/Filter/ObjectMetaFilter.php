<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use Vrok\Doctrine\AbstractFilter;
use Vrok\Doctrine\Traits\FilterReferenceFunctions;

/**
 * Implements functions to query for object meta entries by often used or complex filters to
 * avoid code duplication.
 */
class ObjectMetaFilter extends AbstractFilter
{
    use FilterReferenceFunctions;

    /**
     * Retrieve only meta entries for the given meta name.
     *
     * @param string $name
     * @return self
     */
    public function byName($name)
    {
        $this->qb->andWhere($this->alias.".name = :name")
           ->setParameter('name', $name);
        return $this;
    }
}
