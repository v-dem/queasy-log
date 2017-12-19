<?php

namespace queasy\log;

use queasy\config\ConfigInterface;

class FileSystemLogger extends LoggerAggregate
{
    const DEFAULT_PATH = 'logs/debug.log';

    /**
     * @var string Log file path
     */
    private $path;

    /**
     * Constructor.
     *
     * @param ConfigInterface $config Logger configuration
     *
     * @throws InvalidArgumentException Can be thrown by parent class
     */
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);

        $this->path = $config->get('path', self::DEFAULT_PATH);
    }

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
     * @return Log file path
     */
    protected function path()
    {
        return $this->path;
    }
}

