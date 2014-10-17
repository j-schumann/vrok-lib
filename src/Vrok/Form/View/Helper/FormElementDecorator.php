<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form\View\Helper;

use Zend\Form\ElementInterface;
use Zend\Form\View\Helper\AbstractHelper;
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
     * @var Partial
     */
    protected $partialHelper;

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

        $ph = $this->getPartialHelper();
        return $ph('vrok/partials/form/element', [
            'element' => $element,
        ]);
    }

    /**
     * Retrieve the helper to render the element partial.
     *
     * @return Partial
     */
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
}
