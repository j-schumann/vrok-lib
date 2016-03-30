<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

/**
 * Utility class for testing and manipulation of PHP arrays.
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class ArrayUtils
{
    /**
     * Prepends a key/value pair to the first element of the array.
     *
     * @param array $arr
     * @param mixed $key
     * @param mixed $val
     *
     * @return array
     */
    public static function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr       = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr       = array_reverse($arr, true);

        return $arr;
    }

    /**
     * Calculates the average of the values in the given array.
     *
     * @param array $array
     *
     * @return float
     */
    public static function array_average(array $array)
    {
        return count($array)
            ? array_sum($array) / count($array)
            : 0;
    }

    /**
     * Calculates the median of the values in the given array.
     *
     * @param array $array
     *
     * @return float
     *
     * @throws \DomainException if the array is empty
     */
    public static function array_median(array $array)
    {
        // @todo perhaps all non numeric values should filtered out of $array here?
        $count = count($array);
        if ($count === 0) {
            throw new \DomainException('Median of an empty array is undefined');
        }

        sort($array, SORT_NUMERIC);
        $middle = floor($count / 2);

        if ($count % 2) { // odd number, middle is the median
            return $array[$middle];
        }

        // even number, calculate avg of 2 medians
        $low  = $array[$middle - 1];
        $high = $array[$middle];

        return ($low + $high) / 2;
    }

/**
     * Removes the given value from the given array. If the value exists multiple times,
     * all instances are removed.
     *
     * @param array $arr
     * @param mixed $value
     */
    public static function unsetValue(array &$arr, $value)
    {
        foreach (array_keys($arr, $value, true) as $key) {
            unset($arr[$key]);
        }
    }
}
