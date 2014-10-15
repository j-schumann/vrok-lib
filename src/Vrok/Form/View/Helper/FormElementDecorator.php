<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\View\Helper\FormLabel;
use Zend\Form\View\Helper\FormElement;
use Zend\Form\View\Helper\FormElementErrors;
use Zend\View\Helper\Partial;

/**
 * View helper that renders a single form element into a container including
 * the label, errors and optional description or tooltip.
 */
class FormElementDecorator extends AbstractHelper
{
    /**
     * Additional options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Label helper instance
     *
     * @var FormLabel
     */
    protected $labelHelper;

    /**
     * Label helper instance
     *
     * @var Partial
     */
    protected $partialHelper;

    /**
     * Element helper instance
     *
     * @var FormElement
     */
    protected $elementHelper;

    /**
     * Errors helper instance
     *
     * @var FormElementErrors
     */
    protected $elementErrorsHelper;

    /**
     * Invoke helper as functor
     *
     * Proxies to {@link render()}.
     *
     * @param  ElementInterface $element
     * @param array $options
     * @return string|self
     */
    public function __invoke(ElementInterface $element, array $options = array())
    {
        $this->options = $options;

        if (!$element) {
            return $this;
        }

        return $this->render($element);
    }

    /**
     * Utility form helper that renders a label (if it exists), an element and errors
     *
     * @param  ElementInterface $element
     * @return string
     */
    public function render(ElementInterface $element)
    {
        // unify elements, we also need the ID for the labels "for" attribute
        // and the container IDs
        if (!$element->getAttribute('id')) {
            $id = str_replace(array('[', ']'), array('-', ''), $element->getName());
            $element->setAttribute('id', $id);
        }

        // inject the class if no custom label_attributes are set
        $required = $element->getAttribute('required');
        if ($required === 'required' && !$element->getOption('label_attributes')) {
            $element->setLabelAttributes(array(
                'class' => 'required',
            ));
        }

        $ph = $this->getPartialHelper();
        return $ph('vrok/partials/form/element', [
            'element' => $element,
        ]);

        $type = $element->getAttribute('type');
        $elementMarkup     = $this->getElementMarkup($element);
        $errorsMarkup      = $this->getErrorsMarkup($element);
        $labelOpen         = $this->getLabelOpen($element);
        $labelClose        = $this->getLabelClose($element);

        $descriptionMarkup = '';
        //$tooltipMarkup     = '';

        $description = $element->getOption('description');
        if ($description) {
            $translator = $this->getTranslator();
            if ($translator) {
                $description = $translator->translate($description, $this->getTranslatorTextDomain());
            }
            $descriptionMarkup = "<p class=\"element-description\">$description</p>";
        }
/*
        $tooltip = $element->getOption('tooltip');
        if ($tooltip) {
            $tooltipHelper = $this->getTooltipHelper();
            $tooltipMarkup = $tooltipHelper($tooltip);
        }*/

        $containerClass = "form-group element-container element-$type";

        if (count($element->getMessages())) {
            $containerClass .= ' input-error';
        }

        $markup = '';
        if (!isset($this->options['noContainer'])) {
            $containerId = 'container-'.$element->getAttribute('id');
            $markup .= "<div class=\"$containerClass\" id=\"$containerId\">";
        }

        if ($type === 'multi_checkbox' || $type === 'radio') {
            $markup .= "<fieldset class=\"$containerClass\">";
        }

        if ($type === 'checkbox') {
            $markup .= $labelOpen.$elementMarkup.$labelClose.$descriptionMarkup;
        }
        else {
            $markup .= $labelOpen.$labelClose.$descriptionMarkup.$elementMarkup;
        }

        $markup .= $errorsMarkup;

        if ($type === 'multi_checkbox' || $type === 'radio') {
            $markup .= '</fieldset>';
        }

        if (!isset($this->options['noContainer'])) {
            $markup .= '</div>';
        }

        return $markup;
    }

    /**
     * Renders the form element using the default helper.
     *
     * @param ElementInterface $element
     * @return string
     */
    protected function getElementMarkup(ElementInterface $element)
    {
        $class = $element->getAttribute('class');
        $class .= ' form-control';
        $element->setAttribute('class', $class);
        $elementHelper = $this->getElementHelper($element);
        return $elementHelper->render($element);
    }

    /**
     * Retrieve the view helper to render the element.
     *
     * @param ElementInterface $element
     * @return \Zend\View\Helper\HelperInterface
     */
    protected function getElementHelper(ElementInterface $element)
    {
        if ($element instanceof \Vrok\Form\ElementInterface
            && method_exists($this->view, 'plugin'))
        {
            return $this->view->plugin($element->suggestViewHelper());
        }

        if ($this->elementHelper) {
            return $this->elementHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementHelper = $this->view->plugin('form_element');
        }
        else {
            $this->elementHelper = new FormElement();
        }

        return $this->elementHelper;
    }

    /**
     * Translates and renders the label to use for this element.
     * May be empty or a <legend> for some element types.
     *
     * @param ElementInterface $element
     * @return string
     */
    protected function getLabelOpen(ElementInterface $element)
    {
        $label = $element->getLabel();
        if (!$label || $element->getAttribute('type') === 'hidden') {
            return '';
        }

        $translator = $this->getTranslator();
        if ($translator) {
            $label = $translator->translate($label, $this->getTranslatorTextDomain());
        }

        $labelAttributes = $element->getLabelAttributes();
        $labelAttributes['class'] = isset($labelAttributes['class'])
            ? $labelAttributes['class']
            : '';
        $labelAttributes['class'] .= ' control-label col-md-4';

        if (count($element->getMessages())) {
            $labelAttributes['class'] .= ' error';
        }

        $labelHelper = $this->getLabelHelper();
        $attributeString = $labelHelper->createAttributesString($labelAttributes);

        $type = $element->getAttribute('type');
        if ($type === 'multi_checkbox' || $type === 'radio') {
            return "<legend $attributeString>$label";
        }

        return "<label $attributeString>$label";
    }

    protected function getLabelClose(ElementInterface $element)
    {
        $type = $element->getAttribute('type');
        if ($type === 'multi_checkbox' || $type === 'radio') {
            return '</legend>';
        }

        return '</label>';
    }

    /**
     * Retrieve the FormLabel helper
     *
     * @return FormLabel
     */
    protected function getLabelHelper()
    {
        if ($this->labelHelper) {
            return $this->labelHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->labelHelper = $this->view->plugin('form_label');
        }

        if (!$this->labelHelper instanceof FormLabel) {
            $this->labelHelper = new FormLabel();
        }

        // we do not inject the translator as we translate the label ourself
        return $this->labelHelper;
    }

    protected function getPartialHelper()
    {
        if ($this->partialHelper) {
            return $this->partialHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->partialHelper = $this->view->plugin('partial');
        }

        if (!$this->partialHelper instanceof Partial) {
            $this->partialHelper = new Partial();
            $this->partialHelper->setView($this->view);
        }

        return $this->partialHelper;
    }

    /**
     * Renders the element error messages.
     *
     * @param ElementInterface $element
     * @return string
     */
    protected function getErrorsMarkup(ElementInterface $element)
    {
        if (isset($this->options['noErrors']) && $this->options['noErrors']) {
            return '';
        }

        if (!count($element->getMessages())) {
            return '';
        }

        $errorsHelper = $this->getErrorsHelper();
        return $errorsHelper->render($element, array('class' => 'errors'));
    }

    /**
     * Retrieve the FormElementErrors helper
     *
     * @return FormElementErrors
     */
    protected function getErrorsHelper()
    {
        if ($this->elementErrorsHelper) {
            return $this->elementErrorsHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementErrorsHelper = $this->view->plugin('form_element_errors');
        }

        if (!$this->elementErrorsHelper instanceof FormElementErrors) {
            $this->elementErrorsHelper = new FormElementErrors();
        }

        return $this->elementErrorsHelper;
    }
}
