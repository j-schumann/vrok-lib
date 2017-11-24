<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Filter;

use Zend\Filter\AbstractFilter;

/**
 * Filters the array returned by Interval elements into a string according to
 * https://en.wikipedia.org/wiki/ISO_8601#Durations.
 *
 * If the value can not be combined into a valid interval_spec the unfiltered
 * value is returned!
 */
class Interval extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function filter($value)
    {
        if (! is_array($value) || empty($value['intervaltype'])) {
            return $value;
        }

        $amount = isset($value['amount']) ? $value['amount'] : null;
        $type   = $value['intervaltype'];

        if (empty($amount)) {
            return;
        }

        if (! is_numeric($amount) || (int) $amount != $amount || $amount <= 0) {
            return $value;
        }

        if (! in_array($type, ['S', 'M', 'H', 'D'])) {
            return $value;
        }

        // construct a valid interval_spec string
        $interval = 'P';
        if (in_array($type, ['S', 'M', 'H'])) {
            $interval .= 'T';
        }
        $interval .= $amount.$type;

        return $interval;
    }
}
