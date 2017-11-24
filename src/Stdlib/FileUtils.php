<?php

/**
 * @copyright   (c) 2017, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

use Exception\RuntimeException;

/**
 * Utility class for handling of files.
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class FileUtils
{
    /**
     * Tries to open the file with the given name.
     *
     * @param string $filename  the files name including path
     * @param bool $write       (optional) wether the file handle is writable or not
     * @param bool $append      (optional) if writeable set pointer the beginning or end
     *     of the file
     * @return resource         file handle or false on error
     */
    public static function open(string $filename, bool $write = true, bool $append = false)
    {
        $mode = $write
            ? $append
                ? 'a+'
                : 'w+'
            : 'r';

        return fopen($filename, $mode);
    }

    /**
     * Deletes the given filesystem path, whether it's a directory or a file.
     * Directories are deleted recursively.
     *
     * @param string $name  the path to delete
     *
     * @return bool     true when the file/dir was deleted, else false
     * @throws RuntimeException when no filename was given
     */
    public static function delete(string $name) : bool
    {
        if (empty($name)) {
            throw new RuntimeException('File/directory name cannot be empty!');
        }

        if (! file_exists($name)) {
            return true;
        }

        if (is_dir($name)) {
            $objects = scandir($name);
            foreach ($objects as $object) {
                if ($object == '.' || $object == '..') {
                    continue;
                }
                self::delete($name . DIRECTORY_SEPARATOR . $object);
            }

            rmdir($name);
        } else {
            unlink($name);
        }

        return ! file_exists($name);
    }
}
