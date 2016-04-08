<?php

namespace VrokLibTest\Owner;

use VrokLibTest\Bootstrap;

class OwnerServiceTest extends \PHPUnit_Framework_TestCase
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
