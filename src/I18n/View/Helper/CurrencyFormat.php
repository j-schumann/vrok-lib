<?php

/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\View\Helper;

use NumberFormatter;
use Zend\I18n\View\Helper\CurrencyFormat as ZendFormat;

/**
 * Overwritten to allow patterns with more than 2 decimal digits.
 *
 * ZF would set the pattern on the formatter and then use
 * setAttribute(FRACTION_DIGITS) which modifies the pattern. We prevent this
 * (when the helper is invoked with a custom pattern, using setCurrencyPattern
 * will not work) by not using setAttribute() at all.
 */
class CurrencyFormat extends ZendFormat
{
    /**
     * Format a number.
     *
     * @param float  $number
     * @param string $currencyCode
     * @param bool   $showDecimals
     * @param string $locale
     * @param string $pattern
     *
     * @return string
     */
    public function __invoke(
        $number,
        $currencyCode = null,
        $showDecimals = null,
        $locale = null,
        $pattern = null
    ) {
        if (null === $locale) {
            $locale = $this->getLocale();
        }
        if (null === $currencyCode) {
            $currencyCode = $this->getCurrencyCode();
        }
        if (null === $showDecimals) {
            $showDecimals = $this->shouldShowDecimals();
        }
        if (null === $pattern) {
            $pattern = $this->getCurrencyPattern();
        } else {
            // reset the decimal settings if we got custom pattern so we do
            // not modify it by setting any FRACTION_DIGITS
            $showDecimals = null;
        }

        return $this->formatCurrency($number, $currencyCode, $showDecimals, $locale, $pattern);
    }

    /**
     * Format a number.
     *
     * @param float     $number
     * @param string    $currencyCode
     * @param bool|null $showDecimals
     * @param string    $locale
     * @param string    $pattern
     *
     * @return string
     */
    protected function formatCurrency(
        $number,
        $currencyCode,
        $showDecimals,
        $locale,
        $pattern
    ) {
        $formatterId = md5($locale);

        if (!isset($this->formatters[$formatterId])) {
            $this->formatters[$formatterId] = new NumberFormatter(
                $locale,
                NumberFormatter::CURRENCY
            );
        }

        if ($pattern !== null) {
            $this->formatters[$formatterId]->setPattern($pattern);
        }

        // only set the FRACTION_DIGITS if showDecimals is set, if it is NULL
        // we got a custom pattern which would be modified by setAttribute()
        if ($showDecimals) {
            $this->formatters[$formatterId]->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        } elseif ($showDecimals === false) {
            $this->formatters[$formatterId]->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        }

        return $this->formatters[$formatterId]->formatCurrency($number, $currencyCode);
    }
}
