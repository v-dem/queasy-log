# [Queasy PHP Framework](https://github.com/v-dem/queasy-app/)

## Package `v-dem/queasy-log`

Contains logger classes compatible with PSR-3 logger interface. Currenly file system and console loggers are implemented.

### Features

* PSR-3 compatible.
* Easy to use.
* Easy to extend.
* Nested loggers support.
* Output message format is fully configurable.

### Dependencies

#### Production

* PHP version 5.3 or higher
* Package `v-dem/queasy-config`

#### Development

* PHP version 5.6 or higher (PHPUnit requirement)
* PHPUnit 5.7

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
        'minLevel' => 'debug', // Message's minimum acceptable log level
        'path' => 'debug.log' // Path to logger output file
    ]
];
```

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

Output debug message:

```php
$logger->debug('Test debug message.');
```

