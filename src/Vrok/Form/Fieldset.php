<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Form;

use Zend\Form\Fieldset as ZendFieldset;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Common functionality for fieldsets that work with doctrine entities.
 *
 * Fieldset class must be instantiated by the formElementManager or the
 * serviceLocator won't be injected!
 */
class Fieldset extends ZendFieldset implements ServiceLocatorAwareInterface
{
    use SharedFunctions;
}
