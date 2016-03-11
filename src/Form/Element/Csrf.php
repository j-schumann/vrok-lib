<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\Element;

use Zend\Form\Element\Csrf as ZendCsrf;

/**
 * We need to overwrite the DoctrineModule class as it does not allow to configure
 * another proxy class.
 */
class Csrf extends ZendCsrf
{
    /**
     * Override to use translated message by default.
     *
     * @var array
     */
    protected $csrfValidatorOptions = [
        'timeout'          => 600,
        'messageTemplates' => [
            \Zend\Validator\Csrf::NOT_SAME => 'validate.form.csrfInvalid',
        ],
    ];

    /**
     * Override to allow setting only some options and to keep others at default, we don't
     * want to set the messageTemplates again each time.
     *
     * @param array $options
     *
     * @return Csrf
     */
    public function setCsrfValidatorOptions(array $options)
    {
        $this->csrfValidatorOptions = array_merge($this->csrfValidatorOptions, $options);

        return $this;
    }

    /**
     * Override to add custom NotEmpty validator.
     *
     * @return array
     */
    public function getInputSpecification()
    {
        // @todo https://github.com/j-schumann/vrok-lib/issues/2
        // der notEmpty Validator wird ignoriert
        return [
            'name'     => $this->getName(),
            'required' => true,
            'filters'  => [
                ['name' => 'Zend\Filter\StringTrim'],
            ],
            'validators' => [
                // use our custom NotEmpty specification else a default NotEmpty
                // instance would be created which does not use the translated messages
                // and would use a general message not related to the CSRF field.
                [
                    'name'                   => 'Zend\Validator\NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            \Zend\Validator\NotEmpty::IS_EMPTY => 'validate.form.csrfInvalid',
                        ],
                    ],
                ],

                $this->getCsrfValidator(),
            ],
        ];
    }
}
