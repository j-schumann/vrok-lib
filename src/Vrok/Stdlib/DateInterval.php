<?php
/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     http://customlicense CustomLicense
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

use DateInterval as BaseInterval;

class DateInterval extends BaseInterval
{
    static function convert(BaseInterval $interval)
    {
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            strlen(__CLASS__),
            __CLASS__,
            strstr(strstr(serialize($interval), '"'), ':')
        ));
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
        $seconds = $this->h * 3600
            + $this->i * 60
            + $this->s;

        // if created by $dateA->diff($dateB) the days are set to the exact number
        if ($this->days) {
            $seconds += $this->days * 86400;
        }
        else {
            $seconds += $this->y * 86400 * 365
                + $this->m * 86400 * 30
                + $this->d * 86400;
        }

        return $seconds;
    }

    /**
     * Retrieve the interval in minutes (rounded down).
     *
     * @return int
     */
    public function asMinutes()
    {
        return floor($this->asSeconds() / 60);
    }

    /**
     * Retrieve the interval in hours (rounded down).
     *
     * @return int
     */
    public function asHours()
    {
        return floor($this->asSeconds() / 3600);
    }
}
