#!/usr/bin/php -d memory_limit=512M
<?php
function countLinesInFile($fileInfo)
{
    return count(file($fileInfo));
}

function countLinesInDir($directory, $filePattern)
{
    $total = 0;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach($iterator as $fileInfo) {
        if (-1 < preg_match($filePattern, $fileInfo->getFileName())) {
            $total += countLinesInFile($fileInfo);
        }
    }
    return $total;
}

function usage($argv)
{
    printf("usage: php -q %s <directory> <filematch>\n", reset($argv));

    printf(" - directory: path to the root directory of a project.\n");
    printf(" - filematch: regex pattern for files to include.\n");

    return 1;
}

if (count($argv) < 3)
{
    die(usage($argv));
}

printf("%d\n", countLinesInDir($argv[1], $argv[2]));
