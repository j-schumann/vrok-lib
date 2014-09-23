<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Entity;

use Vrok\Doctrine\EntityRepository;

/**
 * Table management class for all users.
 */
class UserRepository extends EntityRepository
{
    use \Vrok\Doctrine\Traits\GetById;

    /**
     * {@inheritDoc}
     */
    public function getFormElementDefinition($fieldName)
    {
        $spec = parent::getFormElementDefinition($fieldName);

        switch ($fieldName) {
            case 'id':
                $spec['type'] = 'Zend\Form\Element\Hidden';
                break;

            case 'email':
                $spec['type'] = 'Zend\Form\Element\Email';
                break;

            case 'password':
                $spec['type'] = 'Zend\Form\Element\Password';
                break;
        }

        return $spec;
    }

    /**
     * {@inheritDoc}
     */
    public function getInputSpecification($fieldName)
    {
        $spec = parent::getInputSpecification($fieldName);

        switch ($fieldName) {
            case 'createdAt':
                // the field is filled automatically, we keep the column
                // definition NOT NULL but allow empty values in forms/filters
                $spec['required'] = false;
                $spec['allowEmpty'] = true;
                unset($spec['validators']['notEmpty']);
                break;

            case 'email':
                $spec['validators']['email'] =
                    $this->getFormHelper()->getEmailValidatorSpecification();
                $spec['validators']['stringLength']['options']['messages'] =
                    array(\Zend\Validator\StringLength::TOO_LONG =>
                        $this->getTranslationString('name').'.tooLong',);
                $spec['validators']['uniqueObject'] = array(
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => array(
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'email',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => array(
                            \DoctrineModule\Validator\UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('email').'.notUnique',
                        ),
                    ),
                );
                break;

            case 'username':
                // @todo validate [a-z@\.]
                break;
        }

        return $spec;
    }

    /**
     * Allows to search for users by group name(s).
     * Proxies to the UserFilter, implemented here to be used in a ObjectSelect element
     * which requires the functions to be implemented in the repository.
     * We can not use findBy(groups => $name) because this will return:
     * "You cannot search for the association field 'Vrok\Entity\User#groups', because it
     * is the inverse side of an association."
     *
     * @param string|array $group
     * @return User[]
     */
    public function findByGroup($group)
    {
        $qb = $this->createQueryBuilder('u');
        $filter = new Filter\UserFilter($qb);

        if (is_array($group)) {
            $filter->byGroupNames($group);
        }
        else {
            $filter->byGroupName($group);
        }

        return $filter->getResult();
    }
}
