<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests;

use Cerpus\EdlibResourceKitProvider\EdlibResourceKitServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EdlibResourceKitServiceProvider::class,
        ];
    }
}
