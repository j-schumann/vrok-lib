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
 * Table management class for all users.
 */
class UserRepository extends EntityRepository
{
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

            case 'createdAt':
                unset($spec['attributes']['required']);
                $spec['attributes']['disabled'] = 'disabled';
                break;

            case 'email':
                $spec['type'] = 'Zend\Form\Element\Email';
                break;

            case 'emailNotificationsEnabled':
                $spec['options']['description'] =
                    $this->getTranslationString('emailNotificationsEnabled')
                    .'.description';
                break;

            case 'httpNotificationCertCheck':
                $spec['options']['description'] =
                    $this->getTranslationString('httpNotificationCertCheck')
                    .'.description';
                break;

            /*
             * @todo aktuell wird das Auth PW angezeigt, wenn wir hier den
             * Feldtyp auf password setzen kann der Wert aber nicht mehr
             * vorausgefüllt werden und das PW müsste jedes Mal neu eingegeben
             * werden -> wenn leerer Wert gepostet wird aber in der DB einer
             * steht den behalten und alles ist ok, nur wenn da keiner ist aber
             * ein User angegeben wurde einen Fehler ausgeben? Irgendein hidden
             * field einbauen in dem das alte PW mitgeschickt und ggf via JS
             * ausgefüllt wird?
             */
            case 'httpNotificationPw':
                //$spec['type'] = 'Zend\Form\Element\Password';
                //$spec['attributes']['placeholder'] = '********';
                break;

            case 'httpNotificationsEnabled':
                $spec['options']['description'] =
                    $this->getTranslationString('httpNotificationsEnabled')
                    .'.description';
                break;

            case 'lastLogin':
                // no break
            case 'lastSession':
                $spec['attributes']['disabled'] = 'disabled';
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
                // no break
            case 'lastLogin':
                // no break
            case 'lastSession':
                // the field is filled automatically, we keep the column
                // definition NOT NULL but allow empty values in forms/filters
                $spec['required']   = false;
                $spec['allowEmpty'] = true;
                unset($spec['validators']['notEmpty']);
                break;

            case 'displayName':
                $spec['filters']['stripTags'] = [
                    'name' => 'Zend\Filter\StripTags',
                ];

                $spec['validators']['stringLength']['options']['min']      = 3;
                $spec['validators']['stringLength']['options']['messages'] =
                    [
                        \Zend\Validator\StringLength::TOO_LONG  =>
                            $this->getTranslationString('displayName').'.tooLong',
                        \Zend\Validator\StringLength::TOO_SHORT =>
                            $this->getTranslationString('displayName').'.tooShort',
                    ];

                // a displayName may not be in use as email, username or
                // displayName for any other user:
                $spec['validators']['uniqueObject1'] = [
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => [
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'username',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => [
                            UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('displayName').'.notUnique',
                        ],
                    ],
                ];
                $spec['validators']['uniqueObject2'] = [
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => [
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'email',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => [
                            UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('displayName').'.notUnique',
                        ],
                    ],
                ];
                $spec['validators']['uniqueObject3'] = [
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => [
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'displayName',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => [
                            UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('displayName').'.notUnique',
                        ],
                    ],
                ];
                break;

            case 'email':
                $spec['filters']['stringToLower'] = [
                    'name' => 'Zend\Filter\StringToLower',
                ];
                $spec['validators']['email'] =
                    $this->getFormHelper()->getEmailValidatorSpecification();
                $spec['validators']['stringLength']['options']['messages'] =
                    [\Zend\Validator\StringLength::TOO_LONG =>
                        $this->getTranslationString('email').'.tooLong'];
                $spec['validators']['uniqueObject1'] = [
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => [
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'email',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => [
                            UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('email').'.notUnique',
                        ],
                    ],
                ];
                $spec['validators']['uniqueObject2'] = [
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => [
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'username',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => [
                            UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('email').'.notUnique',
                        ],
                    ],
                ];
                break;

            case 'httpNotificationPw':
                //$spec['required']   = true;
                //$spec['allowEmpty'] = false;
                /*$spec['validators']['callback'] = [
                    'name'                   => 'Zend\Validator\Callback',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            \Zend\Validator\Callback::INVALID_VALUE =>
                               $this->getTranslationString('httpNotification').'.incomplete',
                        ],
                        'callback' => function ($value, $context) {
                var_dump(__METHOD__);
                            return !(empty($value)
                                xor empty($context['httpNotificationFalse']));
                        },
                    ],
                ];*/
                break;

            case 'username':
                $spec['filters']['stringToLower'] = [
                    'name' => 'Zend\Filter\StringToLower',
                ];
                /*
                 * @todo diese validierungen gehören in das Registrierungsform
                 * des jeweiligen Projekts dass die Anforderungen festlegt.
                 * Sie dürfen nicht per default aktiv sein da das Feld auch für
                 * den Login verwendet wird und dort die UniqueObj-Validatoren
                 * nicht anschlagen sollten
                $spec['validators']['regex'] = array(
                    'name'    => 'Regex',
                    'options' => array(
                        'pattern'  => '/^[a-z0-9._+-@]*$/',
                        'messages' => array(
                            \Zend\Validator\Regex::NOT_MATCH =>
                                $this->getTranslationString('username').'.invalid',
                        ),
                    ),
                );
                $spec['validators']['uniqueObject1'] = array(
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => array(
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'username',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => array(
                            \DoctrineModule\Validator\UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('username').'.notUnique',
                        ),
                    ),
                );
                $spec['validators']['uniqueObject2'] = array(
                    'name'    => 'DoctrineModule\Validator\UniqueObject',
                    'options' => array(
                        'use_context'       => true,
                        'object_repository' => $this,
                        'fields'            => 'email',
                        'object_manager'    => $this->getEntityManager(),
                        'messages'          => array(
                            \DoctrineModule\Validator\UniqueObject::ERROR_OBJECT_NOT_UNIQUE =>
                                $this->getTranslationString('username').'.notUnique',
                        ),
                    ),
                );*/
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
     * is the inverse side of an association.".
     *
     * @param string|array $group
     *
     * @return User[]
     */
    public function findByGroup($group)
    {
        $qb     = $this->createQueryBuilder('u');
        $filter = new Filter\UserFilter($qb);

        if (is_array($group)) {
            $filter->byGroupNames($group);
        } else {
            $filter->byGroupName($group);
        }

        return $filter->getResult();
    }
}
