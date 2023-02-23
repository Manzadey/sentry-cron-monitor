# Sentry Cron Monitor for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/manzadey/sentry-cron-monitor.svg?style=flat-square)](https://packagist.org/packages/manzadey/sentry-cron-monitor)
[![Total Downloads](https://img.shields.io/packagist/dt/manzadey/sentry-cron-monitor.svg?style=flat-square)](https://packagist.org/packages/manzadey/sentry-cron-monitor)
<!--delete-->
---
<!--/delete-->

## Installation

You can install the package via composer:

```bash
composer require manzadey/sentry-cron-monitor
```

## Usage

```php
use Manzadey\SentryCronMonitor\CronMonitor;

$monitor = new CronMonitor('dsn');

$monitor->progress('monitorId');
// Get data from progress request
$monitor->getDataProgress();

// our code...

// if a failure is detected in the execution of the task
$monitor->error();

// Get data from error request
$monitor->getDataError();

// or if your task is completed successfully
$monitor->ok(); 

// Get data from ok request
$monitor->getDataOk();

// Get all data from requests
$monitor->getData();

// get exception errors
$monitor->getErrors();

// check exception errors
$monitor->hasErrors();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Manzadey](https://github.com/manzadey)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
