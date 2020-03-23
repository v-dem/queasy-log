[![Build Status](https://travis-ci.com/v-dem/queasy-log.svg?branch=master)](https://travis-ci.com/v-dem/queasy-log)
[![codecov](https://codecov.io/gh/v-dem/queasy-log/branch/master/graph/badge.svg)](https://codecov.io/gh/v-dem/queasy-log)
[![Total Downloads](https://poser.pugx.org/v-dem/queasy-log/downloads)](https://packagist.org/packages/v-dem/queasy-log)
[![License](https://poser.pugx.org/v-dem/queasy-log/license)](https://packagist.org/packages/v-dem/queasy-log)

# [Queasy PHP Framework](https://github.com/v-dem/queasy-app/) - Logger

## Package `v-dem/queasy-log`

Contains logger classes compatible with [PSR-3](https://www.php-fig.org/psr/psr-3/) logger interface. Currently file system and console loggers are implemented.
This package includes these types of logging:

* Logger (base class, can be used as a container for other loggers)
* FileSystemLogger
* ConsoleLogger (supports ANSI color codes)
* SimpleMailLogger (encapsulates `mail()` function)

### Features

* PSR-3 compatible.
* Easy to use.
* Easy to extend.
* Nested loggers support.
* Configurable output message format.

### Requirements

* PHP version 5.3 or higher

### Documentation

See our [Wiki page](https://github.com/v-dem/queasy-log/wiki).

### Installation

    composer require v-dem/queasy-log:master-dev

### Usage

Let's imagine we have the following `config.php`:

```php
<?php
return [
    'logger' => [
        'class' => queasy\log\FileSystemLogger::class, // Logger class
        'processName' => 'test', // Process name, to differentiate log messages from different sources
        'minLevel' => Psr\Log\LogLevel::WARNING, // Message's minimum acceptable log level
        'path' => 'debug.log' // Path to logger output file
    ]
];
```

#### Creating logger instance

Include Composer autoloader:

```php
require_once('vendor/autoload.php');
```

Create config instance (using [`v-dem/queasy-config`](https://github.com/v-dem/queasy-config/) package):

```php
$config = new queasy\config\Config('config.php');
```

Or using arrays:

```php
$config = include('config.php');
```

Create logger instance (in this case `class` option can be omitted and will be ignored):

```php
$logger = new queasy\log\Logger($config);
```

Another way to create logger instance (it will create an instance of `$config->logger->class`, by default `queasy\log\Logger`
as an aggregate logger will be used):

```php
$logger = queasy\log\Logger::create($config);
```

> `FileSystemLogger` and `ConsoleLogger` have default settings and can be used without config. Default log file path for
> `FileSystemLogger` is `debug.log`, default min log level is `Psr\Log\LogLevel::DEBUG` and max is `LogLevel::EMERGENCY`.

#### Writing messages to log

Output warning message:

```php
$logger->warning('Test warning message.');
```

In `debug.log` you'll see something like this:

    2017-12-24 16:13:09.302334 EET test [] [] [WARNING] Test warning message.

#### Chain log messages

```php
$logger
    ->warning('going strange')
    ->error('cannot connect to the database')
    ->emergency('the website is down');
```

#### Using composite/nested loggers

`config.php`:
```php
<?php
return [
    [
        'class' => queasy\log\FileSystemLogger::class,
        'path' => 'debug.full.log',
        'minLevel' => Psr\Log\LogLevel::DEBUG,
        [
            'class' => queasy\log\ConsoleLogger::class,
            'minLevel' => Psr\Log\LogLevel::INFO
        ], [
            'class' => queasy\log\SimpleMailLogger::class,
            'minLevel' => Psr\Log\LogLevel::ALERT,
            'mailTo' => 'john.doe@example.com',
            'subject' => 'Website Alert'
        ]
    ], [
        'class' => queasy\log\FileSystemLogger::class,
        'path' => 'debug.log',
        'minLevel' => Psr\Log\LogLevel::INFO
    ]
];
```

Usage:
```php
$config = new queasy\config\Config('config.php');
$logger = new queasy\log\Logger($config);
$logger->info('Hello, world!');
```

#### Using date/time in log file name (note "%s" there, it will be replaced by current date and/or time formatted as described in `timeLabel`)

`config.php`:
```php
<?php
return [
    [
        'class' => queasy\log\FileSystemLogger::class,
        'path' => 'debug-full.%s.log',
        'timeLabel' => 'Y-m-d',
        'minLevel' => Psr\Log\LogLevel::DEBUG
    ]
];
```

