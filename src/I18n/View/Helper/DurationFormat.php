<?php

/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\View\Helper;

use DateInterval;
use Vrok\Stdlib\DateInterval as VrokInterval;
use Zend\I18n\View\Helper\AbstractTranslatorHelper;

/**
 * View helper that outputs the given duration in human readable form.
 * Can calculate carry over points, skips "0" values (if not inbetween),
 * can combine days+hours into hours or split hours into days+hours.
 * Uses the translation for localized output, respecting singular/plural.
 * Supported options are:
 *    showYears, showMonths, showDays, showHours, showMinutes, showSeconds.
 */
class DurationFormat extends AbstractTranslatorHelper
{
    /**
     * The locale to use.
     *
     * @var string
     */
    protected $locale = null;

    /**
     * Formats a number of hours in human readable form.
     * If no value is given the helper is returned.
     *
     * @param mixed  $value
     * @param array  $options
     * @param string $locale
     *
     * @return mixed
     */
    public function __invoke($value = null, array $options = [], $locale = null)
    {
        if (!$value) {
            return $this;
        }

        return $this->formatDuration($value, $options, $locale);
    }

    /**
     * Shortcut function - format the duration to only show the sum of hours&minutes.
     *
     * @param mixed  $value
     * @param string $locale
     *
     * @return string
     */
    public function hoursAndMinutes($value, $locale = null)
    {
        return $this->formatDuration(
            $value,
            [
                'showYears'   => false,
                'showMonths'  => false,
                'showDays'    => false,
                'showHours'   => true,
                'shouMinutes' => true,
                'showSeconds' => false,
            ],
            $locale
        );
    }

    /**
     * Formats a number of hours in human readable form.
     *
     * @param mixed  $value
     * @param array  $options
     * @param string $locale
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function formatDuration($value, array $options = [], $locale = null)
    {
        $this->locale = $locale;

        if (is_array($value)) {
            $value = VrokInterval::fromArray($value);
        }

        if (!$value instanceof VrokInterval) {
            if ($value instanceof DateInterval) {
                $value = VrokInterval::convert($value);
            } else {
                // @todo custom exception interface
                throw new \InvalidArgumentException('Value can not be parsed as interval!');
            }
        }

        // default is to show all parts
        $showYears   = isset($options['showYears']) ? $options['showYears'] : true;
        $showMonths  = isset($options['showMonths']) ? $options['showMonths'] : true;
        $showDays    = isset($options['showDays']) ? $options['showDays'] : true;
        $showHours   = isset($options['showHours']) ? $options['showHours'] : true;
        $showMinutes = isset($options['showMinutes']) ? $options['showMinutes'] : true;
        $showSeconds = isset($options['showSeconds']) ? $options['showSeconds'] : true;

        // fullXOnly: when there are smaller parts shown after the current part we do not
        // round, e.g. "40 days" will be shown as "1 month 10 days" if the days are
        // enabled, else we round to "2 months"

        // allX: when there are no bigger parts shown before the current part we show the
        // sum, e.g. "390 days" are shown as "1 year 1 month" if the years are enabled,
        // else as "13 months"

        $fullYearsOnly = $showMonths || $showDays || $showHours || $showMinutes || showSeconds;
        $y             = $showYears
            ? $value->getYears($fullYearsOnly ? 'full' : 'round')
            : 0;

        $allMonths      = !$showYears;
        $fullMonthsOnly = $showDays || $showHours || $showMinutes || showSeconds;
        $m              = $showMonths
            ? $value->getMonths($allMonths, $fullMonthsOnly ? 'full' : 'round')
            : 0;

        $allDays      = !$showYears && !$showMonths;
        $fullDaysOnly = $showHours || $showMinutes || showSeconds;
        $d            = $showDays
            ? $value->getDays($allDays, $fullDaysOnly ? 'full' : 'round')
            : 0;

        $allHours      = !$showYears && !$showMonths && !$showDays;
        $fullHoursOnly = $showMinutes || showSeconds;
        $h             = $showHours
            ? $value->getHours($allHours, $fullHoursOnly ? 'full' : 'round')
            : 0;

        $allMinutes      = !$showYears && !$showMonths && !$showDays && !$showHours;
        $fullMinutesOnly = $showSeconds;
        $i               = $showMinutes
            ? $value->getMinutes($allMinutes, $fullMinutesOnly ? 'full' : 'round')
            : 0;

        $allSeconds = !$showYears && !$showMonths && !$showDays && !$showHours && !$showMinutes;
        $s          = $showSeconds
            ? $value->getSeconds($allSeconds, 'full')
            : 0;

        $string = '';
        if ($y) {
            $string .= $y.' '.$this->translate(['year', 'years'], $y);
        }

        // show "0 months" if a gap would occur (e.g. 3 years, 12 days)
        if ($m || $y && ($d || $h || $i || $s)) {
            $string .= ', '.$m.' '.$this->translate(['month', 'months'], $m);
        }

        // show "0 days" if a gap would occur (e.g. 3 months, 12 hours)
        if ($d || ($y || $m) && ($h || $i || $s)) {
            $string .= ', '.$d.' '.$this->translate(['day', 'days'], $d);
        }

        // show "0 hours" if a gap would occur (e.g. 3 days, 12 minutes)
        if ($h || ($y || $m || $d) && ($i || $s)) {
            $string .= ', '.$h.' '.$this->translate(['hour', 'hours'], $h);
        }

        // show "0 minutes" if a gap would occur (e.g. 3 hours, 12 seconds)
        if ($i || ($y || $m || $d || $h) && $s) {
            $string .= ', '.$i.' '.$this->translate(['minute', 'minutes'], $i);
        }

        // show "0 seconds" if nothing else would be displayed
        if ($s || !$string) {
            $string .= ', '.$s.' '.$this->translate(['second', 'seconds'], $s);
        }

        return trim($string, ' ,');
    }

    /**
     * Translates singular/plural depending on the given value.
     *
     * @param array $translations
     * @param type  $value
     *
     * @return string
     */
    protected function translate(array $translations, $value)
    {
        return $this->getView()->translatePlural(
            $translations,
            $value,
            $this->getTranslatorTextDomain(),
            $this->locale
        );
    }
}
