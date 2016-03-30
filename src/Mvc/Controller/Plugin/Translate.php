<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Allows to translate messages directly from the controller.
 */
class Translate extends AbstractPlugin
{
    /**
     * Translates the given message.
     *
     * @param string $message
     * @param string $textDomain
     * @param string $locale
     *
     * @return string
     */
    public function __invoke($message, $textDomain = null, $locale = null)
    {
        $translator = $this->getController()->getServiceLocator()
                ->get('Zend\I18n\Translator\TranslatorInterface');

        return $translator->translate($message, $textDomain, $locale);
    }
}
