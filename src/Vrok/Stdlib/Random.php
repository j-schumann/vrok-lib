<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

/**
 * Helper for generating random bytes / numbers, utilizes the operating system
 * Random Number Generator to get cryptographically secure numbers.
 */
class Random
{
    const OUTPUT_HEX   = 'hex';
    const OUTPUT_ALNUM = 'base62';

    /**
     * Returns secure random bytes using the OS random source(s). If multiple
     * sources are available they are mixed using XOR.
     *
     * @param integer $byteCount    the number of bytes to return
     * @param string $outputType    one of the OUTPUT_* constants
     * @return string               a string of bytes
     * @throws Exception\InvalidArgumentException if a unknown $outputType is requested
     */
    public static function getRandomBytes($byteCount, $outputType = null)
    {
        $sources = array();
        foreach(array('getFromOpenSSL', 'getFromMcrypt', 'getFromCOM') as $func) {
            $bytes = self::$func($byteCount);
            if ($bytes) {
                $sources[] = $bytes;
            }
        }

        // /dev/random is blocking, only use it if none of the other sources
        // is available
        if (!count($sources)) {
            $bytes = $this->getFromDev($byteCount);
            if ($bytes) {
                $sources[] = $bytes;
            }
        }

        if (!count($sources)) {
            throw new Exception\RuntimeException (
                'No (secure) random byte source available!'
            );
        }

        // primitive mixing as suggested in
        // http://www.rfc-editor.org/rfc/rfc4086.txt
        $result = array_shift($sources);
        foreach($sources as $source) {
            $result ^= $source;
        }

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
     * @param int $length   string length of the token
     * @param string $type  one of the OUTPUT_* constants
     * @return string
     * @throws Exception\InvalidArgumentException if a unknown $type is requested
     */
    public static function getRandomToken($length, $type = self::OUTPUT_ALNUM)
    {
        $byteCount = $length;

        // the types return more characters than bytes are needed, save entropy!
        switch(strtolower($type)) {
            case self::OUTPUT_HEX:
                $byteCount = $length / 2;
                break;

            case self::OUTPUT_ALNUM:
                // base62 returns ca 1.3 chars / byte, e.g. 10 bytes result
                // in 13 chars of [0-9a-zA-Z]
                $byteCount = ceil($length / 1.3);
                break;

            default:
                throw new Exception\InvalidArgumentException(__METHOD__
                    .' unsupported $type="'.$type.'" requested!');
        }

        $token = '';
        do {
            $token .= self::getRandomBytes($byteCount, $type);
        }
        // re-request bytes if our $length => $byteCount conversion was bad
        while(strlen($token) < $length);

        return substr($token, 0, $length);
    }

    /**
     * Get the requested number of random bytes using Mcrypt.
     *
     * @param int $byteCount    number of bytes to return
     * @return string   the random bytes, null or false on error
     */
    public static function getFromMcrypt($byteCount)
    {
        // Don't use the mcrypt function on http://en.wikipedia.org/wiki/Phalanger
        // as /dev/[u]random is not available
        if (!function_exists('mcrypt_create_iv') || defined('PHALANGER')) {
            return null;
        }
        return mcrypt_create_iv($byteCount, MCRYPT_DEV_URANDOM);
    }

    /**
     * Get the requested number of random bytes.
     *
     * @param int $byteCount    number of bytes to return
     * @return string
     */
    public static function getFromOpenSSL($byteCount)
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            return null;
        }
        // according to the docs "most times" a secure algorithm is used, so we
        // don't check for the second parameter $isSecure to be true
        return openssl_random_pseudo_bytes($byteCount);
    }

    /**
     * Returns the requested number of random bytes from /dev/random.
     * Attention: uses blocking filesystem access.
     *
     * @param int $byteCount    number of bytes to return
     * @return string
     */
    public static function getFromDev($byteCount)
    {
        $value = null;

        $handle = @fopen('/dev/random', 'rb');
        if ($handle) {
            $this->sources[] = fread($this->_unixHandle, $byteCount);
            fclose($handle);
        }

        return $value;
    }

    /**
     * Uses the CryptoAPI / COM to return random bytes.
     *
     * @link http://msdn.microsoft.com/en-us/library/aa388182(VS.85).aspx
     * @param integer $byteCount    number of bytes to return
     * @return string
     */
    public static function getFromCOM($byteCount)
    {
        if (!class_exists('\\COM', 0)) {
            return null;
        }

        $comObject = new \COM('CAPICOM.Utilities.1');
        // second parameter to GetRandom sets BASE64 output, binary doesn't work
        return base64_decode($comObject->GetRandom($byteCount, 0));
    }

    /**
     * Return pseudorandom bytes by using Mersenne Twister.
     *
     * @param integer $byteCount    the number of bytes to return
     * @return string               a string of bytes
     */
    public static function getPseudoRandomBytes($byteCount)
    {
        $bytes = '';
        for ($i = 0; $i < $byteCount; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        return $bytes;
    }
}
