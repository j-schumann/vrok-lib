<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Filter;

use Traversable;
use Zend\Filter\AbstractFilter;

/**
 * Filters the array returned by DateSelect elements into a string.
 */
class DateSelect extends AbstractFilter
{
    /**
     * @var array
     */
    protected $options = array(
        'pattern' => 'Y-m-d',
    );

    /**
     * Sets filter options
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
     * Sets the pattern option
     *
     * @param  string $pattern
     * @return self Provides a fluent interface
     */
    public function setPattern($pattern)
    {
        $this->options['pattern'] = $pattern;
        return $this;
    }

    /**
     * Returns the pattern option
     *
     * @return string|null
     */
    public function getPattern()
    {
        return $this->options['pattern'];
    }

    /**
     * Defined by Zend\Filter\FilterInterface
     *
     * Converts the array produced by the Zend\Form\Element\DateSelect
     * to a string according to the configured pattern. Returns null if the
     * value is no array or not all date parts are set.
     *
     * @param mixed $value
     * @return string|null
     */
    public function filter($value)
    {
        if (!is_array($value) || empty($value['year']) || empty($value['month'])
            || empty($value['day']))
        {
            return null;
        }

        return str_replace(
            array('Y', 'm', 'd'),
            array($value['year'], $value['month'], $value['day']),
            $this->getPattern()
        );
    }
}
