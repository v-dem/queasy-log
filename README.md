# [Queasy PHP Framework](https://github.com/v-dem/queasy-app/)

## Package `v-dem/queasy-log`

### Features

* PSR-3 compatible.

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
            'setErrorHandlers' => true, // This option say to set error and exception handlers
            'minLevel' => 'debug', // Min message log level to put message into this logger output
            'path' => 'debug.log' // Path to logger output file
        ]
    ];
```

Include Composer autoloader:

    require_once('vendor/autoload.php');

Create config instance:

    $config = new queasy\config\Config('config.php');

Create logger instance:

    $logger = new queasy\log\Logger::create($config->logger);

Output debug message:

    $logger->debug('Test debug message.');

