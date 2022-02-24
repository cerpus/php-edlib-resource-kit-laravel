# Edlib Resource Kit integration for Laravel

Integrates [Edlib Resource Kit](https://github.com/cerpus/php-edlib-resource-kit)
with Laravel.

## Requirements

* PHP 8.0 or PHP 8.1
* Laravel 9

## Installation

~~~sh
composer require cerpus/edlib-resource-kit-laravel
~~~

## Configuration

First publish the configuration:

~~~sh
php artisan vendor:publish --provider="Cerpus\EdlibResourceKitProvider\EdlibResourceKitServiceProvider"
~~~

It is required to configure RabbitMQ. Now edit `config/edlib-resource-kit.php`, or
provide the expected environment variables, to match your infrastructure.

If using [cerpus/laravel-rabbitmq-pubsub](https://github.com/cerpus/php-laravel-rabbitmq-pubsub),
you can reuse its configuration:

~~~php
<?php

return [
    'pub-sub' => Cerpus\PubSub\PubSub::class,
];
~~~

## Usage

### Automatic publishing of resources

Given a model that implements `EdlibResource`:

~~~php
use Cerpus\EdlibResourceKit\Contract\EdlibResource;
use Cerpus\EdlibResourceKitProvider\Traits\PublishToEdlib;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements EdlibResource
{
    use PublishToEdlib;
}
~~~

Or a model that implements `ConvertableToEdlibResource`:

~~~php
use Cerpus\EdlibResourceKit\Contract\EdlibResource;
use Cerpus\EdlibResourceKitProvider\Contract\ConvertableToEdlibResource;
use Cerpus\EdlibResourceKitProvider\Traits\PublishToEdlib;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements EdlibResource
{
    use PublishToEdlib;

    public function toEdlibResource(): EdlibResource
    {
        // Return your own data object
        return new ArticleEdlibResource(/* ... */);
    }
}
~~~

With the `PublishToEdlib` Edlib will now be notified upon changes to the resource.

## Declared services

* `Cerpus\EdlibResourceKit\ResourceKit`
* `Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface`
* `Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface`

## License

This package is released under the GNU General Public License 3.0. See the
`LICENSE` file for more information.
