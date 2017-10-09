<?php

/**
 * @copyright   (c) 2017, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace VrokLibTest\Owner;

use PHPUnit\Framework\TestCase;
use VrokLibTest\Bootstrap;

class OwnerServiceTest extends TestCase
{
    protected $ownerService = null;

    protected function setUp()
    {
        // ensure Module::onBootstrap is called
        \Zend\Mvc\Application::init(Bootstrap::getConfig());

        $serviceManager     = Bootstrap::getServiceManager();
        $this->ownerService = $serviceManager->get('Vrok\Service\Owner');
    }

    /**
     * @todo funktioniert nicht weil slmQueue benötigt wird aber nicht in der
     * composer.json enthalten ist -> auslagern in neues modul und dort den test
     * wieder aktivieren
     * @group disable
     */
    public function testStrategyEventIsTriggered()
    {
        $em        = $this->ownerService->getEventManager();
        $triggered = false;
        $em->attach('getOwnerStrategy', function () use (&$triggered) {
            $triggered = true;
        });
        $this->ownerService->getOwnerStrategy(__CLASS__);

        $this->assertTrue($triggered);
    }
}
