<?php

/*
 * Queasy PHP Framework - Logger
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\log;

use Psr\Log\LogLevel;

/**
 * Console logger
 */
class ConsoleLogger extends Logger
{
    const DEFAULT_MESSAGE_FORMAT = '[%5$s] %6$s%7$s';

    /**
     * Console log method.
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array|null $context Context
     */
    public function log($level, $message, array $context = array())
    {
        $append = '';
        $prepend = '';
        if ($this->hasColorSupport()) {
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
                    $prepend = "\033[37;41m";
            }

            if ($prepend) {
                $append = "\033[m";
            }
        }

        $preparedMessage = $prepend . $this->prepareMessage($level, $message, $context) . $append;

        file_put_contents('php://stderr', $preparedMessage . PHP_EOL);

        return parent::log($level, $message, $context);
    }

    /**
     * Detect if terminal supports coloured output. Stolen from Symfony.
     *
     * @return bool True if colors supported, false otherwise
     */
    protected function hasColorSupport()
    {
        return
            ((DIRECTORY_SEPARATOR === "\\")
                && ((false !== getenv('ANSICON'))
                    || ('on' === strtolower(getenv('ConEmuANSI')))
                    || ('xterm' === strtolower(getenv('TERM')))))
            || (function_exists('posix_isatty')
                && @posix_isatty(STDOUT));
    }
}

