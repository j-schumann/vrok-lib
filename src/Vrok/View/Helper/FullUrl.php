<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper that constructs the full application url to use for links including
 * the schema + domain.
 */
class FullUrl extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Returns the applications full URL as configured in the config.
     * (Working with the console, e.g. for cron jobs)
     *
     * @param string $schema    (optional) schema to use. If not set a protocol
     *     relative url will be returned
     * @return string
     * @throws \Vrok\View\Exception\RuntimeException
     */
    public function __invoke($schema = null)
    {
        $serviceManager = $this->getServiceLocator() // helperPluginManager
                ->getServiceLocator(); // serviceManager

        $config = $serviceManager->get('Config');

        if (!isset($config['general']) || !isset($config['general']['fullUrl'])) {
            throw new \Vrok\View\Exception\RuntimeException(
                '"fullUrl" is not set in the [general] config!');
        }

        return ($schema ? $schema.':' : '').'//'.$config['general']['fullUrl'];
    }
}
