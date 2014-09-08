<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use Vrok\Doctrine\AbstractFilter;

/**
 * Implements functions to query for calendar entries by often used or complex filters to
 * avoid code duplication.
 */
class CalendarEntryFilter extends AbstractFilter
{
    /**
     * @var \Vrok\Owner\OwnerService
     */
    protected $ownerService = null;

    /**
     * Class constructor - stores the dependencies.
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \Vrok\Owner\OwnerService $ownerService
     */
    public function __construct(
        \Doctrine\ORM\QueryBuilder $qb,
        \Vrok\Owner\OwnerService $ownerService
    ) {
        parent::__construct($qb);
        $this->ownerService = $ownerService;
    }

    /**
     * Only entries for the given object are returned.
     * If no object is given all generic entries are returned (holidays etc).
     *
     * @param object|null $object
     * @param \Vrok\Owner\OwnerService $ownerService
     * @return self
     */
    public function byObject($object)
    {
        if ($object) {
            $identifier = $this->ownerService->getOwnerIdentifier($object);

            $this->qb->where($this->alias.'.ownerClass = :ownerClass')
               ->andWhere($this->alias.'.ownerIdentifier = :ownerIdentifier')
               ->setParameter('ownerClass', get_class($object))
               ->setParameter('ownerIdentifier', $identifier);
        }
        else {
            // explicitly match NULL or every object matches
            $this->qb->andWhere($this->qb->expr()->isNull($this->alias.'.ownerClass'))
               ->andWhere($this->qb->expr()->isNull($this->alias.'.ownerIdentifier'));
        }
        return $this;
    }

    /**
     * Only entries starting and/or ending within the given date/time range are returned.
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
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
     * @param string $type  class name or short name from the discriminator map
     * @return self
     */
    public function byType($type)
    {
        $this->qb->andWhere($this->alias." INSTANCE OF :entryType")
                 ->setParameter('entryType', $type);
        return $this;
    }
}
