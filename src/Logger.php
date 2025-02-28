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

use ArrayAccess;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Logger aggregator class
 */
class Logger extends AbstractLogger
{
    const DEFAULT_MIN_LEVEL = LogLevel::DEBUG;
    const DEFAULT_MAX_LEVEL = LogLevel::EMERGENCY;

    const DEFAULT_TIME_FORMAT = 'Y-m-d H:i:s.u T';

    /**
     * @const string Message format that is acceptable by sprintf() function, passed parameters are (by order)
     *              1) time string,
     *              2) process name,
     *              3) session id,
     *              4) IP address,
     *              5) log level,
     *              6) message,
     *              7) context
     */
    const DEFAULT_MESSAGE_FORMAT = '%1$s %2$s [%3$s] [%4$s] [%5$s] %6$s%7$s';

    /**
     * Create logger instance.
     *
     * Logger class can be specified in config using 'class' option, by default Logger class will be used
     *
     * @param array|ArrayAccess $config Logger config
     *
     * @return int Integer log level value
     */
    public static function create($config = array())
    {
        $class = isset($config['class'])
            ? $config['class']
            : 'queasy\log\Logger';

        return new $class($config);
    }

    private static $logLevels = null;

    /**
     * Translate log level word into an integer value.
     *
     * @param string $level Log level string
     *
     * @return int Integer log level value
     */
    public static function level2int($level)
    {
        if (null === self::$logLevels) {
            self::$logLevels = array(
                LogLevel::DEBUG => 0,
                LogLevel::INFO => 1,
                LogLevel::NOTICE => 2,
                LogLevel::WARNING => 3,
                LogLevel::ERROR => 4,
                LogLevel::CRITICAL => 5,
                LogLevel::ALERT => 6,
                LogLevel::EMERGENCY => 7
            );
        }

        return array_key_exists($level, self::$logLevels)? self::$logLevels[$level]: 0;
    }

    /**
     * @var callable Old error handler
     */
    protected $oldErrorHandler;

    /**
     * @var callable Old exception handler
     */
    protected $oldExceptionHandler;

    /**
     * @var array Subordinated loggers
     */
    private $loggers;

    /**
     * The config instance.
     *
     * @var array|ArrayAccess
     */
    private $config;

    /**
     * Constructor.
     *
     * @param array|ArrayAccess $config Logger configuration
     *
     * @throws InvalidArgumentException When a subordinated logger class doesn't exist or doesn't implement Psr\Log\LoggerInterface
     */
    public function __construct($config = null, $setErrorHandlers = true)
    {
        $this->setConfig($config);

        if ($setErrorHandlers) {
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

        return $this;
    }

    public function emergency($message, array $context = array())
    {
        parent::emergency($message, $context);

        return $this;
    }

    public function alert($message, array $context = array())
    {
        parent::alert($message, $context);

        return $this;
    }

    public function critical($message, array $context = array())
    {
        parent::critical($message, $context);

        return $this;
    }

    public function error($message, array $context = array())
    {
        parent::error($message, $context);

        return $this;
    }

    public function warning($message, array $context = array())
    {
        parent::warning($message, $context);

        return $this;
    }

    public function notice($message, array $context = array())
    {
        parent::notice($message, $context);

        return $this;
    }

    public function info($message, array $context = array())
    {
        parent::info($message, $context);

        return $this;
    }

    public function debug($message, array $context = array())
    {
        parent::debug($message, $context);

        return $this;
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

        // TODO: Check if old handler is called automatically
        if ($this->oldErrorHandler) {
            $handler = $this->oldErrorHandler;

            return $handler($errNo, $errStr, $errFile, $errLine);
        }

        return false;
    }

    /**
     * Exceptions handler.
     *
     * @param Throwable|Exception $e Exception instance
     *
     * @return bool Indicates whether exception was handled or not
     */
    public function handleException($exception)
    {
        $this->error('UNCAUGHT EXCEPTION.', array(
            'exception' => $exception
        ));

        // TODO: Check if old handler is called automatically
        if ($this->oldExceptionHandler) {
            $handler = $this->oldExceptionHandler;

            return $handler($exception);
        }

        return false;
    }

    /**
     * Sets a config.
     *
     * @param array|ArrayAccess $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function __get($field)
    {
        if ('config' === $field) {
            return $this->config;
        }

        throw InvalidArgumentException::unknownField($field);
    }

    /**
     * Get subordinated loggers.
     *
     * @return array Subordinated loggers
     */
    protected function loggers()
    {
        if (!$this->loggers) {
            $this->loggers = array();

            foreach ($this->config() as $section) {
                if ((is_array($section) || is_object($section) && ($section instanceof ArrayAccess))
                        && isset($section['class'])) {
                    $className = $section['class'];
                    if (!class_exists($className)) {
                        throw InvalidArgumentException::loggerNotExists($className);
                    }

                    $interfaceName = 'Psr\Log\LoggerInterface';
                    $interfaces = class_implements($className);
                    if (!$interfaces || !isset($interfaces[$interfaceName])) {
                        throw InvalidArgumentException::interfaceNotImplemented($className, $interfaceName);
                    }

                    $this->loggers[] = new $className($section, false);
                }
            }
        }

        return $this->loggers;
    }

    protected function config()
    {
        if (null === $this->config) {
            $this->setConfig(array());
        }

        return $this->config;
    }

    /**
     * Get min log level.
     *
     * @return string Min log level
     */
    protected function minLevel()
    {
        return isset($this->config['minLevel'])
            ? $this->config['minLevel']
            : static::DEFAULT_MIN_LEVEL;
    }

    /**
     * Get max log level.
     *
     * @return string Max log level
     */
    protected function maxLevel()
    {
        return isset($this->config['maxLevel'])
            ? $this->config['maxLevel']
            : static::DEFAULT_MAX_LEVEL;
    }

    /**
     * Get process name.
     *
     * @return string Process name
     */
    protected function processName()
    {
        return isset($this->config['processName'])
            ? $this->config['processName']
            : null;
    }

    /**
     * Get time format.
     *
     * @return string Time format
     */
    protected function timeFormat()
    {
        return isset($this->config['timeFormat'])
            ? $this->config['timeFormat']
            : static::DEFAULT_TIME_FORMAT;
    }

    /**
     * Get message format.
     *
     * @return string Message format
     */
    protected function messageFormat()
    {
        return isset($this->config['messageFormat'])
            ? $this->config['messageFormat']
            : static::DEFAULT_MESSAGE_FORMAT;
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
        return filter_input(INPUT_SERVER, 'REMOTE_ADDR');
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
     * Build message and context string.
     *
     * @param string $message Source message
     * @param array $context Log message context
     *
     * @return string Message
     */
    protected function messageString($message, array $context = null)
    {
        if (is_object($message) || is_array($message)) {
            $message = print_r($message, true);
        } elseif (null !== $context) {
            foreach ($context as $key => $value) {
                if (false !== strpos($message, '{' . $key . '}')) {
                    $message = str_replace('{' . $key . '}', $value, $message);

                    unset($context[$key]);
                }
            }

            unset($context['exception']);

            $message = $message . (count($context)? PHP_EOL . 'Context: ' . print_r($context, true): '');
        }

        return $message;
    }

    /**
     * Build error string.
     *
     * @param int $errNo Error code
     * @param string $errStr Error message
     * @param string|null $errFile Error file
     * @param int|null $errLine Error line
     *
     * @return string Error string
     */
    protected function errorString($errNo, $errStr, $errFile = null, $errLine = null)
    {
        return $errStr
            . ($errFile
                ? ' in ' . $errFile
                : '')
            . ($errLine
                ? ':' . $errLine
                : '');
    }

    /**
     * Build exception string.
     *
     * @param array|null $context Log message context
     *
     * @return string Context string
     *
     * @throws \InvalidArgumentException When 'exception' key in $context doesn't contain a Throwable or Exception instance
     */
    protected function exceptionString(array $context = null)
    {
        if (null === $context) {
            return '';
        }

        $result = '';
        $exception = null;
        if (isset($context['exception'])) {
            $exception = $context['exception'];

            if (interface_exists('\Throwable') && is_subclass_of($exception, '\Throwable')
                    || ($exception instanceof Exception)) {
                $result .= sprintf('%s%s: %s in %s:%s%sStack trace:%s%s%s',
                    PHP_EOL,
                    get_class($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                    PHP_EOL,
                    PHP_EOL,
                    $exception->getTraceAsString(),
                    PHP_EOL
                );

                $previous = $exception->getPrevious();
                if (is_object($previous)) {
                    $result .= '---' . PHP_EOL;
                    $result .= $this->exceptionString(array('exception' => $previous));
                }
            } else {
                throw InvalidArgumentException::invalidContext();
            }
        }

        return $result;
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
    protected function prepareMessage($level, $message, array $context = null)
    {
        return trim(sprintf(
            $this->messageFormat(),
            $this->logTimeString(),
            $this->processNameString(),
            $this->sessionIdString(),
            $this->ipAddressString(),
            $this->logLevelString($level),
            $this->messageString($message, $context),
            $this->exceptionString($context)
        ));
    }
}

