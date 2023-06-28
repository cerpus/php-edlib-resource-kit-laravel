<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests;

use Cerpus\EdlibResourceKit\Lti\ContentItem\ContentItems;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Mapper\ContentItemsMapper;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Mapper\ContentItemsMapperInterface;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ContentItemPlacementSerializer;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ContentItemPlacementSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ContentItemSerializer;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ContentItemSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ContentItemsSerializer;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ContentItemsSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\FileItemSerializer;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\FileItemSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ImageSerializer;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\ImageSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\LtiLinkItemSerializer;
use Cerpus\EdlibResourceKit\Lti\ContentItem\Serializer\LtiLinkItemSerializerInterface;
use Cerpus\EdlibResourceKit\Oauth1\CredentialStoreInterface;
use Cerpus\EdlibResourceKit\Oauth1\Signer;
use Cerpus\EdlibResourceKit\Oauth1\SignerInterface;
use Cerpus\EdlibResourceKit\Oauth1\Validator;
use Cerpus\EdlibResourceKit\Oauth1\ValidatorInterface;
use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKit\ResourceKitInterface;
use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Cerpus\EdlibResourceKit\Serializer\ResourceSerializer;
use Cerpus\EdlibResourceKitProvider\Internal\NullCredentialStore;
use Cerpus\PubSub\Connection\ConnectionFactory;
use Cerpus\PubSub\PubSub;
use Illuminate\Contracts\Container\BindingResolutionException;
use Psr\Http\Client\ClientInterface;
use TypeError;
use function class_exists;
use function interface_exists;

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

    public function testHasContentItemMappersAndSerializers(): void
    {
        if (!class_exists(ContentItems::class)) {
            $this->markTestSkipped('Older version of Edlib Resource Kit installed, skipping');
        }

        $this->assertInstanceOf(
            ContentItemsMapper::class,
            $this->app->make(ContentItemsMapperInterface::class),
        );
        $this->assertInstanceOf(
            ContentItemPlacementSerializer::class,
            $this->app->make(ContentItemPlacementSerializerInterface::class),
        );
        $this->assertSame(
            ContentItemSerializer::class,
            $this->app->make(ContentItemSerializerInterface::class)::class,
        );
        $this->assertInstanceOf(
            ContentItemsSerializer::class,
            $this->app->make(ContentItemsSerializerInterface::class),
        );
        $this->assertInstanceOf(
            FileItemSerializer::class,
            $this->app->make(FileItemSerializerInterface::class),
        );
        $this->assertInstanceOf(
            ImageSerializer::class,
            $this->app->make(ImageSerializerInterface::class),
        );
        $this->assertInstanceOf(
            LtiLinkItemSerializer::class,
            $this->app->make(LtiLinkItemSerializerInterface::class),
        );
    }

    public function testHasOauth1Services(): void
    {
        if (!interface_exists(SignerInterface::class)) {
            $this->markTestSkipped('Older version of Edlib Resource Kit installed, skipping');
        }

        $this->assertInstanceOf(
            NullCredentialStore::class,
            $this->app->make(CredentialStoreInterface::class),
        );

        $this->assertInstanceOf(
            Signer::class,
            $this->app->make(SignerInterface::class),
        );

        $this->assertInstanceOf(
            Validator::class,
            $this->app->make(ValidatorInterface::class),
        );
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
}
