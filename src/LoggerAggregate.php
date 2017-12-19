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

    // 1) time, 2) process name, 3) session id, 4) ip address, 5) log level, 6) message, 7) context
    const DEFAULT_MESSAGE_FORMAT = '%1$s %2$s [%3$s] [%4$s] [%5$s] %6$s %7$s';

    /**
     * Translate log level word into an integer value.
     *
     * @param string $level Log level string
     *
     * @return int Integer log level value
     */
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

    /**
     * @var string Min log level for this logger
     */
    private $minLevel;

    /**
     * @var string Max log level
     */
    private $maxLevel;

    /**
     * @var string|null Process name to be displayed in log
     */
    private $processName;

    /**
     * @var string Time format string that is acceptable by date() function
     */
    private $timeFormat;

    /**
     * @var string Message format that is acceptable by sprintf() function, passed parameters are (by order)
     *              1) time string,
     *              2) process name,
     *              3) session id,
     *              4) IP address,
     *              5) log level,
     *              6) message,
     *              7) context
     */
    private $messageFormat;

    /**
     * @var array Subordinated loggers
     */
    private $loggers;

    /**
     * Constructor.
     *
     * @param ConfigInterface $config Logger configuration
     *
     * @throws InvalidArgumentException When a subordinated logger class doesn't exist or doesn't implement Psr\Log\LoggerInterface
     */
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

    /**
     * Aggregator log method, will call all subordinated loggers depending on their min and max log levels.
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array|null $context Context
     */
    public function log($level, $message, array $context = array())
    {
        foreach ($this->loggers() as $logger) {
            if ((self::level2int($level) >= self::level2int($logger->minLevel()))
                    && (self::level2int($level) <= self::level2int($logger->maxLevel()))) {
                $logger->log($level, $message, $context);
            }
        }
    }

    /**
     * Get subordinated loggers.
     *
     * @return array Subordinated loggers
     */
    protected function loggers()
    {
        return $this->loggers;
    }

    /**
     * Get min log level.
     *
     * @return string Min log level
     */
    protected function minLevel()
    {
        return $this->minLevel;
    }

    /**
     * Get max log level.
     *
     * @return string Max log level
     */
    protected function maxLevel()
    {
        return $this->maxLevel;
    }

    /**
     * Get process name.
     *
     * @return string Process name
     */
    protected function processName()
    {
        return $this->processName;
    }

    /**
     * Get time format.
     *
     * @return string Time format
     */
    protected function timeFormat()
    {
        return $this->timeFormat;
    }

    /**
     * Get message format.
     *
     * @return string Message format
     */
    protected function messageFormat()
    {
        return $this->messageFormat;
    }

    /**
     * Build current time string.
     *
     * @return string Time string
     */
    protected function logTime()
    {
        $uTimestamp = microtime(true);
        $timestamp = floor($uTimestamp);
        $milliseconds = '' . round(($uTimestamp - $timestamp) * 1000000);
        $milliseconds = str_pad($milliseconds, 6, '0');

        return date(preg_replace('/(?<!\\\\)u/', $milliseconds, $this->timeFormat()), $timestamp);
    }

    /**
     * Get session id.
     *
     * @return string Session id
     */
    protected function sessionId()
    {
        return session_id();
    }

    /**
     * Get IP address.
     *
     * @return string IP address
     */
    protected function ipAddress()
    {
        if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Get log level.
     *
     * @param string $level Source log level
     *
     * @return string Log level
     */
    protected function logLevel($level)
    {
        return strtoupper($level);
    }

    /**
     * Get message.
     *
     * @param string $message Source message
     *
     * @return string Message
     */
    protected function message($message)
    {
        return $message;
    }

    /**
     * Build context string.
     *
     * @param array|null $context Log message context
     *
     * @return string Context string
     */
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

    /**
     * Prepare final log message.
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array|null $context Context
     *
     * @return string Log message string
     */
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

