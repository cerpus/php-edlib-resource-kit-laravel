<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Facades;

use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Illuminate\Support\Facades\Facade;
use Mockery\MockInterface;

/**
 * @method static void save()
 */
class ResourceManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ResourceManagerInterface::class;
    }

    protected static function getMockableClass(): string
    {
        return ResourceManagerInterface::class;
    }

    /**
     * @return MockInterface&ResourceManagerInterface
     */
    public static function fake(): ResourceManagerInterface
    {
        /** @var MockInterface&ResourceManagerInterface $instance */
        $instance = static::createMock();
        static::swap($instance);

        return $instance;
    }
}
