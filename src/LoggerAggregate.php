<?php

/*
 * Queasy PHP Framework - Logger
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\log;

use Exception;
use InvalidArgumentException as StandardInvalidArgumentException;

use Psr\Log\InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use queasy\config\ConfigInterface;

/**
 * Logger aggregator class
 */
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

    private $oldErrorHandler;

    private $oldExceptionHandler;

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
                    && isset($section['loggerClass'])) {
                $className = $section['loggerClass'];
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

        // Weird way to detect if we are a top-level Logger in config
        if (!$config['loggerClass']) {
            // TODO: Set error and exception handlers
            $this->info('SETTING EXCEPTION HANDLER....');
            $this->oldErrorHandler = set_error_handler(array($this, 'handleError'));
            $this->oldExceptionHandler = set_exception_handler(array($this, 'handleException'));
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
     * Errors handler.
     *
     * @param int $errNo Error code
     * @param string $errStr Error message
     * @param string|null $errFile Error file
     * @param int|null $errLine Error line
     *
     * @return bool Indicates whether error was handled or not
     */
    public function handleError($errNo, $errStr, $errFile = null, $errLine = null)
    {
        switch ($errNo) {
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
                $logLevel = LogLevel::NOTICE;
                break;

            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $logLevel = LogLevel::WARNING;
                break;

            default:
                $logLevel = LogLevel::ERROR;
        }

        $this->log($logLevel, $this->errorString($errNo, $errStr, $errFile, $errLine));

        $oldHandler = $this->oldErrorHandler();
        if ($oldHandler) {
            return $oldHandler($errNo, $errStr, $errFile, $errLine);
        }

        return false;
    }

    /**
     * Exceptions handler.
     *
     * @param Throwable|Exception $ex Exception instance
     *
     * @return bool Indicates whether exception was handled or not
     */
    public function handleException($ex)
    {
        $this->error('Uncaught', array(
            'exception' => $ex
        ));

        $oldHandler = $this->oldExceptionHandler();
        if ($oldHandler) {
            return $oldHandler($ex);
        }

        return false;
    }

    /**
     * Return old error handler.
     *
     * @return callable Old error handler
     */
    protected function oldErrorHandler()
    {
        return $this->oldErrorHandler;
    }

    /**
     * Return old exception handler.
     *
     * @return callable Old exception handler
     */
    protected function oldExceptionHandler()
    {
        return $this->oldExceptionHandler;
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
    protected function logTimeString()
    {
        $uTimestamp = microtime(true);
        $timestamp = floor($uTimestamp);
        $milliseconds = '' . round(($uTimestamp - $timestamp) * 1000000);
        $milliseconds = str_pad($milliseconds, 6, '0');

        return date(preg_replace('/(?<!\\\\)u/', $milliseconds, $this->timeFormat()), $timestamp);
    }

    /**
     * Get process name string.
     *
     * @return string Process name
     */
    protected function processNameString()
    {
        return $this->processName();
    }

    /**
     * Get session id string.
     *
     * @return string Session id
     */
    protected function sessionIdString()
    {
        return session_id();
    }

    /**
     * Get IP address string.
     *
     * @return string IP address
     */
    protected function ipAddressString()
    {
        if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Get log level string.
     *
     * @param string $level Source log level
     *
     * @return string Log level
     */
    protected function logLevelString($level)
    {
        return strtoupper($level);
    }

    /**
     * Get message string.
     *
     * @param string $message Source message
     *
     * @return string Message
     */
    protected function messageString($message)
    {
        return $message;
    }

    /**
     * Build exception string.
     *
     * @param Throwable|Exception $ex Throwable or Exception instance
     *
     * @return string Exception string
     */
    protected function exceptionString($ex)
    {
        return get_class($ex) . ': ' . $ex->getMessage() . ' in ' . $ex->getFile() . '(' . $ex->getLine() . ')' . PHP_EOL
            . 'Stack trace:' . PHP_EOL
            . $ex->getTraceAsString() . PHP_EOL;
    }

    /**
     * Build error string.
     *
     * @param int $errNo Error code
     * @param string $errStr Error message
     * @param string|null $errFile Error file
     * @param int|null $errLine Error line
     *
     * @return string Exception string
     */
    protected function errorString($errNo, $errStr, $errFile = null, $errLine = null)
    {
        return $errStr
            . ($errFile? ' in ' . $errFile: '')
            . ($errLine? '(' . $errLine . ')': '');
    }

    /**
     * Build context string.
     *
     * @param array|null $context Log message context
     *
     * @return string Context string
     *
     * @throws \InvalidArgumentException When 'exception' key in $context doesn't contain a Throwable or Exception instance
     */
    protected function contextString(array $context = null)
    {
        if (is_null($context)) {
            return '';
        }

        $result = '';
        $ex = null;
        if (isset($context['exception'])) {
            $ex = $context['exception'];

            unset($context['exception']);

            if (interface_exists('\Throwable') && is_subclass_of($ex, '\Throwable')
                    || ($ex instanceof Exception)) {
                $result .= $this->exceptionString($ex);
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
            $this->logTimeString(),
            $this->processNameString(),
            $this->sessionIdString(),
            $this->ipAddressString(),
            $this->logLevelString($level),
            $this->messageString($message),
            $this->contextString($context)
        ));
    }
}

