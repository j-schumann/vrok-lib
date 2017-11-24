<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\Element;

use Traversable;
use Zend\Form\FormInterface;
use Zend\Form\Element\Text;
use Zend\Form\ElementPrepareAwareInterface;

// @todo used? complete?
class MultiText extends \Zend\Form\Element\Text implements \Vrok\Form\ElementInterface, ElementPrepareAwareInterface
{
    /**
     * The number of inputs to render.
     *
     * @var int
     */
    protected $inputCount = 3;

    /**
     * maxlength to set on the inputs.
     *
     * @var int
     */
    protected $maxlength = 100;

    /**
     * Holds the sub-elements this element is composed of.
     *
     * @var array
     */
    protected $elements = [];

    /**
     * Create the required sub-elements.
     *
     * @param null|int|string $name    Optional name for the element
     * @param array           $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        for ($i = 0; $i < $this->inputCount; ++$i) {
            $this->createText();
        }
    }

    /**
     * Appends a new text input to the current elements list.
     */
    protected function createText()
    {
        $number = count($this->elements) + 1;
        $text   = new Text("input$number");
        $text->setAttribute('maxlength', $this->maxlength);
        $this->elements[] = $text;
    }

    /**
     * Returns the text input with the given number.
     *
     * @return Text
     */
    public function getInput($number)
    {
        if ($number > $this->inputCount) {
            throw new OutOfBoundsException(
                'Only '.$this->inputCount
                .' elements configured, cannot return #'.(int) $number
            );
        }

        return $this->elements[$number - 1];
    }

    /**
     * Sets the options (e.g. "count").
     *
     * @param array|Traversable $options
     *
     * @return self
     *
     * @throws \Zend\Form\Exception\InvalidArgumentException
     */
    public function setOptions($options)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (! is_array($options)) {
            throw new \Zend\Form\Exception\InvalidArgumentException(
                'The options parameter must be an array or a Traversable'
            );
        }

        if (isset($options['count'])) {
            $this->setInputCount($options['count']);
        }
        if (isset($options['maxlength'])) {
            $this->setMaxlength($options['maxlength']);
        }

        return parent::setOptions($options);
    }

    /**
     * Returns all Text inputs.
     *
     * @return array
     */
    public function getInputs()
    {
        return $this->elements;
    }

    /**
     * Returns the number of inputs to render.
     *
     * @return int
     */
    public function getInputCount()
    {
        return $this->inputCount;
    }

    /**
     * Sets the number of inputs to render.
     *
     * @param int $count
     */
    public function setInputCount($count)
    {
        $this->inputCount = $count;
        $elementCount     = count($this->elements);
        if ($this->inputCount < $elementCount) {
            for ($i = $this->inputCount; $i < $elementCount; ++$i) {
                unset($this->elements[$i]);
            }
        } elseif ($this->inputCount > $elementCount) {
            for ($i = 0; $i < $this->inputCount - $elementCount; ++$i) {
                $this->createText();
            }
        }
    }

    /**
     * Returns the maximum input length to set for the inputs.
     *
     * @return int
     */
    public function getMaxlength()
    {
        return $this->maxlength;
    }

    /**
     * (Re-)sets the maxlength to set on the inputs.
     *
     * @param int $maxlength
     */
    public function setMaxlength($maxlength = 100)
    {
        $this->maxlength = $maxlength;
        foreach ($this->elements as $element) {
            $element->setAttribute('maxlength', $this->maxlength);
        }
    }

    /**
     * Populates the sub-elements with the given value.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function setValue($value)
    {
        // reset
        foreach ($this->elements as $element) {
            $element->setValue(null);
        }

        // ignore other data types
        if (is_array($value)) {
            $count = 1;
            foreach ($value as $entry) {
                $this->getInput($count)->setValue($entry);

                ++$count;

                // ignore additional entries
                if ($count >= $this->inputCount) {
                    break;
                }
            }
        }

        return $this;
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
        for ($i = 1; $i <= $this->inputCount; ++$i) {
            $this->getInput($i)->setName($name."[input$i]");
        }
    }

    /**
     * Clone the element (this is needed by Collection element, as it needs
     * different copies of the elements).
     */
    public function __clone()
    {
        $elements = [];
        foreach ($this->elements as $element) {
            $elements = clone $element;
        }
        $this->elements = $elements;
    }

    /**
     * Render this element with a custom helper.
     *
     * @return string
     */
    public function suggestViewHelper()
    {
        return 'form_multitext';
    }
}
