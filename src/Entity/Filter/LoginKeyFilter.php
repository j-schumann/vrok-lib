<?php

/**
 * @copyright   (c) 2016, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use DateTime;
use Vrok\Doctrine\AbstractFilter;
use Vrok\Entity\User;

/**
 * Implements functions to query for loginKeys by often used or complex filters
 * to avoid code duplication.
 */
class LoginKeyFilter extends AbstractFilter
{
    /**
     * Only loginKeys that are expired are returned.
     *
     * @return self
     */
    public function areExpired()
    {
        $now = new DateTime();
        $this->qb->andWhere($this->alias.'.expirationDate <= :date')
                 ->setParameter('date', $now);

        return $this;
    }

    /**
     * Only loginKeys that are NOT expired are returned.
     *
     * @return self
     */
    public function areNotExpired()
    {
        $now = new DateTime();
        $this->qb->andWhere($this->alias.'.expirationDate > :date')
                 ->setParameter('date', $now);

        return $this;
    }

    /**
     * Only the loginKeys for the given user are returned.
     *
     * @param \Vrok\Entity\User $user
     *
     * @return self
     */
    public function byUser(User $user)
    {
        $this->qb->andWhere($this->alias.'.user = :user')
                 ->setParameter('user', $user);

        return $this;
    }
}
