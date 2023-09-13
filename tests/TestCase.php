<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests;

use Cerpus\EdlibResourceKit\Oauth1\CredentialStoreInterface;
use Cerpus\EdlibResourceKitProvider\EdlibResourceKitServiceProvider;
use Cerpus\EdlibResourceKitProvider\Internal\NullCredentialStore;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->afterApplicationCreated(function () {
            $this->app->singleton(CredentialStoreInterface::class, NullCredentialStore::class);
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            EdlibResourceKitServiceProvider::class,
        ];
    }
}
