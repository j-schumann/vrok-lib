<?php

namespace VrokLibTest\Stdlib;

use Vrok\Stdlib\Random;

class RandomTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsPseudoRandomBytes()
    {
        $bytes = Random::getPseudoRandomBytes(10);
        $this->assertEquals(strlen($bytes), 10);
    }

    public function testReturnsRandomBytes()
    {
        $bytes = Random::getRandomBytes(10);
        $this->assertEquals(strlen($bytes), 10);
    }

    public function testReturnsHex()
    {
        $bytes = Random::getRandomBytes(10, Random::OUTPUT_HEX);
        $result = preg_match('/^[0-9a-fA-F]+$/', $bytes);
        $this->assertEquals($result, 1);
    }

    public function testReturnsAlnum()
    {
        $bytes = Random::getRandomBytes(10, Random::OUTPUT_ALNUM);
        $result = preg_match('/^[0-9a-zA-Z]+$/', $bytes);
        $this->assertEquals($result, 1);
    }

    public function testReturnsRandomToken()
    {
        $bytes = Random::getRandomToken(10);
        $this->assertEquals(strlen($bytes), 10);
    }

    public function testReturnsHexToken()
    {
        $bytes = Random::getRandomToken(10, Random::OUTPUT_HEX);
        $result = preg_match('/^[0-9a-fA-F]+$/', $bytes);
        $this->assertEquals(strlen($bytes), 10);
        $this->assertEquals($result, 1);
    }

    public function testReturnsAlnumToken()
    {
        $bytes = Random::getRandomToken(10, Random::OUTPUT_ALNUM);
        $result = preg_match('/^[0-9a-zA-Z]+$/', $bytes);
        $this->assertEquals(strlen($bytes), 10);
        $this->assertEquals($result, 1);
    }
}
