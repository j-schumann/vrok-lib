<?php

/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\View\Helper;

use NumberFormatter;
use Zend\I18n\View\Helper\NumberFormat as ZendFormat;

/**
 * Overwritten to allow setting of MIN_FRACTION_DIGITS and MAX_FRACTION_DIGITS
 * separately.
 */
class NumberFormat extends ZendFormat
{
    /**
     * Format a number.
     *
     * @param int|float $number
     * @param int       $formatStyle
     * @param int       $formatType
     * @param string    $locale
     * @param int|array $decimals    e.g. 3 or [3, 4]
     * @param array|null $textAttributes
     *
     * @return string
     */
    public function __invoke(
        $number,
        $formatStyle = null,
        $formatType = null,
        $locale = null,
        $decimals = null,
        array $textAttributes = NULL
    ) {
        if (null === $locale) {
            $locale = $this->getLocale();
        }
        if (null === $formatStyle) {
            $formatStyle = $this->getFormatStyle();
        }
        if (null === $formatType) {
            $formatType = $this->getFormatType();
        }
        if (!is_array($decimals) && (!is_int($decimals) || $decimals < 0)) {
            $decimals = $this->getDecimals();
        }
        if (!is_array($textAttributes)) {
            $textAttributes = $this->getTextAttributes();
        }

        $formatterId = md5(
            $formatStyle . "\0" . $locale . "\0" . json_encode($decimals) . "\0"
            . md5(serialize($textAttributes))
        );

        if (!isset($this->formatters[$formatterId])) {
            $formatter = new NumberFormatter($locale, $formatStyle);

            if ($decimals !== null) {
                if (is_array($decimals)) {
                    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals[0]);
                    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals[1]);
                } else {
                    $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
                    $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
                }
            }

            foreach ($textAttributes as $textAttribute => $value) {
                $formatter->setTextAttribute($textAttribute, $value);
            }

            $this->formatters[$formatterId] = $formatter;
        }

        return $this->formatters[$formatterId]->format($number, $formatType);
    }
}
