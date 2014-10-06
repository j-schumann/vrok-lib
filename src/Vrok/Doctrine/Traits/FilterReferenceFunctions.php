<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Traits;

/**
 * Helper for EntityFilter classes that are used for entities that implement the
 * HasReferenceInterface.
 */
trait FilterReferenceFunctions
{
    /**
     * Only entries referencing the given object are returned.
     * If no object is given all entries not referencing an object are returned.
     *
     * @param EntityInterface|null $object
     * @return self
     */
    public function byObject(\Vrok\Doctrine\EntityInterface $object = null)
    {
        if ($object) {
            $class = get_class($object);
            $em = $this->getQueryBuilder()->getEntityManager();
            $jsonIdentifier = json_encode($object->getIdentifiers($em));

            $this->qb->where($this->alias.'.referenceClass = :referenceClass')
               ->andWhere($this->alias.'.referenceIdentifier = :referenceIdentifier')
               ->setParameter('referenceClass', $class)
               ->setParameter('referenceIdentifier', $jsonIdentifier);
        }
        else {
            // explicitly match NULL or every object matches
            $this->qb
                ->andWhere($this->qb->expr()->isNull($this->alias.'.referenceClass'))
                ->andWhere($this->qb->expr()->isNull($this->alias.'.referenceIdentifier'));
        }
        return $this;
    }

    /**
     * Only entries referencing an object of the given class are returned.
     * If no class is given all entries not referencing an object are returned.
     *
     * @param string|null $class
     * @return self
     */
    public function byReferenceClass($class = null)
    {
        if ($class) {
            $this->qb->where($this->alias.'.referenceClass = :referenceClass')
               ->setParameter('referenceClass', $class);
        }
        else {
            // explicitly match NULL or every object matches
            $this->qb->andWhere($this->qb->expr()->isNull($this->alias.'.referenceClass'));
        }
        return $this;
    }
}
