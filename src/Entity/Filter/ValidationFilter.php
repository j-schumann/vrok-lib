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
 * Implements functions to query for Validations by often used or complex filters to
 * avoid code duplication.
 */
class ValidationFilter extends AbstractFilter
{
    use FilterReferenceFunctions;

    /**
     * Retrieve only validations of the given type.
     *
     * @param string $type
     *
     * @return self
     */
    public function byType($type)
    {
        $this->qb->andWhere($this->alias.'.type = :type')
            ->setParameter('type', $type);

        return $this;
    }
}
