<?php

namespace queasy\log;

use queasy\config\ConfigTrait;

use Psr\Log\AbstractLogger;

class Logger implements AbstractLogger
{

    use ConfigTrait;

    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;

    const DEFAULT_PATH = 'logs' . DIRECTORY_SEPARATOR . 'debug.log';

    private static $path = self::DEFAULT_PATH;
    private static $debugMode = true;
    private static $processName = null;
    private static $sendEmail = false;
    private static $mailTo = null;
    private static $timeFormat = 'Y-m-d H:i:s.u T';
    private static $historyLength = 10;

    private static $history;

    public static function init()
    {
        $config = self::config();

        self::$path = $config->get('path', self::$path);
        self::$debugMode = $config->get('debugMode', self::$debugMode);
        self::$processName = $config->get('processName', self::$processName);
        self::$sendEmail = $config->get('sendEmail', self::$sendEmail);
        self::$mailTo = $config->get('mailTo', self::$mailTo);
        self::$timeFormat = $config->get('timeFormat', self::$timeFormat);
        self::$historyLength = $config->get('historyLength', self::$historyLength);

        self::$history = array();
    }

    public static function debug($message, $prefix = '')
    {
        if (self::$debugMode) {
            self::message(self::LEVEL_DEBUG, $message, true, $prefix);
        }
    }

    public static function info($message, $prefix = '')
    {
        self::message(self::LEVEL_INFO, $message, true, $prefix);
    }

    public static function warning($message, $prefix = '')
    {
        self::message(self::LEVEL_WARNING, $message, true, $prefix);
    }

    public static function error($message, $prefix = '')
    {
        self::message(self::LEVEL_ERROR, $message, true, $prefix);
    }

    public function log($level, $message, array $context = array())
    {
        $this->message();
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

