<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Observers;

use Cerpus\EdlibResourceKit\Contract\EdlibResource;
use Cerpus\EdlibResourceKit\Resource\ResourceManagerInterface;
use Cerpus\EdlibResourceKitProvider\Contracts\ConvertableToEdlibResource;

class ResourceObserver
{
    public function __construct(private ResourceManagerInterface $resourceManager)
    {
    }

    public function saved(EdlibResource|ConvertableToEdlibResource $resource): void
    {
        if ($resource instanceof ConvertableToEdlibResource) {
            $resource = $resource->toEdlibResource();
        }

        $this->resourceManager->save($resource);
    }

    // TODO: delete
}
