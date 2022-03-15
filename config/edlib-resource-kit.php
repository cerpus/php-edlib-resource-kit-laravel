<?php

return [

    //
    // If false, resources will be published asynchronously over the message
    // bus. This is fast, but you get no feedback in the event that publishing
    // was unsuccessful.
    //
    // If true, resources will be synchronously published over HTTP. This is
    // slower, but allows you to handle errors while publishing.
    //
    'synchronous-resource-manager' => false,

    //
    // RabbitMQ configuration
    // (only required if 'synchronous-resource-manager' => false):
    //
    'pub-sub' => [
        'host' => env('RABBITMQ_HOST', 'localhost'),
        'port' => env('RABBITMQ_PORT', 5672),
        'username' => env('RABBITMQ_USERNAME', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'secure' => env('RABBITMQ_SECURE', false),
        'ssl_options' => [],
    ],

    //
    // To use a Cerpus\PubSub\PubSub instance from the container instead:
    //
    //'pub-sub' => App\PubSub\MyPubSubService::class,

    //
    // If you need to include extra data while publishing a resource, you can
    // add it using a custom serializer extending
    // Cerpus\EdlibResourceKit\Serializer\ResourceSerializer:
    //
    //'resource-serializer' => App\Serializer\MyCustomSerializer::class,

    //
    // By default, HTTP clients & message factories are discovered
    // automatically. You can override these with your own:
    //
    //'http-client' => App\Http\Clients\MyCustomHttpClient::class,
    //'request-factory' => App\Http\Message\MyCustomRequestFactory::class,
    //'stream-factory' => App\Http\Message\MyCustomStreamFactory::class,
];
