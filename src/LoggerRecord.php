<?php

namespace queasy\log;

use Psr\Log\LoggerInterface;

use queasy\config\ConfigInterface;

class LoggerRecord
{
    private $logger;
    private $config;

    public function __construct(LoggerInterface $logger, ConfigInterface $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function logger()
    {
        return $this->logger;
    }

    public function config()
    {
        return $this->config;
    }
}

