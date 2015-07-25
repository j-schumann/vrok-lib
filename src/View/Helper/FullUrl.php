<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Helper that constructs the full application url to use for links including
 * the schema + domain.
 */
class FullUrl extends AbstractHelper
{
    /**
     * The server/domain name including TLD, e.g.:
     * www.domain.tld.
     *
     * @var string
     */
    protected $fullUrl = '';

    /**
     * Class constructor - stores the hard dependency.
     *
     * @param string $fullUrl
     */
    public function __construct($fullUrl)
    {
        $this->fullUrl = $fullUrl;
    }

    /**
     * Returns the applications full URL as configured in the config.
     * (Working with the console, e.g. for cron jobs).
     *
     * @param string $schema (optional) schema to use. If not set a protocol
     *                       relative url will be returned
     *
     * @return string
     */
    public function __invoke($schema = null)
    {
        return ($schema ? $schema.':' : '').'//'.$this->fullUrl;
    }
}
