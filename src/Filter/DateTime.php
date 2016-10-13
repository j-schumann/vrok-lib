<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Filter;

use Traversable;
use Zend\Filter\AbstractFilter;

/**
 * Converts a dateTime string into an object using the given format.
 */
class DateTime extends AbstractFilter
{
    /**
     * @var array
     */
    protected $options = [
        'format' => 'Y-m-d',
    ];

    /**
     * Sets filter options.
     *
     * @param array|Traversable $options
     */
    public function __construct($options = null)
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets the format option.
     *
     * @param string $format
     *
     * @return self Provides a fluent interface
     */
    public function setFormat($format)
    {
        $this->options['format'] = $format;

        return $this;
    }

    /**
     * Returns the pattern option.
     *
     * @return string|null
     */
    public function getFormat()
    {
        return $this->options['format'];
    }

    /**
     * Defined by Zend\Filter\FilterInterface.
     *
     * Converts the array produced by the Zend\Form\Element\DateSelect
     * to a string according to the configured pattern. Returns null if the
     * value is no array or not all date parts are set.
     *
     * @param mixed $value
     *
     * @return string|null
     */
    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return \DateTime::createFromFormat($this->getFormat(), $value) ?: $value;
    }
}
