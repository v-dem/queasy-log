# [Queasy PHP Framework](https://github.com/v-dem/queasy-app/) - Logger

## Package `v-dem/queasy-log`

Contains logger classes compatible with PSR-3 logger interface. Currently file system and console loggers are implemented.
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
* Package `v-dem/queasy-config`

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
        'class' => queasy\log\FileSystemLogger::class, // Logger class to be instantiated
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

Create config instance:

```php
$config = new queasy\config\Config('config.php');
```

Create logger instance (in this case `class` option can be omitted and will be ignored):

```php
$logger = new queasy\log\FileSystemLogger($config->logger);
```

Another way to create logger instance (it will create an instance of `$config->logger->class`, by default `queasy\log\Logger`
as an aggregate logger will be used):

```php
$logger = queasy\log\Logger::create($config->logger);
```

#### Writing messages to log

Output warning message:

```php
$logger->warning('Test warning message.');
```

In `debug.log` you'll see something like this:

    2017-12-24 16:13:09.302334 EET test [] [] [WARNING] Test warning message.

> If you already have another config tool than `queasy\config` then you can use `queasy\config\Config` as just a proxy,
> for example you can pass an options array to a Config constructor and pass Config instance to Logger constructor:

```php
$customConfig = get_my_custom_config(); // An array with option 'name' => 'value' pairs, may include nested arrays

$logger = new queasy\log\FileSystemLogger(new queasy\config\Config($customConfig));
```

See [`v-dem/queasy-config` Wiki](https://github.com/v-dem/queasy-config/wiki) for more details.

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

