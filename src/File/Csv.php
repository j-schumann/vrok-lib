<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\File;

/**
 * Holds definitions for reading and writing CSV files.
 */
class Csv
{
    /**
     * Name & path of the CSV.
     *
     * @var string
     */
    protected $filename = '';

    /**
     * The file handle of the CSV.
     *
     * @var resource
     */
    protected $file = null;

    /**
     * Field separator.
     *
     * @var string
     */
    protected $delimiter = ';';

    /**
     * Field enclosure character.
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * Escape character for chars that are the same as the enclosure or delimiter.
     *
     * @var string
     */
    protected $escape = '\\';

    /**
     * Indicates if the first line contains the column headers.
     *
     * @var bool
     */
    protected $hasHeader = true;

    /**
     * Holds the column headers.
     *
     * @var array
     */
    protected $header = [];

    /**
     * Allows injection of a new delimiter char.
     * If called without parameter it resets the delimiter to default.
     *
     * @param string $delimiter
     */
    public function setDelimiter($delimiter = ';')
    {
        $this->delimiter = (string) $delimiter;
    }

    /**
     * Allows injection of a new enclosure char.
     * If called without parameter it resets the enclosure to default.
     *
     * @param string $enclosure
     */
    public function setEnclosure($enclosure = '"')
    {
        $this->enclosure = (string) $enclosure;
    }

    /**
     * Allows injection of a new escape char.
     * If called without parameter it resets the escape to default.
     *
     * @param string $escape
     */
    public function setEscape($escape = '\\')
    {
        $this->escape = (string) $escape;
    }

    /**
     * Allows to define if the CSV has column headers in the first line or not.
     * Default is true.
     *
     * @param bool $hasHeader
     */
    public function setHasHeader($hasHeader = true)
    {
        $this->hasHeader = (bool) $hasHeader;
    }
}
