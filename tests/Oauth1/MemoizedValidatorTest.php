<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests\Oauth1;

use Cerpus\EdlibResourceKit\Oauth1\Claim;
use Cerpus\EdlibResourceKit\Oauth1\Exception\ValidationException;
use Cerpus\EdlibResourceKit\Oauth1\Request as Oauth1Request;
use Cerpus\EdlibResourceKit\Oauth1\ValidatorInterface;
use Cerpus\EdlibResourceKitProvider\Oauth1\MemoizedValidator;
use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MemoizedValidatorTest extends TestCase
{
    private MockObject&ValidatorInterface $validator;

    private MemoizedValidator $memoizedValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->memoizedValidator = new MemoizedValidator(
            new Request(),
            $this->validator,
        );
    }

    public function testMemoizesSuccessfulAttempts(): void
    {
        $oauth1Request = (new Oauth1Request('GET', 'http://example.com/'))
            ->with(Claim::SIGNATURE, 'the-signature');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($oauth1Request);

        $this->memoizedValidator->validate($oauth1Request);
        $this->memoizedValidator->validate($oauth1Request);
    }

    public function testDoesNotMemoizeValidationFailures(): void
    {
        $oauth1Request = (new Oauth1Request('GET', 'http://example.com/'))
            ->with(Claim::SIGNATURE, 'the-signature');

        $this->validator
            ->expects($this->exactly(2))
            ->method('validate')
            ->willThrowException(new ValidationException());

        try {
            $this->memoizedValidator->validate($oauth1Request);
        } catch (ValidationException) {
        }

        try {
            $this->memoizedValidator->validate($oauth1Request);
        } catch (ValidationException) {
        }
    }
}
