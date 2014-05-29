<?php

namespace VrokLibTest\Controller;

use VrokLibTest\Bootstrap;

class OwnerServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $ownerService = null;

    protected function setUp()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $this->ownerService = $serviceManager->get('Vrok\Owner\OwnerService');
    }

    public function testServiceShortNameIsAvailable()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $service = $serviceManager->get('OwnerService');
        $this->assertInstanceOf('\Vrok\Owner\OwnerService', $service);
    }

    public function testStrategyEventIsTriggered()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $em = $serviceManager->get('EventManager');
        $sm = $em->getSharedManager();
        $triggered = false;
        $sm->attach('Vrok\Owner\OwnerService', 'getOwnerStrategy', function() use(&$triggered) {
            $triggered = true;
        });

        $this->assertTrue($triggered);
    }

    public function testUserStrategyIsAvailable()
    {
        $strategy = $this->ownerService->getOwnerStrategy('\Vrok\Entity\User');
        $this->assertInstanceOf('\Vrok\Owner\UserStrategy', $strategy);
    }
}
