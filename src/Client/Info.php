<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Client;

class Info
{
    /**
     * Returns if the current request is using SSL.
     *
     * @return bool
     */
    public function isSslRequest()
    {
        // non-empty value for all but IIS if SSL, 'off' on IIS if not SSL
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Nginx
        if (isset($_SERVER['HTTP_SCHEME'])
                && ($_SERVER['HTTP_SCHEME'] === 'https')) {
            return true;
        }

        // Fallback
        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
            return true;
        }

        // Behind a loadbalancer, e.g. nginx?
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Returns if the current request is made from the CLI.
     *
     * @return bool
     */
    public function isConsoleRequest()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Returns the clients IP address, checks for forward header.
     * If no IP found returns the sapi_name to allow checking for CLI scripts.
     *
     * @return string
     */
    public function getIp()
    {
        $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? $_SERVER['HTTP_X_FORWARDED_FOR']
            : (isset($_SERVER['REMOTE_ADDR'])
                ? $_SERVER['REMOTE_ADDR']
                : php_sapi_name());

        // limit to 39 chars (IPv6), to avoid db field overflow by proxies
        // which set the forwarded_for header to an ip-chain
        return substr($ip, 0, 39);
    }
}
