<?php
/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

use Datetime;
use DateInterval as BaseInterval;

/**
 * Extends the default class by adding some convenience calculation functions.
 * Supports carry over points.
  */
class DateInterval extends BaseInterval
{
    // hardcoded carry over points, the average year has 365.25 days, thus
    // the average month has 30.4375 days
    const SECONDS_PER_MINUTE = 60;
    const SECONDS_PER_HOUR   = 3600; // SECONDS_PER_MINUTE * 60
    const SECONDS_PER_DAY    = 86400; // SECONDS_PER_HOUR * 24
    const SECONDS_PER_MONTH  = 2629800; // SECONDS_PER_YEAR / 12
    const SECONDS_PER_YEAR   = 31557600; // SECONDS_PER_DAY * 365.25 (1/4th leap year)

    // determines how the values for getYears/getMonths/getDays/getHours/getMinutes
    // are returned
    const OPTION_EXACT = 'exact'; // default, returns a float/integer
    const OPTION_FULL  = 'full'; // round down, returns an integer
    const OPTION_ROUND = 'round'; // rounds to the nearest integer

    /**
     * Creates an interval from the given dates.
     *
     * @param Datetime $start
     * @param Datetime $end
     * @return self
     */
    public static function createDiff(Datetime $start, Datetime $end)
    {
        return self::convert($start->diff($end));
    }

    /**
     * Converts the given \DateTime into the custom class to use the extended
     * functionality.
     *
     * @param BaseInterval $interval
     * @return self
     */
    public static function convert(BaseInterval $interval)
    {
        // @todo bad hack but probably faster than using Reflection
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            strlen(__CLASS__),
            __CLASS__,
            strstr(strstr(serialize($interval), '"'), ':')
        ));
    }

    /**
     * Create a new interval instance from the array specification.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $interval = new self('PT0S');
        if (isset($data['years'])) {
            $interval->y = (int)$data['years'];
        }
        if (isset($data['months'])) {
            $interval->m = (int)$data['months'];
        }
        if (isset($data['days'])) {
            $interval->d = (int)$data['days'];
        }
        if (isset($data['hours'])) {
            $interval->h = (int)$data['hours'];
        }
        if (isset($data['minutes'])) {
            $interval->i = (int)$data['minutes'];
        }
        if (isset($data['seconds'])) {
            $interval->s = (int)$data['seconds'];
        }
        return $interval;
    }

    /**
     * Retrieve the interval duration in seconds.
     * Attention: If $this->days is not set but $this->m and/or $this->y general values
     * are used: a year is assumed to be 365 full days, a month to be 30 full days!
     *
     * @return int
     */
    public function asSeconds()
    {
        $seconds = $this->h * self::SECONDS_PER_HOUR
            + $this->i * self::SECONDS_PER_MINUTE
            + $this->s;

        // if created by $dateA->diff($dateB) the days are set to the exact number
        if ($this->days) {
            $seconds += $this->days * self::SECONDS_PER_DAY;
        }
        else {
            $seconds += $this->y * self::SECONDS_PER_YEAR
                + $this->m * self::SECONDS_PER_MONTH
                + $this->d * self::SECONDS_PER_DAY;
        }

        return $seconds;
    }

    /**
     * Retrieve the number of years in this interval.
     *
     * @param string $round     whether to return the exact value or round/floor
     * @return int|float
     */
    public function getYears($round = self::OPTION_EXACT)
    {
        $s = $this->asSeconds();
        return $this->round($s / self::SECONDS_PER_YEAR, $round);
    }

    /**
     * Retrieve the number of months in this interval.
     *
     * @param bool $complete    if true the whole interval is returned as months,
     *     else only the months not adding up to years are returned
     * @param string $round     whether to return the exact value or round/floor
     * @return int|float
     */
    public function getMonths(
        $complete = true,
        $round = self::OPTION_EXACT
    ) {
        $s = $this->asSeconds();
        $value = $complete
            ? $s
            : ($s - floor($s / self::SECONDS_PER_YEAR) * self::SECONDS_PER_YEAR);
        return $this->round($value / self::SECONDS_PER_MONTH, $round);
    }

    /**
     * Retrieve the number of days in this interval.
     *
     * @param bool $complete    if true the whole interval is returned as days,
     *     else only the days not adding up to months are returned
     * @param string $round     whether to return the exact value or round/floor
     * @return int|float
     */
    public function getDays(
        $complete = true,
        $round = self::OPTION_EXACT
    ) {
        $s = $this->asSeconds();
        $value = $complete
            ? $s
            : ($s - floor($s / self::SECONDS_PER_MONTH) * self::SECONDS_PER_MONTH);
        return $this->round($value / self::SECONDS_PER_DAY, $round);
    }

    /**
     * Retrieve the number of hours in this interval.
     *
     * @param bool $complete    if true the whole interval is returned as hours,
     *     else only the hours not adding up to days are returned
     * @param string $round     whether to return the exact value or round/floor
     * @return int|float
     */
    public function getHours(
        $complete = true,
        $round = self::OPTION_EXACT
    ) {
        $s = $this->asSeconds();
        $value = $complete
            ? $s
            : ($s - floor($s / self::SECONDS_PER_DAY) * self::SECONDS_PER_DAY);
        return $this->round($value / self::SECONDS_PER_HOUR, $round);
    }

    /**
     * Retrieve the number of minutes in this interval.
     *
     * @param bool $complete    if true the whole interval is returned as minutes,
     *     else only the minutes not adding up to hours are returned
     * @param string $round     whether to return the exact value or round/floor
     * @return int|float
     */
    public function getMinutes(
        $complete = true,
        $round = self::OPTION_EXACT
    ) {
        $s = $this->asSeconds();
        $value = $complete
            ? $s
            : ($s - floor($s / self::SECONDS_PER_HOUR) * self::SECONDS_PER_HOUR);
        return $this->round($value / self::SECONDS_PER_MINUTE, $round);
    }

    /**
     * Retrieve the number of seconds in this interval.
     *
     * @param bool $complete    if true the whole interval is returned as seconds,
     *     else only the seconds not adding up to minutes are returned
     * @return int
     */
    public function getSeconds($complete = true)
    {
        $s = $this->asSeconds();
        $value = $complete
            ? $s
            : ($s - floor($s / self::SECONDS_PER_MINUTE) * self::SECONDS_PER_MINUTE);
        return $value;
    }

    /**
     *
     * @return string
     */
    public function getIntervalSpec()
    {
        $spec = 'P';

        $y = $this->getYears(self::OPTION_FULL);
        if ($y) {
            $spec .= $y.'Y';
        }

        $m = $this->getMonths(false, self::OPTION_FULL);
        if ($m) {
            $spec .= $m.'M';
        }

        $d = $this->getDays(false, self::OPTION_FULL);
        if ($d) {
            $spec .= $d.'D';
        }

        $h = $this->getHours(false, self::OPTION_FULL);
        if ($h) {
            $spec .= 'T'.$h.'H';
        }

        $i = $this->getMinutes(false, self::OPTION_FULL);
        if ($i) {
            if (!$h) {
                $spec .= 'T';
            }
            $spec .= $i.'M';
        }

        $s = $this->getSeconds(false);
        if ($s) {
            if (!$h && !$i) {
                $spec .= 'T';
            }
            $spec .= $s.'M';
        }

        return $spec;
    }

    /**
     * Evaluates the roundOption.
     *
     * @param int|float $value
     * @param string $round
     * @return int|float
     */
    protected function round($value, $round)
    {
        if ($round === self::OPTION_FULL) {
            return floor($value);
        }
        if ($round === self::OPTION_ROUND) {
            return round($value);
        }

        return $value;
    }
}
