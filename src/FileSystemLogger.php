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
        $preparedMessage = $this->prepareMessage($level, $message, $context);

        $path = isset($this->config()->timeLabel)? sprintf($this->path(), date($this->config()->timeLabel)): $this->path();

        file_put_contents($path, $preparedMessage . PHP_EOL, FILE_APPEND | LOCK_EX);

        return parent::log($level, $message, $context);
    }

    /**
     * Get log file path
     *
     * @return string Log file path
     */
    protected function path()
    {
        return $this->config()('path', static::DEFAULT_PATH);
    }
}

