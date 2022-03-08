<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests\Facades;

use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKitProvider\Facades\ResourceManager;
use Cerpus\EdlibResourceKitProvider\Tests\TestCase;
use Mockery\MockInterface;

class ResourceManagerTest extends TestCase
{
    public function testFake(): void
    {
        $fake = ResourceManager::fake();

        $this->assertInstanceOf(ResourceManagerInterface::class, $fake);
        $this->assertInstanceOf(MockInterface::class, $fake);
    }
}
