<?php

/**
 * @copyright   (c) 2018, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace VrokLibTest\EntityLink;

use PHPUnit\Framework\TestCase;
use VrokLibTest\Bootstrap;
use Vrok\Entity\Group;
use Vrok\Entity\User;
use Vrok\EntityLink\UserStrategy;
use Vrok\Exception;

class UserStrategyTest extends TestCase
{
    private $user = null;
    private $group = null;

    public function setUp()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $em = $serviceManager->get('Doctrine\ORM\EntityManager');
        /* @var $em \Doctrine\ORM\EntityManagerInterface */

        $user = new User();
        $user->setUsername('strategytest-name');
        $user->setEmail('strategytest-email');
        $user->setPassword('strategytest-pw');
        $em->persist($user);
        $em->flush();
        $this->user = $user;

        $group = new Group();
        $group->setName('strategytest-name');
        $em->persist($group);
        $em->flush();
        $this->group = $group;
    }

    public function tearDown()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $em = $serviceManager->get('Doctrine\ORM\EntityManager');
        /* @var $em \Doctrine\ORM\EntityManagerInterface */

        $query = $em->createQuery('DELETE Vrok\Entity\User');
        $query->execute();
        $query2 = $em->createQuery('DELETE Vrok\Entity\Group');
        $query2->execute();
    }

    public function testCanCreateStrategy()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $strategy = $serviceManager->get(UserStrategy::class);
        $this->assertInstanceOf(UserStrategy::class, $strategy);
    }

    public function testGetSearchUrl()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $strategy = $serviceManager->get(UserStrategy::class);
        /* @var $strategy UserStrategy */

        $url = $strategy->getSearchUrl();
        $this->assertEquals('/test-user/search/', $url);
    }

    public function testGetEditUrl()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $strategy = $serviceManager->get(UserStrategy::class);
        /* @var $strategy UserStrategy */

        $url = $strategy->getEditUrl($this->user);
        $this->assertEquals('/test-user/edit/'.$this->user->getId().'/', $url);
    }

    public function testGetEditUrlUserClassIsChecked()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $strategy = $serviceManager->get(UserStrategy::class);
        /* @var $strategy UserStrategy */

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported object for UserStrategy');
        $strategy->getEditUrl($this->group);
    }

    public function testGetPresentation()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $strategy = $serviceManager->get(UserStrategy::class);
        /* @var $strategy UserStrategy */

        $url = $strategy->getPresentation($this->user);
        $this->assertEquals('strategytest-name (strategytest-email)', $url);
    }

    public function testGetPresentationUserClassIsChecked()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $strategy = $serviceManager->get(UserStrategy::class);
        /* @var $strategy UserStrategy */

        $this->expectException(Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported object for UserStrategy');
        $strategy->getPresentation($this->group);
    }
}
