<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests;

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
use Cerpus\EdlibResourceKit\Oauth1\ValidatorInterface;
use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKit\ResourceKitInterface;
use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Cerpus\EdlibResourceKit\Serializer\ResourceSerializer;
use Cerpus\EdlibResourceKitProvider\Internal\NullCredentialStore;
use Cerpus\EdlibResourceKitProvider\Oauth1\MemoizedValidator;
use Cerpus\PubSub\Connection\ConnectionFactory;
use Cerpus\PubSub\PubSub;
use Generator;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use TypeError;

final class EdlibResourceKitServiceProviderTest extends TestCase
{
    public function testMinimalConfiguration(): void
    {
        $this->assertResourceKitResolves();
    }

    public function testSynchronousResourceManager(): void
    {
        $this->app->make('config')
            ->set('edlib-resource-kit', [
                'synchronous-resource-manager' => true,
            ]);

        $this->assertResourceKitResolves();
    }

    public function testGetResourceManager(): void
    {
        $this->usePubSubMock();

        $this->assertInstanceOf(
            ResourceManagerInterface::class,
            $this->app->make(ResourceManagerInterface::class),
        );
    }

    public function testGetResourceVersionManager(): void
    {
        $this->usePubSubMock();

        $this->assertInstanceOf(
            ResourceVersionManagerInterface::class,
            $this->app->make(ResourceVersionManagerInterface::class),
        );
    }

    public function testCustomHttpClient(): void
    {
        $this->app->instance('my-http-client', $this->createMock(ClientInterface::class));

        $this->app->make('config')->set('edlib-resource-kit.http-client', 'my-http-client');

        $this->assertResourceKitResolves();
    }

    public function testCustomPubSub(): void
    {
        $pubSub = $this->createMock(PubSub::class);

        $this->app->instance('my-pub-sub', $pubSub);
        $this->app->make('config')->set('edlib-resource-kit.pub-sub', 'my-pub-sub');

        $this->assertResourceKitResolves();
    }

    public function testCustomPubSubAsConnectionFactory(): void
    {
        $pubSub = $this->createMock(ConnectionFactory::class);

        $this->app->instance('my-pub-sub', $pubSub);
        $this->app->make('config')->set('edlib-resource-kit.pub-sub', 'my-pub-sub');

        $this->assertResourceKitResolves();
    }

    public function testThrowsOnInvalidConnectionFactory(): void
    {
        $this->app->instance('my-pub-sub', new class {});
        $this->app->make('config')->set('edlib-resource-kit.pub-sub', 'my-pub-sub');

        $this->expectException(TypeError::class);

        $this->assertResourceKitResolves();
    }

    public function testCustomHttpClientMustBeValidService(): void
    {
        $this->app->make('config')->set('edlib-resource-kit.http-client', 'foo');

        $this->expectException(BindingResolutionException::class);

        $this->app->make(ResourceKitInterface::class);
    }

    public function testThrowsOnInvalidHttpClient(): void
    {
        $this->app->instance('foo', new class {});
        $this->app->make('config')->set('edlib-resource-kit.http-client', 'foo');

        $this->expectException(TypeError::class);

        $this->app->make(ResourceKitInterface::class);
    }

    public function testCustomResourceSerializer(): void
    {
        $this->app->instance('my-serializer', $this->createMock(ResourceSerializer::class));
        $this->app->make('config')->set('edlib-resource-kit.resource-serializer', 'my-serializer');

        $this->assertResourceKitResolves();
    }

    public function testThrowsOnInvalidResourceSerializer(): void
    {
        $this->app->instance('my-serializer', new class {});
        $this->app->make('config')->set('edlib-resource-kit.resource-serializer', 'my-serializer');

        $this->expectException(TypeError::class);

        $this->app->make(ResourceKitInterface::class);
    }

    #[DataProvider('provideServiceImplementationsAndInterfaces')]
    public function testInstantiatesServices(string $concrete, string $abstract): void
    {
        $this->assertInstanceOf($concrete, $this->app->make($abstract));
    }

    private function assertResourceKitResolves(): void
    {
        $this->assertInstanceOf(
            ResourceKitInterface::class,
            $this->app->make(ResourceKitInterface::class),
        );
    }

    private function usePubSubMock(): void
    {
        $pubSub = $this->createMock(PubSub::class);
        $this->app->instance(PubSub::class, $pubSub);
        $this->app->make('config')->set('edlib-resource-kit.pub-sub', PubSub::class);
    }

    public static function provideServiceImplementationsAndInterfaces(): Generator
    {
        // LTI 1.1 mappers
        yield 'content-item mapper' => [ContentItemMapper::class, ContentItemMapperInterface::class];
        yield 'content-items mapper' => [ContentItemsMapper::class, ContentItemsMapperInterface::class];
        yield 'image mapper' => [ImageMapper::class, ImageMapperInterface::class];
        yield 'placement advice mapper' => [PlacementAdviceMapper::class, PlacementAdviceMapperInterface::class];

        // LTI 1.1 serializers
        yield 'content-item serializer' => [ContentItemSerializer::class, ContentItemSerializerInterface::class];
        yield 'content-items serializer' => [ContentItemsSerializer::class, ContentItemsSerializerInterface::class];
        yield 'image serializer' => [ImageSerializer::class, ImageSerializerInterface::class];
        yield 'LtiLinkItem serializer' => [LtiLinkItemSerializer::class, LtiLinkItemSerializerInterface::class];
        yield 'FileItem serializer' => [FileItemSerializer::class, FileItemSerializerInterface::class];
        yield 'content-item placement serializer' => [ContentItemPlacementSerializer::class, ContentItemPlacementSerializerInterface::class];

        // OAuth 1.0 services
        yield 'oauth 1.0 credential store' => [NullCredentialStore::class, CredentialStoreInterface::class];
        yield 'oauth 1.0 validator' => [MemoizedValidator::class, ValidatorInterface::class];
        yield 'oauth 1.0 signer' => [Signer::class, SignerInterface::class];
    }
}
