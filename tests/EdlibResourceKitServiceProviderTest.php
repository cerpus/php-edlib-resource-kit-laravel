<?php

declare(strict_types=1);

namespace Cerpus\EdlibResourceKitProvider\Tests;

use Cerpus\EdlibResourceKit\Lti\Edlib\DeepLinking\EdlibContentItemMapper;
use Cerpus\EdlibResourceKit\Lti\Edlib\DeepLinking\EdlibContentItemsSerializer;
use Cerpus\EdlibResourceKit\Lti\Edlib\DeepLinking\EdlibLtiLinkItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemsMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ContentItemsMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ImageMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\ImageMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\PlacementAdviceMapper;
use Cerpus\EdlibResourceKit\Lti\Lti11\Mapper\DeepLinking\PlacementAdviceMapperInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemPlacementSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemPlacementSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemsSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ContentItemsSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\FileItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\FileItemSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ImageSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\ImageSerializerInterface;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\LtiLinkItemSerializer;
use Cerpus\EdlibResourceKit\Lti\Lti11\Serializer\DeepLinking\LtiLinkItemSerializerInterface;
use Cerpus\EdlibResourceKit\Oauth1\CredentialStoreInterface;
use Cerpus\EdlibResourceKit\Oauth1\Signer;
use Cerpus\EdlibResourceKit\Oauth1\SignerInterface;
use Cerpus\EdlibResourceKit\Oauth1\ValidatorInterface;
use Cerpus\EdlibResourceKitProvider\Internal\NullCredentialStore;
use Cerpus\EdlibResourceKitProvider\Oauth1\MemoizedValidator;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use function class_exists;

final class EdlibResourceKitServiceProviderTest extends TestCase
{
    #[DataProvider('provideServiceImplementationsAndInterfaces')]
    public function testInstantiatesServices(string $concrete, string $abstract): void
    {
        $this->assertInstanceOf($concrete, $this->app->make($abstract));
    }

    #[DataProvider('provideEdlibExtensions')]
    public function testInstantiatesEdlibExtensions(string $concrete, string $abstract): void
    {
        if (!class_exists(EdlibContentItemsSerializer::class)) {
            $this->markTestSkipped('Edlib extensions are not available');
        }

        $this->app->make('config')->set('edlib-resource-kit.use-edlib-extensions', true);

        $this->assertInstanceOf($concrete, $this->app->make($abstract));
    }

    public static function provideServiceImplementationsAndInterfaces(): Generator
    {
        // LTI 1.1 mappers
        yield 'content-item mapper' => [ContentItemMapper::class, ContentItemMapperInterface::class];
        yield 'content-items mapper' => [ContentItemsMapper::class, ContentItemsMapperInterface::class];
        yield 'image mapper' => [ImageMapper::class, ImageMapperInterface::class];
        yield 'placement advice mapper' => [PlacementAdviceMapper::class, PlacementAdviceMapperInterface::class];

        // LTI 1.1 serializers
        yield 'content-item serializer' => [ContentItemSerializer::class, ContentItemSerializerInterface::class];
        yield 'content-items serializer' => [ContentItemsSerializer::class, ContentItemsSerializerInterface::class];
        yield 'image serializer' => [ImageSerializer::class, ImageSerializerInterface::class];
        yield 'LtiLinkItem serializer' => [LtiLinkItemSerializer::class, LtiLinkItemSerializerInterface::class];
        yield 'FileItem serializer' => [FileItemSerializer::class, FileItemSerializerInterface::class];
        yield 'content-item placement serializer' => [ContentItemPlacementSerializer::class, ContentItemPlacementSerializerInterface::class];

        // OAuth 1.0 services
        yield 'oauth 1.0 credential store' => [NullCredentialStore::class, CredentialStoreInterface::class];
        yield 'oauth 1.0 validator' => [MemoizedValidator::class, ValidatorInterface::class];
        yield 'oauth 1.0 signer' => [Signer::class, SignerInterface::class];
    }

    public static function provideEdlibExtensions(): Generator
    {
        yield 'content-item mapper' => [EdlibContentItemMapper::class, ContentItemMapperInterface::class];
        yield 'content-item serializer' => [EdlibContentItemsSerializer::class, ContentItemsSerializerInterface::class];
        yield 'LtiLinkItem serializer' => [EdlibLtiLinkItemSerializer::class, LtiLinkItemSerializerInterface::class];
    }
}
