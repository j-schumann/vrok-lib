<?php
namespace Vrok\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\View\Helper\FormLabel;
use Zend\Form\View\Helper\FormElement;
use Zend\Form\View\Helper\FormElementErrors;

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

        $type = $element->getAttribute('type');
        $elementMarkup     = $this->getElementMarkup($element);
        $errorsMarkup      = $this->getErrorsMarkup($element);
        $labelMarkup       = $this->getLabelMarkup($element);

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

        $containerClass = "element-container element-$type";

        if (count($element->getMessages())) {
            $containerClass .= ' input-error';
        }

        $markup = '';
        if (!isset($this->options['noContainer'])) {
            $containerId = 'container-'.$element->getAttribute('id');
            $markup .= "<div class=\"$containerClass\" id=\"$containerId\">";
        }

        if ($type === 'multi_checkbox' || $type === 'radio') {
            $markup .= '<fieldset>';
        }

        if ($type === 'checkbox') {
            $markup .= $elementMarkup.$labelMarkup.$descriptionMarkup;
        }
        else {
            $markup .= $labelMarkup.$descriptionMarkup.$elementMarkup;
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
    protected function getLabelMarkup(ElementInterface $element)
    {
        $label = $element->getLabel();
        if (!$label || $element->getAttribute('type') === 'hidden') {
            return '';
        }

        $translator = $this->getTranslator();
        if ($translator) {
            $label = $translator->translate($label, $this->getTranslatorTextDomain());
        }

        $labelHelper = $this->getLabelHelper();
        if (count($element->getMessages())) {
            $labelAttributes = $element->getLabelAttributes();
            $labelAttributes['class'] = isset($labelAttributes['class'])
                    ? $labelAttributes['class'].' error'
                    : 'error';
            $element->setLabelAttributes($labelAttributes);
        }

        $type = $element->getAttribute('type');
        if ($type === 'multi_checkbox' || $type === 'radio') {
            $labelAttributes = $element->getLabelAttributes();
            $attributeString = $labelHelper->createAttributesString($labelAttributes);
            return "<legend $attributeString>$label</legend>";
        }

        return $labelHelper($element, $label);
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
