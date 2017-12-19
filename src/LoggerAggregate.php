<?php

namespace queasy\log;

use Exception;
use InvalidArgumentException as StandardInvalidArgumentException;

use Psr\Log\InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use queasy\config\ConfigInterface;

class LoggerAggregate extends AbstractLogger
{
    const DEFAULT_MIN_LEVEL = LogLevel::DEBUG;
    const DEFAULT_MAX_LEVEL = LogLevel::EMERGENCY;
    const DEFAULT_TIME_FORMAT = 'Y-m-d H:i:s.u T';
    const DEFAULT_MESSAGE_FORMAT = '%1$s %2$s [%3$s] [%4$s] [%5$s] %6$s %7$s'; // 1) time, 2) process name, 3) session id, 4) ip address, 5) log level, 6) message, 7) context

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
        $this->messageFormat = $config->get('messageFormat', static::DEFAULT_MESSAGE_FORMAT);

        $this->loggers = array();

        foreach ($config as $section) {
            if (($section instanceof ConfigInterface)
                    && isset($section['logger'])) {
                $className = $section['logger'];
                if (!class_exists($className)) {
                    throw new InvalidArgumentException(sprintf('Logger class "%s" does not exist.', $className));
                }

                $interfaces = class_implements($className);
                if (!$interfaces || !isset($interfaces['Psr\Log\LoggerInterface'])) {
                    throw new InvalidArgumentException(sprintf('Logger class "%s" does not implement Psr\Log\LoggerInterface.', $className));
                }

                $this->loggers[] = new $className($section);
            }
        }
    }

    public function log($level, $message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            if ((self::level2int($level) >= self::level2int($logger->minLevel()))
                    && (self::level2int($level) <= self::level2int($logger->maxLevel()))) {
                $logger->log($level, $message, $context);
            }
        }
    }

    protected function minLevel()
    {
        return $this->minLevel;
    }

    protected function maxLevel()
    {
        return $this->maxLevel;
    }

    protected function processName()
    {
        return $this->processName;
    }

    protected function timeFormat()
    {
        return $this->timeFormat;
    }

    protected function messageFormat()
    {
        return $this->messageFormat;
    }

    protected function logTime()
    {
        $uTimestamp = microtime(true);
        $timestamp = floor($uTimestamp);
        $milliseconds = '' . round(($uTimestamp - $timestamp) * 1000000);
        $milliseconds = str_pad($milliseconds, 6, '0');

        return date(preg_replace('/(?<!\\\\)u/', $milliseconds, $this->timeFormat()), $timestamp);
    }

    protected function sessionId()
    {
        return session_id();
    }

    protected function ipAddress()
    {
        if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    protected function logLevel($level)
    {
        return strtoupper($level);
    }

    protected function message($message)
    {
        return $message;
    }

    protected function context(array $context = null)
    {
        if (is_null($context)) {
            return '';
        }

        $result = '';
        $ex = null;
        if (isset($context['exception'])) {
            $ex = $context['exception'];

            if (interface_exists('\Throwable') && is_subclass_of($ex, '\Throwable')
                    || ($ex instanceof Exception)) {
                $result .= get_class($ex) . ': ' . $ex->getMessage() . ' in ' . $ex->getFile() . '(' . $ex->getLine() . ')' . PHP_EOL;
                $result .= 'Stack trace:' . PHP_EOL;
                $result .= $ex->getTraceAsString() . PHP_EOL;

                unset($context['exception']);
            } else {
                throw new StandardInvalidArgumentException('Value of \'exception\' key in $context does not contain valid Throwable or Exception instance.');
            }
        }

        return (count($context)? PHP_EOL . print_r($context, true): '') . $result;
    }

    protected function prepareMessage($level, $message, array $context = array())
    {
        return trim(sprintf(
            $this->messageFormat(),
            $this->logTime(),
            $this->processName(),
            $this->sessionId(),
            $this->ipAddress(),
            $this->logLevel($level),
            $this->message($message),
            $this->context($context)
        ));
    }
}

