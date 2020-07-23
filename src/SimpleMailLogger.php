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
 * Simple email logger
 */
class SimpleMailLogger extends Logger
{
    const DEFAULT_MAIL_TO = 'admin@example.com';
    const DEFAULT_SUBJECT = 'Log message';

    /**
     * File system log method.
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array|null $context Context
     */
    public function log($level, $message, array $context = array())
    {
        $preparedMessage = $this->prepareMessage($level, $message, $context) . PHP_EOL;

        $additionalHeaders = '';
        if ($this->mailFrom()) {
            $additionalHeaders .= sprintf("From: %s\r\n", $this->mailFrom());
        }

        $configHeaders = isset($this->config['headers'])? $this->config['headers']: array();
        foreach ($configHeaders as $header) {
            $additionalHeaders .= sprintf("%s\r\n", $header);
        }

        mail($this->mailTo(), $this->subject(), $preparedMessage, $additionalHeaders);

        return parent::log($level, $message, $context);
    }

    /**
     * Get mail "From:" address
     *
     * @return string Email "From:" address
     */
    protected function mailFrom()
    {
        return isset($this->config['mailFrom'])
            ? $this->config['mailFrom']
            : null;
    }

    /**
     * Get mail "To:" address
     *
     * @return string Email "To:" address
     */
    protected function mailTo()
    {
        return isset($this->config['mailTo'])
            ? $this->config['mailTo']
            : static::DEFAULT_MAIL_TO;
    }

    /**
     * Get mail subject
     *
     * @return string Email subject
     */
    protected function subject()
    {
        return isset($this->config['subject'])
            ? $this->config['subject']
            : static::DEFAULT_SUBJECT;
    }
}

