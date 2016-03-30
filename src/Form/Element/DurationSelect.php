<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\Element;

use Vrok\Form\ElementInterface;
use Zend\Form\ElementPrepareAwareInterface;
use Zend\Form\Element\Select;
use Zend\Form\FormInterface;
use Zend\Form\Exception\InvalidArgumentException;
use Zend\InputFilter\InputProviderInterface;

/**
 * Shows two dropdown elements to select a number of hours and a number of minutes
 * as a duration/interval.
 * Allows to configure steps and min/max values for the minutes/hours.
 */
class DurationSelect extends Select implements
    ElementInterface,
    ElementPrepareAwareInterface,
    InputProviderInterface
{
    /**
     * If set to true, it will generate an empty option for every select (this is mainly needed by most JavaScript
     * libraries to allow to have a placeholder).
     *
     * @var bool
     */
    protected $createEmptyOption = false;

    /**
     * Select form element that contains values for hour.
     *
     * @var Select
     */
    protected $hourElement;

    /**
     * Select form element that contains values for minute.
     *
     * @var Select
     */
    protected $minuteElement;

    protected $minHours  = 0;
    protected $maxHours  = 24;
    protected $stepHours = 1;
    protected $showHours = true;

    protected $minMinutes  = 0;
    protected $maxMinutes  = 59;
    protected $stepMinutes = 1;
    protected $showMinutes = true;

    /**
     * Constructor. Add the hours and minutes select elements.
     *
     * @param null|int|string $name    Optional name for the element
     * @param array           $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->hourElement = new Select('hours');
        $this->hourElement->setAttribute('class', 'durationselect-hours');
        $this->minuteElement = new Select('minutes');
        $this->minuteElement->setAttribute('class', 'durationselect-minutes');
    }

    /**
     * Render this element with a custom helper.
     *
     * @return string
     */
    public function suggestViewHelper()
    {
        return 'form_durationselect';
    }

    /**
     * Accepted options for DurationSelect:
     * - hour_attributes: HTML attributes to be rendered with the hour element
     * - minute_attributes: HTML attributes to be rendered with the minute element.
     *
     * @param array|\Traversable $options
     *
     * @return self
     */
    public function setOptions($options)
    {
        parent::setOptions($options);

        if (isset($options['hour_attributes'])) {
            $this->setHourAttributes($options['hour_attributes']);
        }

        if (isset($options['minute_attributes'])) {
            $this->setMinuteAttributes($options['minute_attributes']);
        }

        if (isset($options['create_empty_option'])) {
            $this->setShouldCreateEmptyOption($options['create_empty_option']);
        }

        if (isset($options['minHours'])) {
            $this->setMinHours($options['minHours']);
        }
        if (isset($options['maxHours'])) {
            $this->setMaxHours($options['maxHours']);
        }
        if (isset($options['stepHours'])) {
            $this->setStepHours($options['stepHours']);
        }
        if (isset($options['showHours'])) {
            $this->setShowHours($options['showHours']);
        }

        if (isset($options['minMinutes'])) {
            $this->setMinMinutes($options['minMinutes']);
        }
        if (isset($options['maxMinutes'])) {
            $this->setMaxMinutes($options['maxMinutes']);
        }
        if (isset($options['stepMinutes'])) {
            $this->setStepMinutes($options['stepMinutes']);
        }
        if (isset($options['showMinutes'])) {
            $this->setShowMinutes($options['showMinutes']);
        }

        return $this;
    }

    /**
     * @param bool $createEmptyOption
     *
     * @return MonthSelect
     */
    public function setShouldCreateEmptyOption($createEmptyOption)
    {
        $this->createEmptyOption = (bool) $createEmptyOption;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldCreateEmptyOption()
    {
        return $this->createEmptyOption;
    }

    /**
     * @return Select
     */
    public function getHourElement()
    {
        return $this->hourElement;
    }

    /**
     * @return Select
     */
    public function getMinuteElement()
    {
        return $this->minuteElement;
    }

    /**
     * @return Select
     */
    public function getSecondElement()
    {
        return $this->secondElement;
    }

    /**
     * Set the hour attributes.
     *
     * @param array $hourAttributes
     *
     * @return DateSelect
     */
    public function setHourAttributes(array $hourAttributes)
    {
        $this->hourElement->setAttributes($hourAttributes);

        return $this;
    }

    /**
     * Get the hour attributes.
     *
     * @return array
     */
    public function getHourAttributes()
    {
        return $this->hourElement->getAttributes();
    }

    /**
     * Set the minute attributes.
     *
     * @param array $minuteAttributes
     *
     * @return DateSelect
     */
    public function setMinuteAttributes(array $minuteAttributes)
    {
        $this->minuteElement->setAttributes($minuteAttributes);

        return $this;
    }

    /**
     * Get the minute attributes.
     *
     * @return array
     */
    public function getMinuteAttributes()
    {
        return $this->minuteElement->getAttributes();
    }

    /**
     * Sets the minimum hours.
     *
     * @param int $value
     */
    public function setMinHours($value)
    {
        $this->minHours = $value;
    }

    /**
     * Sets the maximum hours.
     *
     * @param int $value
     */
    public function setMaxHours($value)
    {
        $this->maxHours = $value;
    }

    /**
     * Sets the hour steps.
     *
     * @param int $value
     */
    public function setStepHours($value)
    {
        $this->stepHours = $value;
    }

    /**
     * Sets whether to show the hour select or not.
     *
     * @param bool $value
     */
    public function setShowHours($value)
    {
        $this->showHours = $value;
    }

    /**
     * Sets the minimum minutes.
     *
     * @param int $value
     */
    public function setMinMinutes($value)
    {
        $this->minMinutes = $value;
    }

    /**
     * Sets the maximum minutes.
     *
     * @param int $value
     */
    public function setMaxMinutes($value)
    {
        $this->maxMinutes = $value;
    }

    /**
     * Sets the minutes steps.
     *
     * @param int $value
     */
    public function setStepMinutes($value)
    {
        $this->stepMinutes = $value;
    }

    /**
     * Sets whether to show the minute select or not.
     *
     * @param bool $value
     */
    public function setShowMinutes($value)
    {
        $this->showMinutes = $value;
    }

    /**
     * Returns true if the hour select should be displayed, else false.
     *
     * @return bool
     */
    public function getShowHours()
    {
        return $this->showHours;
    }

    /**
     * Returns true if the minute select should be displayed, else false.
     *
     * @return bool
     */
    public function getShowMinutes()
    {
        return $this->showMinutes;
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
        if (is_array($value)) {
            if (!isset($value['hours'])) {
                $value['hours'] = isset($value[0]) ? $value[0] : 0;
            }
            if (!isset($value['minutes'])) {
                $value['minutes'] = isset($value[1]) ? $value[1] : 0;
            }
        } elseif (is_null($value)) {
            $value = ['hours' => 0, 'minutes' => 0];
        } elseif (is_numeric($value)) {
            // the values is given as a number of hours
            $value = [
                'hours'   => floor($value),
                'minutes' => ($value - floor($value)) * 60,
            ];
        } else {
            throw new InvalidArgumentException(
                    $value.' is a invalid duration!');
        }

        $this->hourElement->setValue($value['hours']);
        $this->minuteElement->setValue($value['minutes']);
    }

    /**
     * Retrieve the selected values.
     *
     * @return array
     */
    public function getValue()
    {
        return [
            'hours'   => $this->hourElement->getValue(),
            'minutes' => $this->minuteElement->getValue(),
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
        $this->hourElement->setName($name.'[hours]');
        $this->minuteElement->setName($name.'[minutes]');
    }

    /**
     * Retrieve the allowed options to select from.
     *
     * @return array
     */
    public function getHourValueOptions()
    {
        $options = [];
        for ($i = $this->minHours; $i <= $this->maxHours; $i += $this->stepHours) {
            $options[$i] = $i;
        }

        return $options;
    }

    /**
     * Retrieve the allowed options to select from.
     *
     * @return array
     */
    public function getMinuteValueOptions()
    {
        $options = [];
        for ($i = $this->minMinutes; $i <= $this->maxMinutes; $i += $this->stepMinutes) {
            $options[$i] = $i;
        }

        return $options;
    }

    /**
     * Should return an array specification compatible with
     * {@link Zend\InputFilter\Factory::createInput()}.
     *
     * @return array
     */
    public function getInputSpecification()
    {
        return [
            'name'       => $this->getName(),
            'required'   => false,
            'validators' => [
                'callback' => [
                    'name'                   => 'Zend\Validator\Callback',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            \Zend\Validator\Callback::INVALID_VALUE => 'validate.field.invalidDuration',
                        ],
                        'callback' => [$this, 'validate'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Validate the value using the options set.
     *
     * @param array $value
     *
     * @return bool
     */
    public function validate($value)
    {
        if ($this->showHours
            && (!isset($value['hours'])
            || !in_array((int) $value['hours'], $this->getHourValueOptions()))
        ) {
            return false;
        }

        if ($this->showMinutes
            && (!isset($value['minutes'])
            || !in_array((int) $value['minutes'], $this->getMinuteValueOptions()))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Clone the element (this is needed by Collection element, as it needs different copies of the elements).
     */
    public function __clone()
    {
        $this->hourElement   = clone $this->hourElement;
        $this->minuteElement = clone $this->minuteElement;
    }
}
