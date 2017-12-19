<?php

namespace queasy\log;

use Psr\Log\LogLevel;

use queasy\config\ConfigInterface;

class ConsoleLogger extends LoggerAggregate
{
    const DEFAULT_MESSAGE_FORMAT = '[%5$s] %6$s %7$s';

    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
    }

    public function log($level, $message, array $context = array())
    {
        parent::log($level, $message, $context);

        $prepend = '';
        $append = '';
        switch ($level) {
            case LogLevel::INFO:
            case LogLevel::NOTICE:
                $prepend = "\033[32m";
                break;
            case LogLevel::WARNING:
                $prepend = "\033[33m";
                break;
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
                $prepend = "\033[31m";
                break;
            case LogLevel::EMERGENCY:
                $prepend = "\033[37;41;1m";
        }

        if ($prepend) {
            $append = "\033[m";
        }

        $preparedMessage = $prepend . $this->prepareMessage($level, $message, $context) . $append;

        echo $preparedMessage . PHP_EOL;
    }
}

