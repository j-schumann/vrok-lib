<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

/**
 * Used to extend our ajax supporting fieldset and form classes.
 */
trait SharedAjaxFunctions
{
    /**
     * Add an element or fieldset
     *
     * Injects the onclick attribute to element specifications that represent
     * a submit button/image to allow submitting via XHR.
     * Does not modify instantiated elements or elements that already have the
     * onclick set.
     *
     * @param  array|Traversable|ElementInterface $elementOrFieldset
     * @param  array                              $flags
     * @return self
     * @todo  - Nicht nur anhand [attribs][type] den Button erkennen sondern
     * auch nur an [type], fals attribs->type nicht gesetzt ist
     */
    public function add($elementOrFieldset, array $flags = array())
    {
        if ($elementOrFieldset instanceof \Zend\Form\ElementInterface
            || !isset($elementOrFieldset['attributes'])
            || isset($elementOrFieldset['attributes']['onclick']))
        {
            return parent::add($elementOrFieldset, $flags);
        }

        if (isset($elementOrFieldset['attributes']['type'])
            && $elementOrFieldset['attributes']['type']== 'submit')
        {
            $elementOrFieldset['attributes']['onclick'] =
                    "return Vrok.Tools.submit(this, this.name, this.value);";
        }

        return parent::add($elementOrFieldset, $flags);
    }
}
