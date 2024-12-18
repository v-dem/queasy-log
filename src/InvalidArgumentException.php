<?php

/*
 * Queasy PHP Framework - Logger
 *
 * (c) Vitaly Demyanenko <vitaly_demyanenko@yahoo.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace queasy\log;

use Psr\Log\InvalidArgumentException as PsrLogInvalidArgumentException;

/**
 * InvalidArgumentException
 */
class InvalidArgumentException extends PsrLogInvalidArgumentException
{
    public static function loggerNotExists($className)
    {
        return new InvalidArgumentException(sprintf('Logger class "%s" does not exist.', $className));
    }

    public static function interfaceNotImplemented($className, $interfaceName)
    {
        return new InvalidArgumentException(sprintf('Logger class "%s" does not implement "%s".', $className, $interfaceName));
    }

    public static function invalidContext()
    {
        return new InvalidArgumentException('Value of \'exception\' key in log message context does not contain valid Throwable or Exception instance.');
    }

    public static function unknownField($field)
    {
        return new InvalidArgumentException(sprintf('Unknown field "%s"', $field));
    }
}

