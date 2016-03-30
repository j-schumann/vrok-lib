<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

interface ElementInterface extends \Zend\Form\ElementInterface
{
    /**
     * Suggest form render helpers like FormElement or FormElementDecorator
     * a viewHelper to use to render this element as FormElement does (and
     * can) not know all the elements and their corresponding helpers.
     *
     * @return string viewHelper name to query the helperManager with
     */
    public function suggestViewHelper();
}
