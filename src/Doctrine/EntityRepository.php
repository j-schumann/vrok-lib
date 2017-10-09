<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use Doctrine\ORM\EntityRepository as DoctrineRepository;
use Vrok\Stdlib\Guard\ObjectGuardTrait;
use Vrok\Stdlib\Guard\InstanceOfGuardTrait;
use Vrok\Stdlib\Random;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Extends the basic repository to support easy form generation and translation.
 */
class EntityRepository extends DoctrineRepository implements InputFilterProviderInterface
{
    use InstanceOfGuardTrait;
    use ObjectGuardTrait;

    /**
     * FormHelper instance for this table.
     *
     * @var FormHelper
     */
    protected $formHelper = null;

    /**
     * Counts entities by a set of criteria.
     *
     * @todo remove when Doctrine commit a90035e is stable and the base class
     * contains this method.
     *
     * @param array $criteria
     *
     * @return int The quantity of objects that matches the criteria.
     */
    public function count(array $criteria)
    {
        $persister = $this->_em->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->count($criteria);
    }

    /**
     * Finds entities that do not match a set of criteria.
     *
     * @author http://stackoverflow.com/users/710693/jcm
     *
     * @link http://stackoverflow.com/questions/14085946/doctrine-findby-does-not-equal
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     */
    public function findByNot(
        array $criteria,
        array $orderBy = null,
        $limit = null,
        $offset = null
    ) {
        $qb   = $this->getEntityManager()->createQueryBuilder();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $qb->select('entity')->from($this->getEntityName(), 'entity');

        foreach ($criteria as $field => $value) {
            $qb->andWhere($expr->neq('entity.'.$field, $value));
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy('entity.'.$field, $order);
            }
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Persists the given entity.
     * Convenience function because getEntityManager is protected.
     *
     * @param object $entity
     */
    public function persist($entity)
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * Removes the given entity from the database.
     * Convenience function because getEntityManager is protected.
     *
     * @param object $entity
     */
    public function remove($entity)
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * Returns true if the entity has a field with the given name, else false.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return $this->getClassMetadata()->hasField($fieldName);
    }

    /**
     * Retrieve the primary key value[s] for the given entity.
     *
     * @param object $entity
     *
     * @return array
     */
    public function getIdentifierValues($entity)
    {
        $class = $this->getClassName();
        $this->guardForInstanceOf($entity, $class);

        $meta = $this->getClassMetadata();

        return $meta->getIdentifierValues($entity);
    }

    /**
     * Uses the FormHelper to build a form element specification to use with the
     * form factory.
     * Overwrite in the child class to add custom attributes or change the
     * element type.
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getFormElementDefinition($fieldName)
    {
        $definition = $this->getFormHelper()->getElementDefinition($fieldName);

        return $definition;
    }

    /**
     * Uses the FormHelper to build a input specification to use with the
     * InputFilter factory.
     * Overwrite in the child class to add custom filters/validators.
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getInputSpecification($fieldName)
    {
        $spec = $this->getFormHelper()->getInputSpecification($fieldName);

        return $spec;
    }

    /**
     * Returns a InputFilter specification to use with the InputFilter factory
     * to validate & filter all the fields.
     * Uses the custom {@see getInputSpecification}.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        $spec = [];
        foreach ($this->_class->getFieldNames() as $fieldName) {
            $spec[$fieldName] = $this->getInputSpecification($fieldName);
        }
        foreach ($this->_class->getAssociationNames() as $association) {
            $spec[$association] = $this->getInputSpecification($association);
        }

        return $spec;
    }

    /**
     * Retrieve a configured InputFilter instance with all entity fields.
     *
     * @return \Zend\InputFilter\InputFilter
     */
    public function getInputFilter()
    {
        $factory = new \Zend\InputFilter\Factory();

        return $factory->createInputFilter($this->getInputFilterSpecification());
    }

    /**
     * Retrieve the FormHelper instance for this table.
     *
     * @return FormHelper
     */
    public function getFormHelper()
    {
        if (! $this->formHelper) {
            $this->formHelper = new FormHelper($this->_class, $this->getEntityManager());
        }

        return $this->formHelper;
    }

    /**
     * Helper function to return a unified string to use as translation identifer.
     *
     * @param string $fieldName (optional) the fieldname to append to the string
     *
     * @return string
     */
    public function getTranslationString($fieldName = null)
    {
        return Common::getEntityTranslationString($this->getEntityName(), $fieldName);
    }

    /**
     * Updates the given entity with the provided data.
     * Overwrite in child classes to add custom filters, e.g. for composed
     * fields.
     * Calls entityManager->persist.
     *
     * @param Entity $instance
     * @param array  $formData
     * @param array  $changeset if given the resulting changeset of the update
     *                          is stored in the referenced array
     *
     * @return Entity
     */
    public function updateInstance(Entity $instance, array $formData, array &$changeset = null)
    {
        if ($changeset !== null) {
            $old = $this->getInstanceData($instance);
        }
        $hydrator = new DoctrineObject($this->getEntityManager());
        $object = $hydrator->hydrate($formData, $instance);

        $this->getEntityManager()->persist($object);

        if ($changeset !== null) {
            $new = $this->getInstanceData($object);
            foreach ($old as $k => $v) {
                if ($old[$k] != $new[$k]) {
                    $changeset[$k] = [$old[$k], $new[$k]];
                }
            }
        }

        // in rare cases when we create a new instance and set the same
        // identifiers as an already existing record uses, the hdydrator will
        // return a new instance (the existing record) updated with the data and
        // leave the given $instance unchanged.
        // For this case we return the existing record here to keep the
        // code working, if it's not intended to update the object, the code
        // should check for objectExists etc via validators before.
        return $object;
    }

    /**
     * Retrieve the entity data as array.
     *
     * @param Entity $instance
     *
     * @return array
     */
    public function getInstanceData(Entity $instance)
    {
        $hydrator = new DoctrineObject($this->getEntityManager());

        return $hydrator->extract($instance);
    }

    /**
     * Sets the given field of the given entity to a random token of the defined
     * length.
     * Attention: flush the EM before using, uses native queries and table locks.
     *
     * @param EntityInterface $entity
     * @param string          $field
     * @param int             $length
     *
     * @throws Exception\RuntimeException
     */
    public function setRandomToken(EntityInterface $entity, $field, $length = 48)
    {
        $em         = $this->getEntityManager();
        $table      = $this->getClassMetadata()->getTableName();
        $connection = $em->getConnection();
        /* @var $connection \Doctrine\DBAL\Connection */

        // we can not use doctrines locking mechanisms as they can only lock
        // existing entities but we also need to prevent other processes from
        // inserting a new entity with the same token, thus we lock the complete
        // table here.
        // We could catch the UniqueConstraintViolationException when using the
        // entityManager to insert the token but this also closes the entity
        // manager so we cannot retry with a new token
        $connection->exec("LOCK TABLES $table WRITE;");

        $result    = $connection->fetchAll("SELECT $field FROM $table;");
        $tokenList = array_map('current', $result);

        $i = 0;
        do {
            $rnd = Random::getRandomToken($length);
        } while (in_array($rnd, $tokenList) && ++$i < 10);

        if ($i >= 10) {
            $connection->exec('UNLOCK TABLES;');
            throw new Exception\RuntimeException('Generation of a unique token'
                ." for field '$field' in table '$table' failed $i times,"
                .' please increase the possible range / token length!');
        }

        $identifiers = $entity->getIdentifiers($em);
        $clauses     = [];
        foreach ($identifiers as $column => $value) {
            $clauses[] = "$column = '$value'";
        }
        $cond = implode(' AND ', $clauses);

        // we cannot use the entityManager here as he would implicitly use
        // transactions causing the release of the table lock -> native query
        $connection->executeUpdate("UPDATE $table SET $field='$rnd' WHERE $cond;");

        $connection->exec('UNLOCK TABLES;');
        $em->refresh($entity);
    }
}
