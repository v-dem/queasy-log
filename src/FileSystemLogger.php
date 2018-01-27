<?php

/*
 * Queasy PHP Framework - Logger
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\log;

/**
 * File system logger
 */
class FileSystemLogger extends Logger
{
    const DEFAULT_PATH = 'debug.log';

    /**
     * File system log method.
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array|null $context Context
     */
    public function log($level, $message, array $context = array())
    {
        parent::log($level, $message, $context);

        $preparedMessage = $this->prepareMessage($level, $message, $context);

        file_put_contents($this->path(), $preparedMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get log file path
     *
     * @return string Log file path
     */
    protected function path()
    {
        return $this->config()->get('path', static::DEFAULT_PATH);
    }
}

