<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\File\Parser;

use Vrok\File\Csv as CsvFile;
use Vrok\File\Exception;
use Vrok\Stdlib\StringUtils;

/**
 * Allows to parse a CSV file and map csv columns to db table columns.
 */
class Csv extends CsvFile
{
    /**
     * Indicates if only columns defined in the map will be returned.
     * Columns that are not within the map will be skipped if this is true.
     *
     * @var bool
     */
    protected $mappedOnly = false;

    /**
     * Allows to specify a mapping of columns to new indexes.
     * A) map = [1 => 'Column A', 2 => 'Column B']
     *    if no csv headers are given.
     *
     * B) map = ['csvhead1' => 'Important Column', 'csvhead2' => 'Additional Column']
     *    if the csv headers should be renamed
     *
     * Columns not found in the map keep their indizes / old headers
     *
     * @var array
     */
    protected $map = [];

    /**
     * Holds the parsed data with the final header as indexes.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Class constructor, stores the filename and checks the file.
     *
     * @param string $filename
     *
     * @throws Exception\InvalidArgumentException when the filename is invalid/empty
     * @throws Exception\RuntimeException         when the file does not exist / is not readable
     */
    public function __construct($filename)
    {
        if (!is_string($filename) || !strlen($filename)) {
            throw new Exception\InvalidArgumentException(
                "Given file name is invalid or empty: '$filename'");
        }

        if (!file_exists($filename) || !is_readable($filename)) {
            throw new Exception\RuntimeException(
                "CSV file for parsing doesn't exist or is not readable: '$filename'");
        }

        $this->filename = $filename;
    }

    /**
     * Unsets the internal data array to free memory.
     */
    public function __destruct()
    {
        unset($this->data);
    }

    /**
     * Parses the file, stores the data.
     */
    public function parse()
    {
        $this->file = fopen($this->filename, 'r');
        $lineCount  = 0;

        while (($line = fgetcsv($this->file, 0, $this->delimiter, $this->enclosure,
            $this->escape)) !== false) {
            if ($lineCount++ == 0) {
                $line[0] = StringUtils::removeBOM($line[0]);
                $this->setHeader($line);

                if ($this->hasHeader) {
                    continue;
                }
            }

            $this->parseLine($line);
        }

        fclose($this->file);
    }

    /**
     * Returns the parsed data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the header to use for data assignment, applies mapping.
     *
     * @param array $line
     */
    protected function setHeader(array $line = null)
    {
        // The CSV file has no header => simply use the map as header
        if (!$this->hasHeader || !$line) {
            $this->header = $this->map;

            return;
        }

        $this->header = $line;

        // CSV Header set but no map found => all done
        if (!count($this->map)) {
            return;
        }

        // walk through each header field, if the value is in the map replace it
        foreach ($this->header as $index => $column) {
            if (isset($this->map[$column])) {
                $this->header[$index] = $this->map[$column];
            }
        }
    }

    /**
     * Stores a single line in the data, headers/mapping is applied.
     *
     * @param array $line
     */
    protected function parseLine($line)
    {
        $row = [];
        foreach ($line as $column => $value) {
            // the column index is set in the header -> use the new index, else keep
            $index = isset($this->header[$column]) ? $this->header[$column] : $index;

            // if we only use mapped values we check if the assigned index is also
            // within the map, else we skip this column
            if ($this->mappedOnly && !in_array($index, $this->map)) {
                continue;
            }

            $row[$index] = $value;
        }

        $this->data[] = $row;
    }

    /**
     * Allows to inject a map. Only necessary before calling parse().
     *
     * @param array $map
     */
    public function setMap(array $map)
    {
        $this->map = $map;
    }

    /**
     * Allows to define if only mapped values will be returned.
     * Default is false.
     *
     * @param bool $mappedOnly
     */
    public function setMappedOnly($mappedOnly = false)
    {
        $this->mappedOnly = $mappedOnly;
    }
}
