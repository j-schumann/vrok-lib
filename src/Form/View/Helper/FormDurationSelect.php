<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\View\Helper;

use Vrok\Form\Element\DurationSelect;
use Zend\Form\ElementInterface;
use Zend\Form\Exception;
use Zend\Form\View\Helper\AbstractHelper;

/**
 * Renders the DurationSelect form element.
 * Either renders only one select element or a pattern is used where %hours% and %minutes%
 * are replaced by the form elements.
 */
class FormDurationSelect extends AbstractHelper
{
    /**
     * Render markup.
     *
     * @var string
     */
    protected $pattern = '%hours% : %minutes%';

    /**
     * FormSelect helper.
     *
     * @var FormSelect
     */
    protected $selectHelper;

    /**
     * Invoke helper as function.
     *
     * Proxies to {@link render()}.
     *
     * @param ElementInterface $element
     * @param string           $pattern
     *
     * @return string
     */
    public function __invoke(
        ElementInterface $element = null,
        $pattern = '%hours% : %minutes%'
    ) {
        if (!$element) {
            return $this;
        }

        $this->pattern = $pattern;

        return $this->render($element);
    }

    /**
     * Render a the two select elements.
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
        if (!$element instanceof DurationSelect) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the element is of type Vrok\Form\Element\DurationSelect',
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

        $hourOptions   = $element->getHourValueOptions();
        $minuteOptions = $element->getMinuteValueOptions();

        $hourElement   = $element->getHourElement()->setValueOptions($hourOptions);
        $minuteElement = $element->getMinuteElement()->setValueOptions($minuteOptions);

        if ($element->shouldCreateEmptyOption()) {
            $hourElement->setEmptyOption('');
            $minuteElement->setEmptyOption('');
        }

        // ignore the pattern if only one element is shown
        if (!$element->getShowHours()) {
            return $selectHelper->render($minuteElement);
        }
        if (!$element->getShowMinutes()) {
            return $selectHelper->render($hourElement);
        }

        $markup = str_replace(
            '%hours%',
            $selectHelper->render($hourElement),
            $this->pattern
        );

        return str_replace(
            '%minutes%',
            $selectHelper->render($minuteElement),
            $markup
        );
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
}
