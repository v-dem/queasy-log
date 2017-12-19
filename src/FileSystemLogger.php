<?php

namespace queasy\log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use queasy\config\ConfigInterface;

class FileSystemLogger extends LoggerAggregate
{
    const DEFAULT_PATH = 'logs/debug.log';

    private $path;

    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);

        $this->path = $config->get('path', self::DEFAULT_PATH);
    }

    public function log($level, $message, array $context = array())
    {
        parent::log($level, $message, $context);

        $preparedMessage = $this->prepareMessage($level, $message, $context);

        file_put_contents($this->path, $preparedMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

