<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\Element;

use DateInterval;
use Vrok\Form\ElementInterface;
use Vrok\Stdlib\DateInterval as VrokInterval;
use Zend\Form\ElementPrepareAwareInterface;
use Zend\Form\Element;
use Zend\Form\Element\Select;
use Zend\Form\Element\Text;
use Zend\Form\FormInterface;
use Zend\Form\Exception\InvalidArgumentException;

/**
 * Shows an input to enter the interval length and a dropdown to select from
 * seconds/minutes/hours/days.
 */
class Interval extends Element implements
    ElementPrepareAwareInterface,
    ElementInterface
{
    /**
     * @var array
     */
    public static $typeValueOptions = [
        'S' => 'seconds',
        'M' => 'minutes',
        'H' => 'hours',
        'D' => 'days',
    ];

    /**
     * Contains the options to show in the select.
     *
     * @var array
     */
    protected $typeOptions = ['S', 'M', 'H', 'D'];

    /**
     * input element that contains the integer part (interval length).
     *
     * @var Text
     */
    protected $amountElement;

    /**
     * Select element that contains the interval type.
     *
     * @var Select
     */
    protected $typeElement;

    /**
     * Constructor. Adds the elements.
     *
     * @param null|int|string $name    Optional name for the element
     * @param array           $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->amountElement = new Text('amount');
        $this->amountElement->setAttribute('class', 'form-control interval-amount');

        $this->typeElement = new Select('intervaltype');
        $this->typeElement->setAttribute('class', 'form-control interval-type');
    }

    /**
     * Render this element with a custom helper.
     *
     * @return string
     */
    public function suggestViewHelper()
    {
        return 'formInterval';
    }

    /**
     * Accepted options for DurationSelect:
     * - amount_attributes: HTML attributes to be rendered with the amout element
     * - type_attributes: HTML attributes to be rendered with the type element.
     *
     * @param array|\Traversable $options
     *
     * @return self
     */
    public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($options['amount_attributes'])) {
            $this->setAmountAttributes($options['amount_attributes']);
        }

        if (isset($options['type_attributes'])) {
            $this->setTypeAttributes($options['type_attributes']);
        }

        if (isset($options['type_options'])) {
            $this->setTypeOptions($options['type_options']);
        }

        return $this;
    }

    /**
     * @return Text
     */
    public function getAmountElement()
    {
        return $this->amountElement;
    }

    /**
     * @return Select
     */
    public function getTypeElement()
    {
        return $this->typeElement;
    }

    /**
     * Set the amount attributes.
     *
     * @param array $attributes
     *
     * @return DateSelect
     */
    public function setAmountAttributes(array $attributes)
    {
        $this->amountElement->setAttributes($attributes);

        return $this;
    }

    /**
     * Get the amount attributes.
     *
     * @return array
     */
    public function getAmountAttributes()
    {
        return $this->amountElement->getAttributes();
    }

    /**
     * Set the type attributes.
     *
     * @param array $attributes
     *
     * @return DateSelect
     */
    public function setTypeAttributes(array $attributes)
    {
        $this->typeElement->setAttributes($attributes);

        return $this;
    }

    /**
     * Get the type attributes.
     *
     * @return array
     */
    public function getTypeAttributes()
    {
        return $this->typeElement->getAttributes();
    }

    /**
     * @param mixed $value
     *
     * @throws \Zend\Form\Exception\InvalidArgumentException
     *
     * @return void|\Zend\Form\Element
     */
    public function setValue($value)
    {
        if ($value instanceof DateInterval) {
            $interval = VrokInterval::convert($value);
            $value    = [];

            if (is_int($interval->getDays())) {
                $value['amount']       = $interval->getDays();
                $value['intervaltype'] = 'D';
            } elseif (is_int($interval->getHours())) {
                $value['amount']       = $interval->getHours();
                $value['intervaltype'] = 'H';
            } elseif (is_int($interval->getMinutes())) {
                $value['amount']       = $interval->getMinutes();
                $value['intervaltype'] = 'M';
            } else {
                $value['amount']       = $interval->asSeconds();
                $value['intervaltype'] = 'S';
            }
        }
        if (is_array($value)) {
            if (!isset($value['amount'])) {
                $value['amount'] = isset($value[0]) ? $value[0] : '';
            }
            if (!isset($value['intervaltype'])) {
                $value['intervaltype'] = isset($value[1]) ? $value[1] : '';
            }
        } elseif (is_null($value)) {
            $value = [
                'amount'       => '',
                'intervaltype' => '',
            ];
        } elseif (is_numeric($value)) {
            $value = [
                'amount'       => floor($value),
                'intervaltype' => '',
            ];
        } else {
            throw new InvalidArgumentException(
                    $value.' is an invalid interval!');
        }

        $this->amountElement->setValue($value['amount']);
        $this->typeElement->setValue($value['intervaltype']);
    }

    /**
     * Retrieve the selected values.
     *
     * @return array
     */
    public function getValue()
    {
        return [
            'amount'       => $this->amountElement->getValue(),
            'intervaltype' => $this->typeElement->getValue(),
        ];
    }

    /**
     * Prepare the form element (mostly used for rendering purposes).
     *
     * @param FormInterface $form
     *
     * @return mixed
     */
    public function prepareElement(FormInterface $form)
    {
        $name = $this->getName();
        $this->amountElement->setName($name.'[amount]');
        $this->typeElement->setName($name.'[intervaltype]');
    }

    /**
     * Sets the allowed options to select from.
     *
     * @param $options array ['S', 'M', 'H', 'D'] or a combination of these
     *
     * @return array
     */
    public function setTypeOptions(array $options)
    {
        $this->typeOptions = $options;
    }

    /**
     * Retrieve the allowed options to select from.
     *
     * @return array
     */
    public function getTypeValueOptions()
    {
        $result = [];
        foreach ($this->typeOptions as $key) {
            $result[$key] = self::$typeValueOptions[$key];
        }

        return $result;
    }

    /**
     * Clone the element (this is needed by Collection element,
     * as it needs different copies of the elements).
     */
    public function __clone()
    {
        $this->amountElement = clone $this->amountElement;
        $this->typeElement   = clone $this->typeElement;
    }
}
