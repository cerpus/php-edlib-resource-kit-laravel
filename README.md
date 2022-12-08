# Edlib Resource Kit integration for Laravel

Integrates [Edlib Resource Kit](https://github.com/cerpus/php-edlib-resource-kit)
with Laravel.

## Requirements

* PHP 8.0, 8.1, or 8.2
* Laravel 9
* A PSR-18 compatible HTTP client (e.g. Guzzle 7)
* PSR-17 compatible HTTP message factories (included with Guzzle 7)

## Installation

~~~sh
composer require cerpus/edlib-resource-kit-laravel
~~~

If using Guzzle 6, you will also need some adapters:

~~~sh
composer require cerpus/edlib-resource-kit-laravel \
    http-interop/http-factory-guzzle \
    php-http/guzzle6-adapter
~~~

## Configuration

First publish the configuration:

~~~sh
php artisan vendor:publish --provider="Cerpus\EdlibResourceKitProvider\EdlibResourceKitServiceProvider"
~~~

For asynchronous publishing, it is required to configure RabbitMQ. Edit
`config/edlib-resource-kit.php`, or provide the expected environment variables
to match your infrastructure.

If using [cerpus/laravel-rabbitmq-pubsub](https://github.com/cerpus/php-laravel-rabbitmq-pubsub),
you can reuse its configuration:

~~~php
<?php

return [
    'pub-sub' => Cerpus\PubSub\PubSub::class,
];
~~~

For synchronous publishing, it is sufficient to provide the following config:

~~~php
<?php

return [
    'synchronous-resource-manager' => true,
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

With the `PublishToEdlib` trait used, Edlib will now be notified upon changes to
the resource.

## Declared services

* `Cerpus\EdlibResourceKit\ResourceKitInterface`
* `Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface`
* `Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface`

## License

This package is released under the GNU General Public License 3.0. See the
`LICENSE` file for more information.
