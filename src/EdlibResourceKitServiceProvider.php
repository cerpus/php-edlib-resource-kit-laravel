<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider;

use Cerpus\EdlibResourceKit\Lti\Edlib\DeepLinking\EdlibContentItemMapper;
use Cerpus\EdlibResourceKit\Lti\Edlib\DeepLinking\EdlibContentItemsSerializer;
use Cerpus\EdlibResourceKit\Lti\Edlib\DeepLinking\EdlibLtiLinkItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemsMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemsMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ImageMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ImageMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\PlacementAdviceMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\PlacementAdviceMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemPlacementSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemPlacementSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemsSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemsSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\FileItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\FileItemSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ImageSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ImageSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\LtiLinkItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\LtiLinkItemSerializerInterface;
use Cerpus\EdlibResourceKit\Oauth1\CredentialStoreInterface;
use Cerpus\EdlibResourceKit\Oauth1\Signer;
use Cerpus\EdlibResourceKit\Oauth1\SignerInterface;
use Cerpus\EdlibResourceKit\Oauth1\Validator;
use Cerpus\EdlibResourceKit\Oauth1\ValidatorInterface;
use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKit\ResourceKit;
use Cerpus\EdlibResourceKit\ResourceKitInterface;
use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Cerpus\EdlibResourceKit\Serializer\ResourceSerializer;
use Cerpus\EdlibResourceKitProvider\Internal\Clock;
use Cerpus\EdlibResourceKitProvider\Internal\NullCredentialStore;
use Cerpus\EdlibResourceKitProvider\Oauth1\MemoizedValidator;
use Cerpus\PubSub\Connection\ConnectionFactory;
use Cerpus\PubSub\PubSub;
use GuzzleHttp\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Random\Randomizer;
use RuntimeException;
use function class_exists;
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

            // LTI 1.1 mappers
            ContentItemsMapperInterface::class,
            ContentItemMapperInterface::class,
            ImageMapperInterface::class,
            PlacementAdviceMapperInterface::class,

            // LTI 1.1 serializers
            ContentItemsSerializerInterface::class,
            ContentItemPlacementSerializerInterface::class,
            ContentItemSerializerInterface::class,
            FileItemSerializerInterface::class,
            ImageSerializerInterface::class,
            LtiLinkItemSerializerInterface::class,

            // OAuth 1.0 services
            CredentialStoreInterface::class,
            SignerInterface::class,
            ValidatorInterface::class,
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

        // LTI 1.1 mappers
        $this->app->singleton(ContentItemsMapperInterface::class, ContentItemsMapper::class);
        $this->app->singleton(ContentItemMapperInterface::class, $this->createContentItemMapper(...));
        $this->app->singleton(ImageMapperInterface::class, ImageMapper::class);
        $this->app->singleton(PlacementAdviceMapperInterface::class, PlacementAdviceMapper::class);

        // LTI 1.1 serializers
        $this->app->singleton(ContentItemsSerializerInterface::class, $this->createContentItemsSerializer(...));
        $this->app->singleton(ContentItemPlacementSerializerInterface::class, ContentItemPlacementSerializer::class);
        $this->app->singleton(ContentItemSerializerInterface::class, ContentItemSerializer::class);
        $this->app->singleton(FileItemSerializerInterface::class, FileItemSerializer::class);
        $this->app->singleton(ImageSerializerInterface::class, ImageSerializer::class);
        $this->app->singleton(LtiLinkItemSerializerInterface::class, $this->createLtiLinkItemSerializer(...));

        // OAuth 1.0 services
        $this->app->singleton(SignerInterface::class, Signer::class);
        $this->app->singleton(ValidatorInterface::class, MemoizedValidator::class);
        $this->app->singletonIf(CredentialStoreInterface::class, NullCredentialStore::class);
        $this->app->when(MemoizedValidator::class)
            ->needs(ValidatorInterface::class)
            ->give(Validator::class);

        // for compatibility
        $this->app->singletonIf(ClockInterface::class, Clock::class);
        $this->app->singletonIf(Randomizer::class);

        if (class_exists(EdlibLtiLinkItemSerializer::class)) {
            $this->app->when(EdlibContentItemMapper::class)
                ->needs(ContentItemMapperInterface::class)
                ->give(ContentItemMapper::class);

            $this->app->when(EdlibContentItemsSerializer::class)
                ->needs(ContentItemsSerializerInterface::class)
                ->give(ContentItemsSerializer::class);

            $this->app->when(EdlibLtiLinkItemSerializer::class)
                ->needs(LtiLinkItemSerializerInterface::class)
                ->give(LtiLinkItemSerializer::class);
        }
    }

    private function assertHasOrDoesNotUseEdlibExtensions(): void
    {
        if (!$this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return;
        }

        if (!class_exists(EdlibContentItemMapper::class)) {
            throw new RuntimeException(
                'The installed version of cerpus/edlib-resource-kit must be upgraded to use Edlib extensions',
            );
        }
    }

    private function createContentItemMapper(): ContentItemMapperInterface
    {
        $this->assertHasOrDoesNotUseEdlibExtensions();

        if ($this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return new EdlibContentItemMapper();
        }

        return $this->app->make(ContentItemMapper::class);
    }

    private function createContentItemsSerializer(): ContentItemsSerializerInterface
    {
        $this->assertHasOrDoesNotUseEdlibExtensions();

        if ($this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return $this->app->make(EdlibContentItemsSerializer::class);
        }

        return $this->app->make(ContentItemsSerializer::class);
    }

    private function createLtiLinkItemSerializer(): LtiLinkItemSerializerInterface
    {
        $this->assertHasOrDoesNotUseEdlibExtensions();

        if ($this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return $this->app->make(EdlibLtiLinkItemSerializer::class);
        }

        return $this->app->make(LtiLinkItemSerializer::class);
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
