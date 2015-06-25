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
     * Only users within the given group are returned.
     *
     * @param string $group
     * @return self
     */
    public function byGroupName($group)
    {
        $this->joinGroups();
        $this->qb->andWhere('g.name = :group')
            ->setParameter('group', $group);
        return $this;
    }

    /**
     * Only users that are within one of the given groups are returned.
     *
     * @param type $groups
     * @return self
     */
    public function byGroupNames($groups)
    {
        $this->joinGroups();
        $this->qb->andWhere('g.name in (:groups)')
            ->setParameter('groups', $groups);
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
            $this->qb->join($this->alias.'.groups', 'g')->select($this->alias.', g');
            $this->groupJoin = true;
        }
        return $this;
    }
}
