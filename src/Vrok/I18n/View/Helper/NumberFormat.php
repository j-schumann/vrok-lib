<?php
/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
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
     * Format a number
     *
     * @param  int|float $number
     * @param  int       $formatStyle
     * @param  int       $formatType
     * @param  string    $locale
     * @param  int|array $decimals  e.g. 3 or [3, 4]
     * @return string
     */
    public function __invoke(
        $number,
        $formatStyle = null,
        $formatType = null,
        $locale = null,
        $decimals = null
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
        if (!is_array($decimals) && $decimals < 0) {
            $decimals = $this->getDecimals();
        }

        $formatterId = md5($formatStyle . "\0" . $locale . "\0" . $decimals);

        if (!isset($this->formatters[$formatterId])) {
            $this->formatters[$formatterId] = new NumberFormatter(
                $locale,
                $formatStyle
            );
\Zend\Debug\Debug::dump($decimals);
            if ($decimals !== null) {

                if (is_array($decimals)) {
                    $this->formatters[$formatterId]->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals[0]);
                    $this->formatters[$formatterId]->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals[1]);
                } else {
                    $this->formatters[$formatterId]->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
                    $this->formatters[$formatterId]->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
                }
            }
        }

        return $this->formatters[$formatterId]->format($number, $formatType);
    }
}
