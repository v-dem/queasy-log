<?php

namespace queasy\log;

trait LoggerTrait
{

    private static function logDebug($message)
    {
        Logger::debug($message, __CLASS__ . ': ');
    }

    private static function logInfo($message)
    {
        Logger::info($message, __CLASS__ . ': ');
    }

    private static function logWarning($message)
    {
        Logger::warning($message, __CLASS__ . ': ');
    }

    private static function logError($message)
    {
        Logger::error($message, __CLASS__ . ': ');
    }

}

