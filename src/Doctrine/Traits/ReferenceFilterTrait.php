<?php

/**
 * @copyright   (c) 2018, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

/**
 * Used to extend Vrok\Doctrine\AbstractFilter to allow simple filtering by
 * reference.
 */
trait ReferenceFilterTrait
{
    /**
     * Uses the column:value pairs returned by the Vrok\References\ReferenceHelper
     * to filter for matching entities.
     *
     * @param array $filterValues
     *
     * @return self
     */
    public function byReference(array $filterValues)
    {
        $conditions = [];
        foreach ($filterValues as $column => $value) {
            if ($value) {
                $conditions[] = "$this->alias.$column = :$column";
                $this->qb->setParameter($column, $value);
            } else {
                $conditions[] = $this->qb->expr()->isNull("$this->alias.$column");
            }
        }

        $this->qb->andWhere('('.implode(' AND ', $conditions).')');
        return $this;
    }
}
