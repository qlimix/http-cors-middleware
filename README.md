# Http-cors-middleware

[![Travis CI](https://api.travis-ci.org/qlimix/http-cors-middleware.svg?branch=master)](https://travis-ci.org/qlimix/http-cors-middleware)
[![Coveralls](https://img.shields.io/coveralls/github/qlimix/http-cors-middleware.svg)](https://coveralls.io/github/qlimix/http-cors-middleware)
[![Packagist](https://img.shields.io/packagist/v/qlimix/http-cors-middleware.svg)](https://packagist.org/packages/qlimix/http-cors-middleware)
[![MIT License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/qlimix/http-cors-middleware/blob/master/LICENSE)

PSR15 cors middleware implementation

## Install

Using Composer:

~~~
$ composer require qlimix/http-cors-middleware
~~~

## usage

```php
<?php

use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Strategies\Settings;
use Neomerx\Cors\Factory\Factory;
use Qlimix\HttpMiddleware\CorsMiddleware;

$analyzer = new Analyzer(new Settings(), new Factory());
$responseFactory = new ResponseFactoryImplementation();

$corsMiddleware = new CorsMiddleware($analyzer, $responseFactory);

// add to middleware request handler

```

## Testing
To run all unit tests locally with PHPUnit:

~~~
$ vendor/bin/phpunit
~~~

## Quality
To ensure code quality run grumphp which will run all tools:

~~~
$ vendor/bin/grumphp run
~~~

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.
