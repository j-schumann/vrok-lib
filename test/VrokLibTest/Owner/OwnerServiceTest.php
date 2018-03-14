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
