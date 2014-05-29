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

    public function testUserStrategyIsAvailable()
    {
        $strategy = $this->ownerService->getOwnerStrategy('\Vrok\Entity\User');
        $this->assertInstanceOf('\Vrok\Owner\UserStrategy', $strategy);
    }
}
