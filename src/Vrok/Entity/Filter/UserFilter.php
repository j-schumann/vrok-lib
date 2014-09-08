<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use Vrok\Doctrine\AbstractFilter;

/**
 * Implements functions to query for Users by often used or complex filters to
 * avoid code duplication.
 */
class UserFilter extends AbstractFilter
{
    protected $groupJoin = false;

    /**
     * Retrieve only users where the given pattern is either in the userName or email
     * or displayName.
     *
     * @param string $pattern
     * @return self
     */
    public function byName($pattern)
    {
        $this->qb->andWhere('('.$this->alias.'.username  LIKE :pattern'
                .' OR '.$this->alias.'.email  LIKE :pattern'
                .' OR '.$this->alias.'.displayName  LIKE :pattern)')
            ->setParameter('pattern', '%'.$pattern.'%');
        return $this;
    }

    /**
     * Only users assigned to the given group are returned.
     *
     * @param int $group
     * @return self
     */
    public function byGroupId($group)
    {
        $this->joinGroups();
        $this->qb->andWhere('g.id = :group')
            ->setParameter('group', $group);
        return $this;
    }

    /**
     * Retrieve only users that are not marked as deleted.
     *
     * @return self
     */
    public function areNotDeleted()
    {
        $this->qb->andWhere($this->alias.'.deletedAt IS NULL');
        return $this;
    }

    /**
     * Join with the group table and hydrate the group data.
     *
     * @return self
     */
    public function joinGroups()
    {
        if (!$this->groupJoin) {
            $this->qb->join($this->alias.'.groups g')->select($this->alias.', g');
            $this->groupJoin = true;
        }
        return $this;
    }
}
