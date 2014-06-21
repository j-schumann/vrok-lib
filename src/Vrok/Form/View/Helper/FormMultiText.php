<?php

namespace Vrok\Form\View\Helper;

use Vrok\Form\Element\MultiText;
use Zend\Form\ElementInterface;
use Zend\Form\Exception;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\View\Helper\FormText;

// @todo used? necessary?
class FormMultiText extends AbstractHelper
{
    /**
     * FormText helper
     *
     * @var FormText
     */
    protected $textHelper;

    /**
     * Render an element that is composed of multiple elements.
     *
     * @param  ElementInterface $element
     * @throws \Zend\Form\Exception\InvalidArgumentException
     * @throws \Zend\Form\Exception\DomainException
     * @return string
     */
    public function render(ElementInterface $element)
    {
        if (!$element instanceof MultiText) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the element is of type Vrok\Form\Element\MultiText',
                __METHOD__
            ));
        }

        $name = $element->getName();
        if ($name === null || $name === '') {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has an assigned name; none discovered',
                __METHOD__
            ));
        }

        $textHelper  = $this->getTextElementHelper();

        $markup = '';
        $subElements = $element->getInputs();
        foreach($subElements as $subElement) {
            if (!$subElement->getAttribute('id')) {
                $id = str_replace(
                    array('[', ']'),
                    array('-', ''),
                    $subElement->getName()
                );
                $subElement->setAttribute('id', $id);
            }

            $markup .= $textHelper->render($subElement);
        }

        return $markup;
    }

    /**
     * Retrieve the FormText helper
     *
     * @return FormText
     */
    protected function getTextElementHelper()
    {
        if ($this->textHelper) {
            return $this->textHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->textHelper = $this->view->plugin('formtext');
        }
        else {
            $this->textHelper = new FormText();
        }

        return $this->textHelper;
    }
}
