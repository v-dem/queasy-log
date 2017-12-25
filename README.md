# [Queasy PHP Framework](https://github.com/v-dem/queasy-app/) - Logger

## Package `v-dem/queasy-log`

Contains logger classes compatible with PSR-3 logger interface. Currenly file system and console loggers are implemented.

### Features

* PSR-3 compatible.
* Easy to use.
* Easy to extend.
* Nested loggers support.
* Output message format is fully configurable.

### Requirements

* PHP version 5.3 or higher
* Package `v-dem/queasy-config`

### Documentation

See our [Wiki page](https://github.com/v-dem/queasy-log/wiki).

### Installation

    composer require v-dem/queasy-log:master-dev

### Usage

Let's imagine we have this `config.php`:

```php
<?php
return [
    'logger' => [
        'loggerClass' => 'queasy\log\FileSystemLogger', // Logger class to be instantiated
        'setErrorHandlers' => true, // This option says to set error and exception handlers
        'processName' => 'test', // Process name, to differentiate log messages from different sources
        'minLevel' => 'debug', // Message's minimum acceptable log level
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

Create logger instance:

```php
$logger = queasy\log\Logger::create($config->logger);
```

#### Writing messages to log

Output debug message:

```php
$logger->debug('Test debug message.');
```

In `debug.log` you'll see something like this:

    2017-12-24 16:13:09.302334 EET test [] [] [DEBUG] Test debug message.


