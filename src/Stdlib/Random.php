<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

/**
 * Helper for generating random bytes / numbers, utilizes the RandomLib to
 * use multiple sources and mix them.
 */
class Random
{
    const OUTPUT_HEX   = 'hex';
    const OUTPUT_ALNUM = 'base62';

    /**
     * Returns secure random bytes using the OS random source(s). If multiple
     * sources are available they are mixed using XOR.
     *
     * @param int    $byteCount  the number of bytes to return
     * @param string $outputType one of the OUTPUT_* constants
     *
     * @return string a string of bytes
     *
     * @throws Exception\InvalidArgumentException if a unknown $outputType is requested
     */
    public static function getRandomBytes($byteCount, $outputType = null)
    {
        $result = random_bytes($byteCount);

        switch (strtolower($outputType)) {
            case null:
                return $result;

            case self::OUTPUT_HEX:
                return bin2hex($result);

            case self::OUTPUT_ALNUM:
                $bigint = \Zend\Math\BigInteger\BigInteger::getDefaultAdapter();

                return $bigint->baseConvert(bin2hex($result), 16, 62);

            default:
                throw new Exception\InvalidArgumentException(__METHOD__
                    .' unsupported $type="'.$outputType.'" requested!');
        }
    }

    /**
     * Returns a random token of the given length.
     *
     * @param int    $length string length of the token
     * @param string $type   one of the OUTPUT_* constants
     *
     * @return string
     *
     * @throws Exception\InvalidArgumentException if a unknown $type is requested
     */
    public static function getRandomToken($length, $type = self::OUTPUT_ALNUM)
    {
        $byteCount = $length;

        // the types return more characters than bytes are needed, save entropy!
        switch (strtolower($type)) {
            case self::OUTPUT_HEX:
                $byteCount = ceil($length / 2);
                break;

            case self::OUTPUT_ALNUM:
                // base62 returns ~1.3 chars per byte, e.g. 10 bytes result
                // in 13 chars of [0-9a-zA-Z]
                $byteCount = ceil($length / 1.3);
                break;

            default:
                throw new Exception\InvalidArgumentException(__METHOD__
                    .' unsupported $type="'.$type.'" requested!');
        }

        $token = '';

        // re-request bytes if our $length => $byteCount conversion was bad
        do {
            $token .= self::getRandomBytes($byteCount, $type);
        } while (strlen($token) < $length);

        return substr($token, 0, $length);
    }

    /**
     * Return pseudorandom bytes by using Mersenne Twister.
     *
     * @param int $byteCount the number of bytes to return
     *
     * @return string a string of bytes
     */
    public static function getPseudoRandomBytes($byteCount)
    {
        $bytes = '';
        for ($i = 0; $i < $byteCount; ++$i) {
            $bytes .= chr(mt_rand(0, 255));
        }

        return $bytes;
    }
}
