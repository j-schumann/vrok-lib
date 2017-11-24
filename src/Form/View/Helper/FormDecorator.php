<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\FormInterface;
use Zend\Form\FieldsetInterface;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\View\Helper\Form;
use Zend\Form\View\Helper\FormCollection;

/**
 * Implements a view helper similar to ZF1 that renders a complete form at once.
 */
class FormDecorator extends AbstractHelper
{
    /**
     * Element view helper instance.
     *
     * @var FormElementDecorator
     */
    protected $elementHelper = null;

    /**
     * FormCollection view helper instance.
     *
     * @var FormCollection
     */
    protected $fieldsetHelper = null;

    /**
     * Form view helper instance.
     *
     * @var Form
     */
    protected $formHelper = null;

    /**
     * Invoke helper as functor.
     *
     * Proxies to {@link render()}.
     *
     * @param FormInterface $form
     *
     * @return string|self
     */
    public function __invoke(FormInterface $form)
    {
        if (! $form) {
            return $this;
        }

        $form->prepare();

        return $this->render($form);
    }

    /**
     * Render a form by iterating through all fieldsets and elements and adding
     * open / close tags.
     *
     * @param FormInterface $form
     *
     * @return string
     */
    public function render(FormInterface $form)
    {
        $formHelper     = $this->getFormHelper();
        $elementHelper  = $this->getElementHelper();
        $fieldsetHelper = $this->getFieldsetHelper();

        $markup = $formHelper->openTag($form);

        foreach ($form->getIterator() as $elementOrFieldset) {
            if ($elementOrFieldset instanceof FieldsetInterface) {
                $markup .= $fieldsetHelper($elementOrFieldset, true);
            } elseif ($elementOrFieldset instanceof ElementInterface) {
                $markup .= $elementHelper($elementOrFieldset);
            }
        }

        $markup .= $formHelper->closeTag();

        return $markup;
    }

    /**
     * Retrieve the element helper.
     *
     * @return FormElementDecorator
     */
    protected function getElementHelper()
    {
        if ($this->elementHelper) {
            return $this->elementHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->elementHelper = $this->view->plugin('formElementDecorator');
        }

        if (! $this->elementHelper instanceof FormElementDecorator) {
            $this->elementHelper = new FormElementDecorator();
            $this->elementHelper->setView($this->view);
        }

        if ($this->hasTranslator()) {
            $this->elementHelper->setTranslator(
                $this->getTranslator(),
                $this->getTranslatorTextDomain()
            );
        }

        return $this->elementHelper;
    }

    /**
     * Retrieve the fieldset helper.
     *
     * @return FormCollection
     */
    protected function getFieldsetHelper()
    {
        if ($this->fieldsetHelper) {
            return $this->fieldsetHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->fieldsetHelper = $this->view->plugin('form_collection');
        }

        if (! $this->fieldsetHelper instanceof FormCollection) {
            $this->fieldsetHelper = new FormCollection();
        }

        $this->fieldsetHelper->setElementHelper($this->getElementHelper());

        if ($this->hasTranslator()) {
            $this->fieldsetHelper->setTranslator(
                $this->getTranslator(),
                $this->getTranslatorTextDomain()
            );
        }

        return $this->fieldsetHelper;
    }

    /**
     * Retrieve the form helper to render open/close tags.
     *
     * @return Form
     */
    protected function getFormHelper()
    {
        if ($this->formHelper) {
            return $this->formHelper;
        }

        if (method_exists($this->view, 'plugin')) {
            $this->formHelper = $this->view->plugin('form');
        }

        if (! $this->formHelper instanceof Form) {
            $this->formHelper = new Form();
        }

        return $this->formHelper;
    }
}
