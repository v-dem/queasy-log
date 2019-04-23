<?php

namespace queasy\log\tests;

use Psr\Log\LogLevel;

use queasy\log\Logger;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testLevel2intAlert()
    {
        $this->assertEquals(Logger::level2int(LogLevel::ALERT), 6);
    }

    public function testLevel2intDummy()
    {
        $this->assertEquals(Logger::level2int('Dummy'), 0);
    }
}

