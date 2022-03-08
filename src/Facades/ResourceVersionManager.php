<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Facades;

use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersion;
use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Illuminate\Support\Facades\Facade;
use Mockery\MockInterface;

/**
 * @method static ResourceVersion getCurrentVersion(string $resourceId)
 * @method static ResourceVersion getVersion(string $resourceId, string $versionId)
 */
class ResourceVersionManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ResourceVersionManagerInterface::class;
    }

    protected static function getMockableClass(): string
    {
        return ResourceVersionManagerInterface::class;
    }

    /**
     * @return MockInterface&ResourceVersionManagerInterface
     */
    public static function fake(): ResourceVersionManagerInterface
    {
        /** @var MockInterface&ResourceVersionManagerInterface $instance */
        $instance = static::createMock();
        static::swap($instance);

        return $instance;
    }
}
