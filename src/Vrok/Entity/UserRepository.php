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
                break;
        }

        return $spec;
    }
}