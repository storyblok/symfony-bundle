<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\ContentTypeControllerDefinition;
use Storyblok\Bundle\ContentType\ContentTypeControllerRegistry;
use Storyblok\Bundle\ContentType\ContentTypeRegistry;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Double\ContentType\FailingContentType;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Double\Controller\SampleController;
use Storyblok\Bundle\Tests\Double\Controller\SampleWithSlugController;

final class ContentTypeRegistryTest extends TestCase
{
    #[Test]
    public function existsWithExistingContentTypeReturnsTrue(): void
    {
        $registry = new ContentTypeRegistry(new ContentTypeControllerRegistry([
            SampleController::class => new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, SampleContentType::type()),
        ]));

        self::assertTrue($registry->exists(SampleContentType::class));
    }

    #[Test]
    public function existsWithNonExistingContentTypeReturnsFalse(): void
    {
        $registry = new ContentTypeRegistry(new ContentTypeControllerRegistry([
            SampleController::class => new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, SampleContentType::type()),
        ]));

        self::assertFalse($registry->exists(FailingContentType::class));
    }

    #[Test]
    public function all(): void
    {
        $registry = new ContentTypeRegistry(new ContentTypeControllerRegistry([
            SampleController::class => new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, SampleContentType::type()),
        ]));

        self::assertCount(1, $registry->all());
        self::assertSame([SampleContentType::class], $registry->all());
    }

    #[Test]
    public function allWithMultipleRegisteredControllersWithSameContentTypes(): void
    {
        $registry = new ContentTypeRegistry(new ContentTypeControllerRegistry([
            SampleController::class => new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, SampleContentType::type()),
            SampleWithSlugController::class => new ContentTypeControllerDefinition(SampleWithSlugController::class, SampleContentType::class, SampleContentType::type()),
        ]));

        self::assertCount(1, $registry->all());
        self::assertSame([SampleContentType::class], $registry->all());
    }

    #[Test]
    public function registeredContentTypeNotUsingInterfaceWillThrowInvalidArgumentException(): void
    {
        self::expectException(\InvalidArgumentException::class);

        new ContentTypeRegistry(new ContentTypeControllerRegistry([
            SampleController::class => new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, SampleContentType::type()),
            SampleWithSlugController::class => new ContentTypeControllerDefinition(SampleWithSlugController::class, SampleBlock::class, 'invalid'),
        ]));
    }
}
