<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Oauth1;

use Cerpus\EdlibResourceKit\Oauth1\Claim;
use Cerpus\EdlibResourceKit\Oauth1\Request as Oauth1Request;
use Cerpus\EdlibResourceKit\Oauth1\ValidatorInterface;
use Illuminate\Http\Request;

/**
 * Remember that an OAuth 1 request was validated earlier in the request, so the
 * replay attack prevention doesn't kick in.
 */
final readonly class MemoizedValidator implements ValidatorInterface
{
    public function __construct(
        private Request $request,
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(Oauth1Request $request): void
    {
        $key = $request->has(Claim::SIGNATURE)
            ? $request->get(Claim::SIGNATURE)
            : null;

        if ($key !== null && $this->request->attributes->has($key)) {
            return;
        }

        $this->validator->validate($request);

        if ($key !== null) {
            // valid signatures are always derived from a secret and randomly
            // generated nonce, so it's safe to use them as keys
            $this->request->attributes->set($key, true);
        }
    }
}
