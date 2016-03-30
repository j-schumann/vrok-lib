<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use Vrok\Doctrine\AbstractFilter;
use Vrok\Doctrine\Traits\FilterReferenceFunctions;

/**
 * Implements functions to query for history entries by often used or complex filters to
 * avoid code duplication.
 */
class FieldHistoryFilter extends AbstractFilter
{
    use FilterReferenceFunctions;

    /**
     * Retrieve only log entries for the given field name.
     *
     * @param string $field
     *
     * @return self
     */
    public function byField($field)
    {
        $this->qb->andWhere($this->alias.'.field = :field')
           ->setParameter('field', $field);

        return $this;
    }
}
