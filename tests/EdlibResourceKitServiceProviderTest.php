<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests;

use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKit\ResourceKitInterface;
use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Cerpus\EdlibResourceKit\Serializer\ResourceSerializer;
use Cerpus\PubSub\Connection\ConnectionFactory;
use Cerpus\PubSub\PubSub;
use Illuminate\Contracts\Container\BindingResolutionException;
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
