<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Allows to translate messages directly from the controller.
 */
class Translate extends AbstractPlugin
{
    /**
     * Proxies to the translate() view helper.
     *
     * @param  string $message
     * @param  string $textDomain
     * @param  string $locale
     * @return string
     */
    public function __invoke($message, $textDomain = null, $locale = null)
    {
        // @todo replace view helper with the translator itself?
        $translateHelper = $this->getController()->getServiceLocator()
                ->get('viewhelpermanager')->get('translate');
        return $translateHelper($message, $textDomain, $locale);
    }
}
