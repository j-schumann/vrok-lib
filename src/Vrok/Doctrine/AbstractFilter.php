<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as PaginatorAdapter;
use Zend\Paginator\Paginator as ZendPaginator;

/**
 * Base class for helper classes that allow filtering of entities and their relations.
 * Used to avoid rewriting recurring complex queries.
 */
abstract class AbstractFilter
{
    /**
     * QueryBuilder instance
     *
     * @var QueryBuilder
     */
    protected $qb = null;

    /**
     * The alias used when the queryBuilder was created.
     *
     * @var string
     */
    protected $alias = '';

    /**
     * Class constructor, stores the dependency.
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;

        // convenience, cache the result for fast access
        $this->alias = $this->getAlias();
    }

    /**
     * Any method not implemented is forwared to the queryBuilder to allow transparent
     * access and a fluent interface:
     * $filter->byAttribute($value)->andWhere('simpleAttribute = :simple)
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->qb, $name), $arguments);
    }

    /**
     * Retrieve the main alias used in the queryBuilder for the entity.
     *
     * @link http://stackoverflow.com/a/16422221/1341762
     * @return string
     */
    public function getAlias()
    {
        return current($this->qb->getDQLPart('from'))->getAlias();
    }

    /**
     * Retrieve the used QueryBuilder.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * Retrieve the constructed query.
     *
     * @return \Doctrine\ORM\Query
     */
    public function getQuery()
    {
        return $this->qb->getQuery();
    }

    /**
     * Retrieve the number of matching records for the current query.
     *
     * @return int
     */
    public function getCount()
    {
        // cloning of the QB does not work, so we store the old select part:
        $old = $this->qb->getDQLPart('select');
        $this->qb->select('count('.$this->alias.')');
        $count = $this->qb->getQuery()->getSingleScalarResult();
        $this->qb->add('select', $old[0]);
        return $count;
    }

    /**
     * Executes the constructed query and returns the result.
     *
     * @return array
     */
    public function getResult()
    {
        return $this->qb->getQuery()->getResult();
    }

    /**
     * Sorts the result by the given field..
     *
     * @param string $field
     * @param string $order
     * @return self
     */
    public function orderByField($field, $order = 'asc')
    {
        $this->qb->orderBy($this->alias.'.'.$field, $order);
        return $this;
    }

    /**
     * Constructs a paginator for the current query.
     *
     * @return \Zend\Paginator\Paginator
     */
    public function getPaginator()
    {
        $query = $this->getQuery();
        return new ZendPaginator(new PaginatorAdapter(new ORMPaginator($query)));
    }
}
