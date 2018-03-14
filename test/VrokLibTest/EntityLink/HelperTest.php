<?php

/**
 * @copyright   (c) 2018, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace VrokLibTest\EntityLink;

use PHPUnit\Framework\TestCase;
use VrokLibTest\Bootstrap;
use Vrok\Entity\User;
use Vrok\EntityLink\Helper;
use Vrok\EntityLink\UserStrategy;
use Vrok\Exception;

class HelperTest extends TestCase
{
    private $user = null;

    public function setUp()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $em = $serviceManager->get('Doctrine\ORM\EntityManager');
        /* @var $em \Doctrine\ORM\EntityManagerInterface */

        $user = new User();
        $user->setUsername('helpertest-name');
        $user->setEmail('helpertest-email');
        $user->setPassword('helpertest-pw');
        $em->persist($user);
        $em->flush();

        $this->user = $user;
    }

    public function tearDown()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $em = $serviceManager->get('Doctrine\ORM\EntityManager');
        /* @var $em \Doctrine\ORM\EntityManagerInterface */

        $query = $em->createQuery('DELETE Vrok\Entity\User');
        $query->execute();
    }

    public function testCanCreateHelper()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        $this->assertInstanceOf(Helper::class, $helper);
    }

    public function testGetStrategyConfig()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $config = $helper->getStrategyConfig();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey(
            'Vrok\EntityLink\UserStrategy',
            $config
        );
        $this->assertArraySubset(
            ['Vrok\Entity\User'],
            $config['Vrok\EntityLink\UserStrategy']
        );
    }

    public function testGetStrategy()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $strategy = $helper->getStrategy(User::class);
        $this->assertInstanceOf(UserStrategy::class, $strategy);

        // now cached
        $strategy2 = $helper->getStrategy(User::class);
        $this->assertInstanceOf(UserStrategy::class, $strategy2);
    }

    public function testGetUnknownStrategyReturnsNull()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $strategy = $helper->getStrategy(\Vrok\Entity\LoginKey::class);
        $this->assertNull($strategy);
    }

    public function testGetUnknownStrategyThrowsException()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('No EntityLinkstrategy found');
        $helper->getStrategy(\Vrok\Entity\LoginKey::class, true);
    }

    public function testGetStrategyNonexistantClass()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Class NotExisting does not exist!');
        $helper->getStrategy("NotExisting", true);
    }

    public function testGetStrategyNonexistantService()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $helper->addStrategy('UnknownService', [\Vrok\Entity\LoginKey::class]);
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Strategy service Vrok\Entity\LoginKey is unknown!');
        $helper->getStrategy(\Vrok\Entity\LoginKey::class, true);
    }

    public function testGetStrategyInvalidService()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $helper->addStrategy(\Vrok\Service\UserManager::class, [\Vrok\Entity\ObjectMeta::class]);
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('could not be fetched or does not implement the StrategyInterface');
        $helper->getStrategy(\Vrok\Entity\ObjectMeta::class, true);
    }

    public function testAddStrategy()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $helper->addStrategy('Fake\Strategy', ['Fake\Entity']);

        $config = $helper->getStrategyConfig();
        $this->assertArrayHasKey('Fake\Strategy', $config);
        $this->assertArraySubset(['Fake\Entity'], $config['Fake\Strategy']);
    }

    public function testAddStrategies()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $helper->addStrategies([
            'Fake\A' => ['Fake\AEntity'],
            'Fake\B' => ['Fake\BEntity'],
        ]);

        $config = $helper->getStrategyConfig();
        $this->assertArrayHasKey('Fake\A', $config);
        $this->assertArrayHasKey('Fake\B', $config);
        $this->assertArraySubset(['Fake\AEntity'], $config['Fake\A']);
        $this->assertArraySubset(['Fake\BEntity'], $config['Fake\B']);
    }

    public function testGetSearchUrl()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $url = $helper->getSearchUrl(User::class);
        $this->assertEquals('/test-user/search/', $url);
    }

    public function testGetEditUrl()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $url = $helper->getEditUrl($this->user);
        $this->assertEquals('/test-user/edit/'.$this->user->getId().'/', $url);
    }

    public function testGetPresentation()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $url = $helper->getPresentation($this->user);
        $this->assertEquals('helpertest-name (helpertest-email)', $url);
    }

    /**
     * MUST BE LAST TEST - overwrites the config
     */
    public function testSetOptions()
    {
        $serviceManager = Bootstrap::getServiceManager();
        $helper = $serviceManager->get(Helper::class);
        /* @var $helper Helper */

        $helper->setOptions([
            'strategies' => [
                'Fake\C' => ['Fake\CEntity']
            ]
        ]);

        $config = $helper->getStrategyConfig();
        $this->assertArrayHasKey('Fake\C', $config);
        $this->assertArraySubset(['Fake\CEntity'], $config['Fake\C']);
    }
}
