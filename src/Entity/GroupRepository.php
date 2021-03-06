<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use DoctrineModule\Validator\UniqueObject;
use Vrok\Doctrine\EntityRepository;

/**
 * Table management class for all groups.
 */
class GroupRepository extends EntityRepository
{
    /**
     * {@inheritDoc}
     */
    public function getFormElementDefinition($fieldName)
    {
        $definition = parent::getFormElementDefinition($fieldName);
        switch ($fieldName) {
            case 'id':
                $definition['type'] = 'hidden';
                break;

            case 'name':
                $definition['options']['description'] =
                    $this->getTranslationString('name').'.description';
                break;

            case 'parent':
                $definition['options']['find_method'] = [
                    'name'   => 'getPotentialParents',
                    'params' => [
                        'groupId' => 0,
                    ],
                ];

                // @todo - validator to prevent setting a parent which in turn
                // has the current element as parent/grandparent/etc
                break;
        }

        return $definition;
    }

    /**
     * Get a list of groups that can be set as parents for the group given
     * by its ID.
     * Does no deep-check for circular references!
     *
     * @param int $groupId
     *
     * @return Collection
     */
    public function getPotentialParents($groupId)
    {
        $em    = $this->getEntityManager();
        $query = $em->createQuery('SELECT g FROM Vrok\Entity\Group g'
            .' WHERE g.id <> :id AND (g.parent <> :parent OR g.parent IS NULL)'
            .' ORDER BY g.name ASC');
        $query->setParameters([
            'id'     => (int) $groupId,
            'parent' => (int) $groupId,
        ]);

        return $query->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getInputSpecification($fieldName)
    {
        $spec = parent::getInputSpecification($fieldName);

        switch ($fieldName) {
            case 'name':
                $spec['validators']['stringLength']['options']['messages'] =
                    [\Zend\Validator\StringLength::TOO_LONG => $this->getTranslationString('name').'.tooLong'];

                $spec['validators']['uniqueObject'] = [
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => [
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'name',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => [
                            UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('name').'.notUnique',
                        ],
                    ],
                ];
                break;
        }

        return $spec;
    }
}
