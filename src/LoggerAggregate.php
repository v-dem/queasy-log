<?php

namespace queasy\log;

use Psr\Log\InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use queasy\config\ConfigInterface;

class LoggerAggregate implements AbstractLogger
{

    const DEFAULT_MIN_LEVEL = 'debug';
    const DEFAULT_MAX_LEVEL = 'emergency';
    const DEFAULT_TIME_FORMAT = 'Y-m-d H:i:s.u T';
    const DEFAULT_MESSAGE_FORMAT = '%1$s %2$s [%3$s] %4$s [%5$s] %6$s';

    public static function level2int($level)
    {
        switch (strtolower($level)) {
            case LogLevel::DEBUG:
                return 0;
            case LogLevel::INFO:
                return 1;
            case LogLevel::NOTICE:
                return 2;
            case LogLevel::WARNING:
                return 3;
            case LogLevel::ERROR:
                return 4;
            case LogLevel::CRITICAL:
                return 5;
            case LogLevel::ALERT:
                return 6;
            case LogLevel::EMERGENCY:
                return 7;
            default:
                return 0;
        }
    }

    private $minLevel;
    private $maxLevel;
    private $processName;
    private $timeFormat;
    private $messageFormat;

    private $loggers;

    public function __construct(ConfigInterface $config)
    {
        $this->minLevel = $config->get('minLevel', self::DEFAULT_MIN_LEVEL);
        $this->maxLevel = $config->get('maxLevel', self::DEFAULT_MAX_LEVEL);
        $this->processName = $config->processName;
        $this->timeFormat = $config->get('timeFormat', self::DEFAULT_TIME_FORMAT);
        $this->messageFormat = $config->get('messageFormat', self::DEFAULT_MESSAGE_FORMAT);

        $this->loggers = array();

        foreach ($config as $section) {
            if (($section instanceof ConfigInterface)
                    && isset($section['logger'])) {
                $className = $section['logger'];
                $interfaces = class_implements($className);
                if (!$interfaces || !isset($interfaces['Psr\Log\LoggerInterface'])) {
                    throw new InvalidArgumentException(sprintf('Logger class "%s" does not implement Psr\Log\LoggerInterface.', $className));
                }

                $logger = new $className($section);

                $this->loggers[] = new LoggerRecord($logger, $section);
            }
        }
    }

    public function minLevel()
    {
        return $this->minLevel;
    }

    public function maxLevel()
    {
        return $this->maxLevel;
    }

    public function processName()
    {
        return $this->processName;
    }

    public function timeFormat()
    {
        return $this->timeFormat;
    }

    public function messageFormat()
    {
        return $this->messageFormat;
    }

    public function log($level, $message, array $context = array())
    {
        foreach ($this->loggers as $loggerRecord) {
            $config = $loggerRecord->config();
            if ((self::level2int($level) >= self::level2int($config->minLevel))
                    && (self::level2int($level) <= self::level2int($config->maxLevel))) {
                $logger = $loggerRecord->logger();

                $logger->log($level, $message, $context);
            }
        }
    }

    protected function getTimeString()
    {
        $uTimestamp = microtime(true);
        $timestamp = floor($uTimestamp);
        $milliseconds = '' . round(($uTimestamp - $timestamp) * 1000000);
        $milliseconds = str_pad($milliseconds, 6, '0');

        return date(preg_replace('/(?<!\\\\)u/', $milliseconds, $this->timeFormat()), $timestamp);
    }

}

