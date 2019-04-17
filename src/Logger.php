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
     * @param ConfigInterface $config Logger config
     *
     * @return int Integer log level value
     */
    public static function create($config)
    {
        $class = $config('class', 'queasy\log\Logger');

        return new $class($config);
    }

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
     * @var array Subordinated loggers
     */
    private $loggers;

    /**
     * @var callable Old error handler
     */
    private $oldErrorHandler;

    /**
     * @var callable Old exception handler
     */
    private $oldExceptionHandler;

    /**
     * The config instance.
     *
     * @var ConfigInterface
     */
    private $config;

    /**
     * Constructor.
     *
     * @param array|ConfigInterface $config Logger configuration
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
        $this->error('UNCAUGHT EXCEPTION.', array(
            'exception' => $ex
        ));

        // TODO: Check if old handler is called automatically
        $oldHandler = $this->oldExceptionHandler();
        if ($oldHandler) {
            return $oldHandler($ex);
        }

        return false;
    }

    /**
     * Sets a config.
     *
     * @param array|ConfigInterface $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function __get($field)
    {
        if ('config' === $field) {
            return $this->config;
        } else {
            InvalidArgumentException::unknownField($field);
        }
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
        if (!$this->loggers) {
            $this->loggers = array();

            foreach ($this->config() as $section) {
                if (is_array($section)
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
        if (is_null($this->config)) {
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
        return isset($this->config['minLevel'])? $this->config['minLevel']: static::DEFAULT_MIN_LEVEL;
    }

    /**
     * Get max log level.
     *
     * @return string Max log level
     */
    protected function maxLevel()
    {
        return isset($this->config['maxLevel'])? $this->config['maxLevel']: static::DEFAULT_MAX_LEVEL;
    }

    /**
     * Get process name.
     *
     * @return string Process name
     */
    protected function processName()
    {
        return isset($this->config['processName'])? $this->config['processName']: null;
    }

    /**
     * Get time format.
     *
     * @return string Time format
     */
    protected function timeFormat()
    {
        return isset($this->config['timeFormat'])? $this->config['timeFormat']: static::DEFAULT_TIME_FORMAT;
    }

    /**
     * Get message format.
     *
     * @return string Message format
     */
    protected function messageFormat()
    {
        return isset($this->config['messageFormat'])? $this->config['messageFormat']: static::DEFAULT_MESSAGE_FORMAT;
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
        } elseif (!is_null($context)) {
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
            . ($errFile? ' in ' . $errFile: '')
            . ($errLine? ' on line ' . $errLine: '');
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
        if (is_null($context)) {
            return '';
        }

        $result = '';
        $ex = null;
        if (isset($context['exception'])) {
            $ex = $context['exception'];

            if (interface_exists('\Throwable') && is_subclass_of($ex, '\Throwable')
                    || ($ex instanceof Exception)) {
                $result .= sprintf('%s%s: %s in %s on line %s%sStack trace:%s%s%s',
                    PHP_EOL,
                    get_class($ex),
                    $ex->getMessage(),
                    $ex->getFile(),
                    $ex->getLine(),
                    PHP_EOL,
                    PHP_EOL,
                    $ex->getTraceAsString(),
                    PHP_EOL
                );

                $previous = $ex->getPrevious();
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

