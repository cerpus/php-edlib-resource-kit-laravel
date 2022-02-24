<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Contracts;

use Cerpus\EdlibResourceKit\Contract\EdlibResource;

interface ConvertableToEdlibResource
{
    public function toEdlibResource(): EdlibResource;
}
