<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\View\Helper;

use Vrok\Form\Element\Interval;
use Zend\Form\ElementInterface;
use Zend\Form\Exception;
use Zend\Form\View\Helper\AbstractHelper;

/**
 * Renders the Interval form element.
 */
class FormInterval extends AbstractHelper
{
    /**
     * FormSelect helper.
     *
     * @var FormSelect
     */
    protected $selectHelper;

    /**
     * FormText helper.
     *
     * @var FormText
     */
    protected $textHelper;

    /**
     * Invoke helper as function.
     *
     * Proxies to {@link render()}.
     *
     * @param ElementInterface $element
     *
     * @return string
     */
    public function __invoke(ElementInterface $element = null)
    {
        if (!$element) {
            return $this;
        }

        return $this->render($element);
    }

    /**
     * Render the two elements.
     *
     * @param ElementInterface $element
     *
     * @return string
     *
     * @throws \Zend\Form\Exception\InvalidArgumentException
     * @throws \Zend\Form\Exception\DomainException
     */
    public function render(ElementInterface $element)
    {
        if (!$element instanceof Interval) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the element is of type Vrok\Form\Element\Interval',
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

        $selectHelper = $this->getSelectElementHelper();
        $textHelper   = $this->getTextElementHelper();

        $typeOptions = $element->getTypeValueOptions();

        $amountElement = $element->getAmountElement();
        $typeElement   = $element->getTypeElement()->setValueOptions($typeOptions);

        return '<div class="form-interval">'.
                $textHelper->render($amountElement)
                .' '
                .$selectHelper->render($typeElement)
                .'</div>';
    }

    /**
     * Retrieve the FormSelect helper.
     *
     * @return FormSelect
     */
    protected function getSelectElementHelper()
    {
        if ($this->selectHelper) {
            return $this->selectHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->selectHelper = $this->view->plugin('formselect');
        }

        return $this->selectHelper;
    }

    /**
     * Retrieve the FormText helper.
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

        return $this->textHelper;
    }
}
