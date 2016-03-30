<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\File\Writer;

use Vrok\File\Csv as CsvFile;
use Vrok\File\Exception;

/**
 * Allows to write a CSV file of the given data.
 */
class Csv extends CsvFile
{
    /**
     * Holds the data to write.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Allows to specify a mapping of indexes to csv columns.
     * map = [1 => 'Column A', 'timestamp' => 'Column B'].
     *
     * Columns not found in the map keep their indizes
     *
     * @var array
     */
    protected $map = [];

    /**
     * Indicates if only columns defined in the map will be returned.
     * Columns that are not within the map will be skipped if this is true.
     *
     * @var bool
     */
    protected $mappedOnly = false;

    /**
     * Indicates if a header line is written or not.
     *
     * @var bool
     */
    protected $writeHeader = false;

    // @todo toOutput func
    // @todo toFile func

    /**
     * Retrieve the complete CSV data as string.
     *
     * @return string
     */
    public function toString()
    {
        $handle = fopen('php://memory', 'r+');
        $this->toFileDescriptor($handle);

        rewind($handle);

        return stream_get_contents($handle);
    }

    /**
     * Writes the CSV data to the given file handle, could be php://output,
     * php://memory or a file on disk etc.
     *
     * @param resource $resource
     *
     * @return bool
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function toFileDescriptor($resource)
    {
        // @todo use Guard
        if (!is_resource($resource)) {
            throw new Exception\InvalidArgumentException('$resource is not valid!');
        }

        $header = $this->getHeader();
        if (!count($header)) {
            return false;
        }

        if ($this->writeHeader) {
            fputcsv($resource, $header, $this->delimiter, $this->enclosure);
        }

        foreach ($this->data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $line = [];
            foreach ($header as $index => $column) {
                $line[] = isset($row[$index])
                    ? $row[$index]
                    : null;
            }

            fputcsv($resource, $line, $this->delimiter, $this->enclosure);
        }

        return true;
    }

    /**
     * Creates the header to use for this CSV.
     * If only mapped values from the data array should be used the header
     * equals the map, else all fields from the first data row are used (and
     * eventually mapped).
     * There will be exactly this many columns in the CSV as this header / the
     * first element contains,
     * rows with missing elements will be filled with NULL, additional elements
     * will be omitted.
     *
     * @return array
     */
    public function getHeader()
    {
        if ($this->mappedOnly) {
            return $this->map;
        }

        if (!count($this->data)) {
            return [];
        }

        if (!is_array($this->data[0])) {
            return [];
        }

        $header = [];
        foreach ($this->data[0] as $column => $value) {
            if (isset($this->map[$column])) {
                $header[$column] = $this->map[$column];
            } else {
                $header[$column] = $column;
            }
        }

        return $header;
    }

    /**
     * Sets the data that should be written.
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Allows to inject a map. Only necessary before calling write().
     *
     * @param array $map
     */
    public function setMap(array $map)
    {
        $this->map = $map;
    }

    /**
     * Allows to define if only mapped values will be written.
     * Default is false.
     *
     * @param bool $mappedOnly
     */
    public function setMappedOnly($mappedOnly = false)
    {
        $this->mappedOnly = $mappedOnly;
    }

    /**
     * Allows to define if a header line is written or not.
     * Default is false.
     *
     * @param bool $writeHeader
     */
    public function setWriteHeader($writeHeader = false)
    {
        $this->writeHeader = $writeHeader;
    }
}
