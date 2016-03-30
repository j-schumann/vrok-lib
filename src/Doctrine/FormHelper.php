<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\EntityManager;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Provides function to generate form elements and inputFilters for the fields
 * of a Doctrine entity.
 */
class FormHelper implements InputFilterProviderInterface
{
    const ERROR_ISEMPTY      = 'validate.field.isEmpty';
    const ERROR_NOTFLOAT     = 'validate.field.notFloat';
    const ERROR_NOTINT       = 'validate.field.notInt';
    const ERROR_TOOLONG      = 'validate.field.tooLong';
    const ERROR_INVALIDDATE  = 'validate.field.invalidDate';
    const ERROR_INVALIDEMAIL = 'validate.field.invalidEmail';

    /**
     * ORM metadata descriptor for a entity class.
     *
     * @var ClassMetadataInfo
     */
    protected $metadata = null;

    /**
     * ObjectManager instance used to get information about associations.
     *
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * Class constructor - stores the given metadata & manager.
     *
     * @param ClassMetadataInfo $metadata
     * @param EntityManager     $entityManager
     */
    public function __construct(ClassMetadataInfo $metadata, EntityManager $entityManager)
    {
        $this->metadata      = $metadata;
        $this->entityManager = $entityManager;
    }

    /**
     * Returns the complete element definition to use for the factory-backed
     * form element creation.
     *
     * @param string $fieldName
     *
     * @return array
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getElementDefinition($fieldName)
    {
        // association mappings are no standard fields, we use the
        // \DoctrineModule\Form\Element\ObjectSelect for them
        if ($this->metadata->hasAssociation($fieldName)) {
            return $this->getAssociationElement($fieldName);
        }

        if (!$this->metadata->hasField($fieldName)) {
            throw \Doctrine\ORM\ORMException::unrecognizedField($fieldName);
        }

        $mapping = $this->metadata->getFieldMapping($fieldName);

        $definition = [
            'type'       => $this->getElementType($mapping),
            'name'       => $fieldName,
            'attributes' => $this->getAttributes($mapping),
            'options'    => [
                'label' => $this->getLabel($fieldName),
            ],
        ];

        switch ($mapping['type']) {
            case 'date':
                $definition['options']['render_delimiters'] = false;
                $definition['options']['day_attributes']    = [
                    'class' => 'dateselect-day',
                ];
                $definition['options']['month_attributes'] = [
                    'class' => 'dateselect-month',
                ];
                $definition['options']['year_attributes'] = [
                    'class' => 'dateselect-year',
                ];
                if (!$this->elementIsRequired($mapping)) {
                    $definition['options']['create_empty_option'] = true;
                }
                break;
        }

        return $definition;
    }

    /**
     * Returns the complete element definition to use for the factory-backed
     * form element creation.
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getAssociationElement($fieldName)
    {
        $association = $this->metadata->associationMappings[$fieldName];
        $target      = $this->entityManager->getClassMetadata($association['targetEntity']);

        $property = null;
        // @todo warum 'name', wo kommt das her? warum ist das unique?
        if ($target->hasField('name')) {
            $property = 'name';
        } elseif ($target->hasField('id')) {
            // @todo is the ID always unique?
            $property = 'id';
        } else {
            // @todo does not work with composite keys
            $identifiers = $target->getIdentifierColumnNames();
            $property    = $identifiers[0];
        }

        $definition = [
            'type'    => 'Vrok\Form\Element\ObjectSelect',
            'name'    => $fieldName,
            'options' => [
                'object_manager' => $this->entityManager,
                'target_class'   => $association['targetEntity'],
                'property'       => $property,
                'label'          => $this->getLabel($fieldName),

                // display the empty element even if the relation is required
                // to force the user to select one and not only use the first
                // one that is automatically selected
                'display_empty_item' => true,
            ],
            'attributes' => [
                'multiple' => $this->associationIsMultiple($association),
            ],
        ];

        if ($this->associationIsRequired($association)) {
            $definition['attributes']['required'] = 'required';
        }

        return $definition;
    }

    /**
     * Checks if the association allows multiple elements.
     *
     * @param array $association
     *
     * @return bool
     */
    protected function associationIsMultiple($association)
    {
        $multiple = [
            \Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_MANY,
            \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY,
        ];

        return in_array($association['type'], $multiple);
    }

    /**
     * Checks if the relation defined by the given association is required.
     *
     * @param array $association
     *
     * @return bool
     */
    protected function associationIsRequired($association)
    {
        if ($association['type'] == \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE) {
            // @todo does MANY_TO_ONE always have joinColumns?
            foreach ($association['joinColumns'] as $joinColumn) {
                if ($joinColumn['nullable'] === true) {
                    return false;
                }
            }

            return true;
        }

        // @todo handling for other types
        return false;
    }

    /**
     * Returns the form element class to use for the given field.
     *
     * @param array $mapping
     *
     * @return string
     */
    public function getElementType(array $mapping)
    {
        switch ($mapping['type']) {
            case 'boolean':
                return 'Zend\Form\Element\Checkbox';

            case 'date':
                return 'Zend\Form\Element\DateSelect';

            // @todo datetime element

            case 'text':
                return 'Zend\Form\Element\Textarea';

            case 'string':
                return $this->getLength($mapping) > 255
                    ? 'Zend\Form\Element\Textarea'
                    : 'Zend\Form\Element\Text';

            case 'integer':
                //no break
            default:
                return 'Zend\Form\Element\Text';
        }
    }

    /**
     * Returns the elements unified translatable label.
     *
     * @param string $fieldName
     *
     * @return string
     */
    protected function getLabel($fieldName)
    {
        return Common::getEntityTranslationString(
                $this->metadata->getName(), $fieldName).'.label';
    }

    /**
     * Returns a list of all attributes to set on the form input.
     *
     * @param array $mapping
     *
     * @return array
     */
    protected function getAttributes(array $mapping)
    {
        $attributes = [];
        if ($this->elementIsRequired($mapping)) {
            $attributes['required'] = 'required';
        }

        if (isset($mapping['options']['default'])) {
            $attributes['value'] = $mapping['options']['default'];
        }

        switch ($mapping['type']) {
            case 'date':
                $attributes['type'] = 'dateselect';
                break;

            case 'string':
                // no break
            case 'text':
                $attributes['maxlength'] = $this->getLength($mapping);
                break;
        }

        return $attributes;
    }

    /**
     * Returns the max length to use for the input field & validation.
     *
     * @param array $mapping
     *
     * @return int
     */
    protected function getLength(array $mapping)
    {
        return isset($mapping['length'])
            ? $mapping['length']
            : ($mapping['type'] === 'text' ? 65535 : 255);
    }

    /**
     * Returns a InputFilter specification for all fields to use with the
     * InputFilter\Factory.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        $spec = [];
        foreach ($this->metadata->getFieldNames() as $fieldName) {
            $spec[$fieldName] = $this->getInputSpecification($fieldName);
        }

        return $spec;
    }

    /**
     * Returns a Input specification for the given field to use with the
     * InputFilter\Factory (@link \Zend\InputFilter\InputProviderInterface).
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getInputSpecification($fieldName)
    {
        if ($this->metadata->hasAssociation($fieldName)) {
            return $this->getAssociationSpecification($fieldName);
        }

        $mapping = $this->metadata->getFieldMapping($fieldName);

        return [
            'name'       => $fieldName,
            'required'   => $this->elementIsRequired($mapping),
            'allowEmpty' => $mapping['nullable'],
            'filters'    => $this->getFilters($mapping),
            'validators' => $this->getValidators($mapping),
        ];
    }

    /**
     * Returns a Input specification for the given association to use with the
     * InputFilter\Factory (@link \Zend\InputFilter\InputProviderInterface).
     *
     * @param string $associationName
     *
     * @return array
     */
    public function getAssociationSpecification($associationName)
    {
        $association = $this->metadata->associationMappings[$associationName];
        $required = $this->associationIsRequired($association);

        $spec = [
            'name'       => $associationName,
            'required'   => $required,
            'allowEmpty' => !$required,
            'filters'    => [
                'null' => [
                    'name'    => 'Zend\Filter\ToNull',
                    'options' => [
                        'type' => \Zend\Filter\ToNull::TYPE_STRING,
                    ],
                ],
            ],
            // @todo object exists validator?
        ];

        if ($required) {
            $spec['validators']['notEmpty'] = $this->getNotEmptyValidatorSpecification();
        }

        return $spec;
    }

    /**
     * Returns the default filters to use for the element.
     *
     * @param array $mapping
     *
     * @return array
     */
    public function getFilters(array $mapping)
    {
        $filters = [
            'stringTrim' => [
                'name' => 'Zend\Filter\StringTrim',
            ],
        ];

        if ($mapping['nullable'] === true) {
            $filters['null'] = [
                'name'    => 'Zend\Filter\ToNull',
                'options' => [
                    'type' => \Zend\Filter\ToNull::TYPE_STRING,
                ],
            ];
        }

        switch ($mapping['type']) {
            case 'date':
                $filters['dateSelect'] = [
                    // special filter to return NULL if no subelement is set
                    'name' => 'Vrok\Filter\DateSelect',
                ];
                break;

            case 'decimal':
                $filters['numberParse'] = [
                    'name' => 'Zend\I18n\Filter\NumberParse',
                ];
                break;
        }

        return $filters;
    }

    /**
     * Returns the default validators for the given field.
     *
     * @param array $mapping
     *
     * @return array
     */
    public function getValidators(array $mapping)
    {
        $validators = [];

        if ($this->elementIsRequired($mapping)) {
            $validators['notEmpty'] = $this->getNotEmptyValidatorSpecification();
        }

        switch ($mapping['type']) {
            case 'date':
                $validators['date'] = [
                    'name'                   => 'Zend\Validator\Date',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            \Zend\Validator\Date::FALSEFORMAT  => self::ERROR_INVALIDDATE,
                            \Zend\Validator\Date::INVALID      => self::ERROR_INVALIDDATE,
                            \Zend\Validator\Date::INVALID_DATE => self::ERROR_INVALIDDATE,
                        ],
                    ],
                ];
                break;

            case 'decimal':
                $validators['float'] = [
                    'name'                   => 'Zend\I18n\Validator\IsFloat',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            \Zend\I18n\Validator\IsFloat::NOT_FLOAT => self::ERROR_NOTFLOAT,
                        ],
                    ],
                ];
                break;

            case 'integer':
                $validators['int'] = [
                    'name'                   => 'Zend\I18n\Validator\IsInt',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            \Zend\I18n\Validator\IsInt::NOT_INT => self::ERROR_NOTINT,
                        ],
                    ],
                ];
                break;

            case 'string':
                // no break
            case 'text':
                $validators['stringLength'] = [
                    'name'                   => 'Zend\Validator\StringLength',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'max'      => $this->getLength($mapping),
                        'messages' => [
                            \Zend\Validator\StringLength::TOO_LONG => self::ERROR_TOOLONG,
                        ],
                    ],
                ];
                break;
        }

        return $validators;
    }

    /**
     * Returns true if the element should be marked as required, else false.
     *
     * @param array $mapping
     *
     * @return bool
     */
    protected function elementIsRequired(array $mapping)
    {
        // if the current field is generated per default (Autoincrement)
        // it is not required
        $isGenerated = $this->metadata->isIdentifier($mapping['fieldName'])
                && $this->metadata->idGenerator->isPostInsertGenerator();
        if ($isGenerated) {
            return false;
        }

        return $mapping['nullable'] === false
                && !isset($mapping['options']['default']);
    }

    /**
     * Returns the default specification for the notEmpty validator.
     *
     * @return array
     */
    public function getNotEmptyValidatorSpecification()
    {
        return [
            'name'                   => 'Zend\Validator\NotEmpty',
            'break_chain_on_failure' => true,
            'options'                => [
                'messages' => [
                    \Zend\Validator\NotEmpty::IS_EMPTY => self::ERROR_ISEMPTY,
                ],
            ],
        ];
    }

    /**
     * Returns the default specification for the email validator.
     *
     * @return array
     */
    public function getEmailValidatorSpecification()
    {
        return [
            'name'                   => 'Zend\Validator\EmailAddress',
            'break_chain_on_failure' => true,
            'options'                => [
                'useDomainCheck' => true,
                'messages'       => [
                    // we don't want all the different technical messages
                    // the user should know what his own email address is.
                    // When setting the same message for each error this message
                    // is only shown once instead of multiple messages
                    \Zend\Validator\EmailAddress::INVALID_FORMAT      => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\EmailAddress::INVALID_LOCAL_PART  => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\EmailAddress::INVALID_HOSTNAME    => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\EmailAddress::INVALID_SEGMENT     => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\EmailAddress::QUOTED_STRING       => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::LOCAL_NAME_NOT_ALLOWED  => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::INVALID_HOSTNAME        => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::INVALID_HOSTNAME_SCHEMA => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::UNDECIPHERABLE_TLD      => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::UNKNOWN_TLD             => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::IP_ADDRESS_NOT_ALLOWED  => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::INVALID_DASH            => self::ERROR_INVALIDEMAIL,
                    \Zend\Validator\Hostname::INVALID_URI             => self::ERROR_INVALIDEMAIL,
                ],
            ],
        ];
    }
}
