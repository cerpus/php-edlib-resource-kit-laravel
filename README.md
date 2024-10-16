# Edlib Resource Kit integration for Laravel

[![codecov](https://codecov.io/github/cerpus/php-edlib-resource-kit-laravel/branch/master/graph/badge.svg?token=FCQU299HRX)](https://codecov.io/github/cerpus/php-edlib-resource-kit-laravel)

Integrates [Edlib Resource Kit](https://github.com/cerpus/php-edlib-resource-kit)
with Laravel.

## Requirements

* PHP 8.2 or 8.3
* Laravel 9, 10, or 11

## Installation

~~~sh
composer require cerpus/edlib-resource-kit-laravel
~~~

## Configuration

Publish the configuration using:

~~~sh
php artisan vendor:publish --provider="Cerpus\EdlibResourceKitProvider\EdlibResourceKitServiceProvider"
~~~

## License

This package is released under the GNU General Public License 3.0. See the
`LICENSE` file for more information.
