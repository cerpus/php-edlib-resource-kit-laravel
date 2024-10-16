<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Internal;

use Cerpus\EdlibResourceKit\Oauth1\Credentials;
use Cerpus\EdlibResourceKit\Oauth1\CredentialStoreInterface;

/**
 * @internal This should not be used outside cerpus/edlib-resource-kit-laravel
 */
final class NullCredentialStore implements CredentialStoreInterface
{
    public function findByKey(string $key): Credentials|null
    {
        return null;
    }
}
