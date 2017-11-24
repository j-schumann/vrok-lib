<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use DateTime;
use Vrok\Doctrine\AbstractFilter;
use Vrok\Entity\User;

/**
 * Implements functions to query for notificaitons by often used or complex
 * filters to avoid code duplication.
 */
class NotificationFilter extends AbstractFilter
{
    /**
     * Only notifications newer than the given date are returned.
     *
     * @return self
     */
    public function createdAfter(DateTime $startDate) : NotificationFilter
    {
        $this->qb->andWhere($this->alias.'.createdAt > :date')
                 ->setParameter('date', $startDate);

        return $this;
    }

    /**
     * Only pullable notifications are returned.
     *
     * @return self
     */
    public function arePullable() : NotificationFilter
    {
        $this->qb->andWhere($this->alias.'.pullable = :pullable')
                 ->setParameter('pullable', true);

        return $this;
    }

    /**
     * Only notifications of the given type are returned.
     *
     * @param string $type
     *
     * @return self
     */
    public function byType(string $type) : NotificationFilter
    {
        $this->qb->andWhere($this->alias.'.type = :type')
                 ->setParameter('type', $type);

        return $this;
    }

    /**
     * Only the notifications for the given user are returned.
     *
     * @param \Vrok\Entity\User $user
     *
     * @return self
     */
    public function byUser(User $user) : NotificationFilter
    {
        $this->qb->andWhere($this->alias.'.user = :user')
                 ->setParameter('user', $user);

        return $this;
    }
}
