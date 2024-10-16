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
use Cerpus\EdlibResourceKitProvider\Internal\Clock;
use Cerpus\EdlibResourceKitProvider\Internal\NullCredentialStore;
use Cerpus\EdlibResourceKitProvider\Oauth1\MemoizedValidator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Clock\ClockInterface;
use Random\Randomizer;
use RuntimeException;
use function class_exists;

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

    private function createContentItemMapper(): ContentItemMapperInterface
    {
        if ($this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return new EdlibContentItemMapper();
        }

        return $this->app->make(ContentItemMapper::class);
    }

    private function createContentItemsSerializer(): ContentItemsSerializerInterface
    {
        if ($this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return $this->app->make(EdlibContentItemsSerializer::class);
        }

        return $this->app->make(ContentItemsSerializer::class);
    }

    private function createLtiLinkItemSerializer(): LtiLinkItemSerializerInterface
    {
        if ($this->app->make('config')->get('edlib-resource-kit.use-edlib-extensions')) {
            return $this->app->make(EdlibLtiLinkItemSerializer::class);
        }

        return $this->app->make(LtiLinkItemSerializer::class);
    }
}
