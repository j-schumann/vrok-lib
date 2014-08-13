<?php

namespace Vrok\Doctrine;

use Doctrine\ORM\QueryBuilder;

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
     * Executes the constructed query and returns the result.
     *
     * @return array
     */
    public function getResult()
    {
        return $this->qb->getQuery()->getResult();
    }
}
