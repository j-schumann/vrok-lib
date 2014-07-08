<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\SlmQueue;

use Vrok\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

/**
 * Form to enter the max execution time to recover jobs in the queue.
 */
class RecoverForm extends Form implements InputFilterProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->addCsrfElement('csrfRecover');

        $this->add(array(
            'type'       => 'Zend\Form\Element\Text',
            'name'       => 'executionTime',
            'options'    => array(
                'label' => 'Maximum execution time in minutes:'
            ),
            'attributes' => array(
                'maxlength' => 3,
                'value'     => 30,
            ),
        ));

        $this->add(array(
            'name'       => 'confirm',
            'attributes' => array(
                'type'    => 'submit',
                'value'   => 'Submit',
            )
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getInputFilterSpecification()
    {
        return array(
            'executionTime' => array(
                'required'   => true,
                'allowEmpty' => false,
                'filters'    => array(
                    array('name' => 'Zend\Filter\StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'Zend\Validator\Digits',
                        'options' => array(
                            'messages' => array(
                                \Zend\Validator\Digits::NOT_DIGITS =>
                                    \Vrok\Doctrine\FormHelper::ERROR_NOTINT,
                            ),
                        ),
                    ),
                    array(
                        'name'    => 'Zend\Validator\StringLength',
                        'options' => array(
                            'max'      => 3,
                            'messages' => array(
                                \Zend\Validator\StringLength::TOO_LONG =>
                                    \Vrok\Doctrine\FormHelper::ERROR_TOOLONG,
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}
