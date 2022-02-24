<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Traits;

use Cerpus\EdlibResourceKitProvider\Observers\ResourceObserver;

/**
 * @method static void observe(object|array|string $classes)
 */
trait PublishToEdlib
{
    public static function bootPublishToEdlib(): void
    {
        static::observe(ResourceObserver::class);
    }
}
