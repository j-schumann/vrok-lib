<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\View\Helper;

use Zend\I18n\Exception;
use Zend\I18n\View\Helper\AbstractTranslatorHelper;

/**
 * The default system works by storing the singular/plural translation for a key
 * as array. We only support (string)$key => (string)$message. Also with the
 * default signature translatePlural($singular, $plural, $number) $plural is
 * never translated, only returned if no translation was found for $singular.
 * The PluralRule supports more than two differentiations which is not reflected
 * by the function signature.
 *
 * We copy the PluralHelper usage here and provide the messages as array.
 */
class TranslatePlural extends AbstractTranslatorHelper
{
    /**
     * Translate a plural message.
     *
     * @param array  $messages
     * @param int    $number
     * @param string $textDomain
     * @param string $locale
     *
     * @return string
     */
    public function __invoke(array $messages, $number, $textDomain = null, $locale = null)
    {
        $translator = $this->getTranslator();
        if (null === $translator) {
            throw new Exception\RuntimeException('Translator has not been set');
        }
        if (null === $textDomain) {
            $textDomain = $this->getTranslatorTextDomain();
        }

        // default: no params, only the singular/plural messages
        $strings = $messages;
        $params  = [];

        // first element is an array: these are the messages and the second
        // element are the params (to use for both messages)
        if (is_array($messages[0])) {
            $strings = $messages[0];
            $params  = $messages[1];
        }

        $domainObject = $translator->getTranslator()->getTextDomain($textDomain, $locale);
        $index        = $domainObject->getPluralRule()->evaluate($number);

        return $translator->translate(
            [$strings[$index], $params],
            $textDomain,
            $locale
        );
    }
}
