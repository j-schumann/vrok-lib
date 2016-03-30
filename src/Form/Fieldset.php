<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

use Zend\Form\Fieldset as ZendFieldset;

/**
 * Common functionality for fieldsets that work with doctrine entities.
 *
 * Fieldset class must be instantiated by the formElementManager or the
 * serviceLocator won't be injected!
 */
class Fieldset extends ZendFieldset
{
    use SharedFunctions;
}
