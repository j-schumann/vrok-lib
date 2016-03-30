<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity\Filter;

use Vrok\Entity\AbstractTodo as Todo;
use Vrok\Doctrine\AbstractFilter;
use Vrok\Doctrine\Traits\FilterReferenceFunctions;

/**
 * Implements functions to query for Todos by often used or complex filters to
 * avoid code duplication.
 */
class TodoFilter extends AbstractFilter
{
    use FilterReferenceFunctions;

    /**
     * Retrieve only todos that relate to the given deadline as the given operator
     * (<, >, =) implies.
     * This only queries todos that have a deadline set!
     *
     * @param \DateTime $deadline
     * @param string    $operator
     *
     * @return self
     */
    public function byDeadline(\DateTime $deadline, $operator = '<')
    {
        $this->qb->andWhere($this->alias.".deadline $operator :deadline")
           ->setParameter('deadline', $deadline);

        return $this;
    }

    /**
     * Only todos with the given status are returned.
     *
     * @param mixed $status single status as string or list of states as array
     *
     * @return self
     */
    public function byStatus($status)
    {
        if (is_array($status)) {
            $this->qb->andWhere(
                    $this->qb->expr()->in($this->alias.'.status', ':todoStatus'));
        } else {
            $this->qb->andWhere($this->alias.'.status = :todoStatus');
        }

        $this->qb->setParameter('todoStatus', $status);

        return $this;
    }

    /**
     * Only todos of the given type are returned.
     *
     * @param string $type class name or short name from the discriminator map
     *
     * @return self
     */
    public function byType($type)
    {
        $this->qb->andWhere($this->alias.' INSTANCE OF :todoType')
                 ->setParameter('todoType', $type);

        return $this;
    }

    /**
     * Only open todos are returned.
     *
     * @return self
     */
    public function areOpen()
    {
        return $this->byStatus([
            Todo::STATUS_ASSIGNED,
            Todo::STATUS_OPEN,
            Todo::STATUS_OVERDUE,
        ]);
    }

    /**
     * Only closed todos are returned.
     *
     * @return self
     */
    public function areClosed()
    {
        return $this->byStatus([
            Todo::STATUS_CANCELLED,
            Todo::STATUS_COMPLETED,
        ]);
    }

    /**
     * Retrieve only todos that are referenced to the given user and have the given
     * user state.
     * Use the {@link byStatus} to restrict to todo states.
     *
     * @param \Vrok\Entity\User $user
     * @param mixed             $status single status as string or list of states as array
     *
     * @return self
     */
    public function byUser(\Vrok\Entity\User $user, $status = null)
    {
        $this->qb->join($this->alias.'.userTodos', 'ut')
                 ->where('ut.user = :user')
                 ->setParameter('user', $user);

        if ($status) {
            if (is_array($status)) {
                $this->qb->andWhere($this->qb->expr()->in('ut.status', ':userStatus'));
            } else {
                $this->qb->andWhere('ut.status = :userStatus');
            }

            $this->qb->setParameter('userStatus', $status);
        }

        return $this;
    }
}
