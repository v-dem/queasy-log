<?php

namespace queasy\log\tests;

use Psr\Log\LogLevel;

use queasy\log\Logger;

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testLevel2intDebug()
    {
        $this->assertEquals(Logger::level2int(LogLevel::DEBUG), 0);
    }

    public function testLevel2intInfo()
    {
        $this->assertEquals(Logger::level2int(LogLevel::INFO), 1);
    }

    public function testLevel2intNotice()
    {
        $this->assertEquals(Logger::level2int(LogLevel::NOTICE), 2);
    }

    public function testLevel2intWarning()
    {
        $this->assertEquals(Logger::level2int(LogLevel::WARNING), 3);
    }

    public function testLevel2intError()
    {
        $this->assertEquals(Logger::level2int(LogLevel::ERROR), 4);
    }

    public function testLevel2intCritical()
    {
        $this->assertEquals(Logger::level2int(LogLevel::CRITICAL), 5);
    }

    public function testLevel2intAlert()
    {
        $this->assertEquals(Logger::level2int(LogLevel::ALERT), 6);
    }

    public function testLevel2intEmergency()
    {
        $this->assertEquals(Logger::level2int(LogLevel::EMERGENCY), 7);
    }

    public function testLevel2intDummy()
    {
        $this->assertEquals(Logger::level2int('Dummy'), 0);
    }
}

