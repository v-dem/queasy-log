<?php

namespace queasy\log;

use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{

    public function testLevel2int()
    {
        $this->assertEquals(Logger::level2int(LogLevel::ALERT), 6);
        $this->assertEquals(Logger::level2int('Dummy'), 0);
    }
}
