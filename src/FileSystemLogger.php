<?php

namespace queasy\log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

use queasy\config\ConfigInterface;

class Logger implements AbstractLogger
{

    const DEFAULT_TIME_FORMAT = 'Y-m-d H:i:s.u T';
    const DEFAULT_HISTORY_LENGTH = 10;

    public static function level2int($level)
    {
        switch ($level) {
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

    private $path;
    private $debugMode;
    private $processName;
    private $sendEmail;
    private $mailTo;
    private $timeFormat;
    private $historyLength;

    private $history = array();

    public function __construct(ConfigInterface $config)
    {
        $this->path = $config->get('path', self::DEFAULT_PATH);
        $this->debugMode = $config->get('debugMode', false);
        $this->processName = $config->get('processName', '');
        $this->sendEmail = $config->get('sendEmail', false);
        $this->mailTo = $config->get('mailTo', null);
        $this->timeFormat = $config->get('timeFormat', self::DEFAULT_TIME_FORMAT);
        $this->historyLength = $config->get('historyLength', self::DEFAULT_HISTORY_LENGTH);
    }

    public function log($level, $message, array $context = array())
    {
        
    }

    private static function message($level, $message, $sendEmail = true, $prefix = '')
    {
        switch ($level) {
            case self::LEVEL_INFO:
                $messageLevel = 'INFO';
                break;
            case self::LEVEL_ERROR:
                $messageLevel = 'ERROR';
                break;
            case self::LEVEL_WARNING:
                $messageLevel = 'WARNING';
                break;
            case self::LEVEL_DEBUG:
            default:
                $messageLevel = 'DEBUG';
        }

        $ipAddress = '';
        if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = sprintf(' [%s]', $_SERVER['REMOTE_ADDR']);
        }

        $message = sprintf(
            '%s%s [%s]%s [%s] %s%s',
            self::getTimeString(),
            (empty(self::$processName)? '': ' ' . self::$processName),
            session_id(),
            $ipAddress,
            $messageLevel,
            $prefix,
            ((is_object($message) || is_array($message))? print_r($message, true): $message)
        );

        self::appendLogMessage($message);

        if ((self::LEVEL_ERROR == $level)
                && !empty(self::$sendEmail)
                && self::$sendEmail
                && $sendEmail) {
            if (!empty(self::$mailTo)) {
                $historyMessage = '';
                foreach(self::$history as $message) {
                    $historyMessage .= $message . "\n";
                }

                if (!mail(self::$mailTo, (empty(self::$processName)? '': self::$processName . ': ') . 'Error Report', $historyMessage)) {
                    self::message(self::LEVEL_ERROR, 'Failed sending email report to admin.', false);
                }
            } else {
                self::message(self::LEVEL_WARNING, 'Cannot send email report to admin. No recipients configured.', false);
            }
        }
    }

    private static function appendLogMessage($message)
    {
        if (self::$historyLength <= count(self::$history)) {
            array_shift(self::$history);
        }

        self::$history[] = $message;

        file_put_contents(self::$path, $message . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function getTimeString()
    {
        $uTimestamp = microtime(true);
        $timestamp = floor($uTimestamp);
        $milliseconds = '' . round(($uTimestamp - $timestamp) * 1000000);
        $milliseconds = str_pad($milliseconds, 6, '0');
        return date(preg_replace('/(?<!\\\\)u/', $milliseconds, self::$timeFormat), $timestamp);
    }

}

$loggerOldErrorHandler = set_error_handler(function($errLevel, $errMessage, $errFile, $errLine, $errContext) {
    global $loggerOldErrorHandler;

    Logger::error("[$errFile:$errLine] ($errLevel) $errMessage");

    if (!is_null($loggerOldErrorHandler)) {
        return $loggerOldErrorHandler($errLevel, $errMessage, $errFile, $errLine, $errContext);
    }
}, E_ALL | E_STRICT);

set_exception_handler(function($ex) {
    Logger::error('Uncaught exception: ' . $ex->getMessage());
    Logger::info('Exception details: ' . print_r($ex, true));
});

Logger::init();

