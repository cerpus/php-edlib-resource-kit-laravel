<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider;

use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKit\ResourceKit;
use Cerpus\EdlibResourceKit\ResourceKitInterface;
use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Cerpus\EdlibResourceKit\Serializer\ResourceSerializer;
use Cerpus\PubSub\Connection\ConnectionFactory;
use Cerpus\PubSub\PubSub;
use GuzzleHttp\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function is_array;

class EdlibResourceKitServiceProvider extends BaseServiceProvider implements DeferrableProvider
{
    private const CONFIG_PATH = __DIR__ . '/../config/edlib-resource-kit.php';

    public function boot(): void
    {
        $this->publishes([
            self::CONFIG_PATH => $this->app->configPath('edlib-resource-kit.php'),
        ], 'config');
    }

    public function provides(): array
    {
        return [
            ResourceKitInterface::class,
            ResourceManagerInterface::class,
            ResourceVersionManagerInterface::class,
        ];
    }

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'edlib-resource-kit');

        $this->app->singleton(ResourceManagerInterface::class, function () {
            /** @var ResourceKit $resourceKit */
            $resourceKit = $this->app->make(ResourceKitInterface::class);

            return $resourceKit->getResourceManager();
        });

        $this->app->singleton(ResourceVersionManagerInterface::class, function () {
            /** @var ResourceKit $resourceKit */
            $resourceKit = $this->app->make(ResourceKitInterface::class);

            return $resourceKit->getResourceVersionManager();
        });

        $this->app->singleton(ResourceKitInterface::class, function () {
            $synchronousResourceManager = (bool) $this->app->make('config')
                ->get('edlib-resource-kit.synchronous-resource-manager', false);

            return new ResourceKit(
                $synchronousResourceManager ? null : $this->createPubSub(),
                $this->createHttpClient(),
                $this->createRequestFactory(),
                $this->createResourceSerializer(),
                $this->createStreamFactory(),
                $synchronousResourceManager,
            );
        });
    }

    private function createPubSub(): PubSub|ConnectionFactory
    {
        $pubSubService = $this->app
            ->make('config')
            ->get('edlib-resource-kit.pub-sub');

        if (is_array($pubSubService)) {
            $config = $pubSubService;

            return new ConnectionFactory(
                $config['host'],
                (int) $config['port'],
                $config['username'],
                $config['password'],
                $config['vhost'],
                (bool) ($config['secure'] ?? false),
                $config['ssl_options'] ?? [],
            );
        }

        return $this->app->make($pubSubService);
    }

    private function createHttpClient(): ClientInterface
    {
        $httpClientService = $this->app->make('config')
            ->get('edlib-resource-kit.http-client');

        if ($httpClientService) {
            return $this->app->make($httpClientService);
        }

        if (
            class_exists(\GuzzleHttp\ClientInterface::class) &&
            \GuzzleHttp\ClientInterface::MAJOR_VERSION === 7
        ) {
            try {
                // try using Guzzle 7
                return $this->app->make(Client::class);
            } catch (BindingResolutionException) {
            }
        }

        return Psr18ClientDiscovery::find();
    }

    private function createRequestFactory(): RequestFactoryInterface
    {
        $requestFactoryService = $this->app->make('config')
            ->get('edlib-resource-kit.request-factory');

        if ($requestFactoryService) {
            return $this->app->make($requestFactoryService);
        }

        return Psr17FactoryDiscovery::findRequestFactory();
    }

    private function createStreamFactory(): StreamFactoryInterface
    {
        $streamFactoryService = $this->app->make('config')
            ->get('edlib-resource-kit.stream-factory');

        if ($streamFactoryService) {
            return $this->app->make($streamFactoryService);
        }

        return Psr17FactoryDiscovery::findStreamFactory();
    }

    private function createResourceSerializer(): ResourceSerializer
    {
        $serializerService = $this->app->make('config')
            ->get('edlib-resource-kit.resource-serializer');

        return $this->app->make($serializerService ?? ResourceSerializer::class);
    }
}
