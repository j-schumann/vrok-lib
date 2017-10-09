<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use Vrok\Doctrine\AbstractFilter;
use Vrok\Doctrine\Traits\FilterReferenceFunctions;

/**
 * Implements functions to query for calendar entries by often used or complex filters to
 * avoid code duplication.
 */
class CalendarEntryFilter extends AbstractFilter
{
    use FilterReferenceFunctions;

    /**
     * Only entries starting and/or ending within the given date/time range are returned.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return self
     */
    public function byRange(\DateTime $startDate, \DateTime $endDate)
    {
        $this->qb->andWhere(
            '('.$this->alias.'.startDate >= :startDate'
                .' AND '.$this->alias.'.startDate <= :endDate'
                .' OR '.$this->alias.'.endDate >= :startDate'
                .' AND '.$this->alias.'.endDate <= :endDate)'
        )
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        return $this;
    }

    /**
     * Only entries of the given type are returned.
     *
     * @param string $type class name or short name from the discriminator map
     *
     * @return self
     */
    public function byType($type)
    {
        $this->qb->andWhere($this->alias.' INSTANCE OF :entryType')
                 ->setParameter('entryType', $type);

        return $this;
    }
}
