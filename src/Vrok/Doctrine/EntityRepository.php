<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use Doctrine\ORM\EntityRepository as DoctrineRepository;
use Vrok\Doctrine\FormHelper;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Extends the basic repository to support easy form generation and translation.
 */
class EntityRepository extends DoctrineRepository implements InputFilterProviderInterface
{
    /**
     * FormHelper instance for this table.
     *
     * @var FormHelper
     */
    protected $formHelper = null;

    /**
     * Retrieve the number of all entities within this repository
     *
     * @return int
     */
    public function count()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select($qb->expr()->count('e'))
           ->from($this->getClassName(), 'e');

        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * Finds entities that do not match a set of criteria.
     *
     * @author http://stackoverflow.com/users/710693/jcm
     * @link http://stackoverflow.com/questions/14085946/doctrine-findby-does-not-equal
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     * @return array The objects.
     */
    public function findByNot(array $criteria, array $orderBy = null,
            $limit = null, $offset = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $expr = $this->getEntityManager()->getExpressionBuilder();

        $qb->select('entity')->from($this->getEntityName(), 'entity');

        foreach ($criteria as $field => $value) {
            $qb->andWhere($expr->neq('entity.' . $field, $value));
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $qb->addOrderBy('entity.' . $field, $order);
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
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return $this->getClassMetadata()->hasField($fieldName);
    }

    /**
     * Uses the FormHelper to build a form element specification to use with the
     * form factory.
     * Overwrite in the child class to add custom attributes or change the
     * element type.
     *
     * @param string $fieldName
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
        $spec = array();
        foreach($this->_class->getFieldNames() as $fieldName) {
            $spec[$fieldName] = $this->getInputSpecification($fieldName);
        }
        foreach($this->_class->getAssociationNames() as $associationName) {
            $spec[$associationName] = $this->getInputSpecification($associationName);
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
        if (!$this->formHelper) {
            $this->formHelper = new \Vrok\Doctrine\FormHelper($this->_class,
                    $this->getEntityManager());
        }
        return $this->formHelper;
    }

    /**
     * Helper function to return a unified string to use as translation identifer.
     *
     * @param string $fieldName (optional) the fieldname to append to the string
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
     * @param array $formData
     * @return Entity
     */
    public function updateInstance(Entity $instance, array $formData)
    {
        $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                $this->getEntityManager());
        $object = $hydrator->hydrate($formData, $instance);

        $this->getEntityManager()->persist($object);

        // in rare cases when we create a new instance and set the same
        // identifiers as an already existing record uses. The hdydrator will
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
     * @param \Vrok\Doctrine\Entity $instance
     * @return array
     */
    public function getInstanceData(Entity $instance)
    {
        $hydrator = new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                $this->getEntityManager());
        return $hydrator->extract($instance);
    }
}
