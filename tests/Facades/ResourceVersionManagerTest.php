<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests\Facades;

use Cerpus\EdlibResourceKit\ResourceVersion\ResourceVersionManagerInterface;
use Cerpus\EdlibResourceKitProvider\Facades\ResourceVersionManager;
use Cerpus\EdlibResourceKitProvider\Tests\TestCase;
use Mockery\MockInterface;

class ResourceVersionManagerTest extends TestCase
{
    public function testFake(): void
    {
        $fake = ResourceVersionManager::fake();

        $this->assertInstanceOf(ResourceVersionManagerInterface::class, $fake);
        $this->assertInstanceOf(MockInterface::class, $fake);
    }
}
