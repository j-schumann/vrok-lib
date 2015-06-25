<?php
/**
 * @copyright   (c) 2014, Vrok
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
     * @return array
     */
    public static function array_unshift_assoc(&$arr, $key, $val)
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr = array_reverse($arr, true);
        return $arr;
    }

    /**
     * Calculates the average of the values in the given array.
     *
     * @param array $array
     * @return float
     */
    public static function array_average(array $array)
    {
        return count($array)
            ? array_sum($array) / count($array)
            : 0;
    }
}
