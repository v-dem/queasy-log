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
        return $this->config()->get('mailFrom', null);
    }

    /**
     * Get mail "To:" address
     *
     * @return string Email "To:" address
     */
    protected function mailTo()
    {
        return $this->config()->get('mailTo');
    }

    /**
     * Get mail subject
     *
     * @return string Email subject
     */
    protected function subject()
    {
        return $this->config()->get('subject', static::DEFAULT_SUBJECT);
    }
}

