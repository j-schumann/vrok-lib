<?php

/*
 *  @copyright   (c) 2014-2015, Vrok
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 *  @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Hydrator\Strategy;

use DateTime;
use DateTimeZone;
use Zend\Hydrator\Strategy\StrategyInterface;

/**
 * Invert Zend's strategy: When saving, the Datetime is automatically converted
 * to UTC in the database, no need to set the timezone there. But when loading
 * form the database we want to show the date in original/custom timezone again.
 */
class DateTimeFormatterStrategy implements StrategyInterface
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @var DateTimeZone|null
     */
    protected $timezone;

    /**
     * Constructor
     *
     * @param string            $format
     * @param DateTimeZone|null $timezone
     */
    public function __construct($format = DateTime::RFC3339, DateTimeZone $timezone = null)
    {
        $this->format   = (string) $format;
        $this->timezone = $timezone;
    }

    /**
     * {@inheritDoc}
     *
     * Converts to date time string
     *
     * @param mixed|DateTime $value
     *
     * @return mixed|string
     */
    public function extract($value)
    {
        if ($value instanceof DateTime && $this->timezone) {
            return $value->setTimezone($this->timezone)->format($this->format);
        }

        return $value;
    }

    /**
     * Converts date time string to DateTime instance for injecting to object
     *
     * {@inheritDoc}
     *
     * @param mixed|string $value
     *
     * @return mixed|DateTime
     */
    public function hydrate($value)
    {
        if (empty($value)) {
            return null;
        }

        // Der DoctrineObject Hydrator konvertiert schon selbst bevor die
        // strategies angewendet werden in handleTypeConversions().
        // Bleibt nur zu hoffen dass nie ein Format verwendet wird dass er nicht
        // automatisch erkennt, denn dann fällt er dort nicht auf den String
        // zurück sonder gibt NULL aus ...
        if ($value instanceof DateTime) {
            return $value;
        }

        $hydrated = DateTime::createFromFormat($this->format, $value);

        return $hydrated ?: $value;
    }
}
