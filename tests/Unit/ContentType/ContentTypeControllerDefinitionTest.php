<?php

declare(strict_types=1);

/**
 * This file is part of storyblok/symfony-bundle.
 *
 * (c) Storyblok GmbH <info@storyblok.com>
 * in cooperation with SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Tests\Unit\ContentType;

use Ergebnis\DataProvider\StringProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\ContentType\ContentTypeControllerDefinition;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Double\Controller\SampleController;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class ContentTypeControllerDefinitionTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function className(): void
    {
        $expected = SampleController::class;

        self::assertSame($expected, (new ContentTypeControllerDefinition($expected, SampleContentType::class, self::faker()->word()))->className);
    }

    #[DataProviderExternal(StringProvider::class, 'arbitrary')]
    #[Test]
    public function classNameInvalid(string $className): void
    {
        self::expectException(\InvalidArgumentException::class);

        new ContentTypeControllerDefinition($className, SampleContentType::class, self::faker()->word());
    }

    #[Test]
    public function contentType(): void
    {
        $expected = SampleContentType::class;

        self::assertSame($expected, (new ContentTypeControllerDefinition(SampleController::class, $expected, self::faker()->word()))->contentType);
    }

    #[DataProviderExternal(StringProvider::class, 'arbitrary')]
    #[Test]
    public function contentTypeInvalid(string $contentType): void
    {
        self::expectException(\InvalidArgumentException::class);

        new ContentTypeControllerDefinition(SampleController::class, $contentType, self::faker()->word());
    }

    #[Test]
    public function contentTypeAndControllerClassMustNotBeTheSame(): void
    {
        self::expectException(\InvalidArgumentException::class);

        new ContentTypeControllerDefinition(SampleController::class, SampleController::class, self::faker()->word());
    }

    #[Test]
    public function type(): void
    {
        $expected = self::faker()->word();

        self::assertSame($expected, (new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $expected))->type);
    }

    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    #[Test]
    public function typeInvalid(string $type): void
    {
        self::expectException(\InvalidArgumentException::class);

        new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, $type);
    }

    #[Test]
    public function slug(): void
    {
        $expected = self::faker()->slug();

        self::assertSame($expected, (new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, self::faker()->word(), $expected))->slug);
    }

    #[Test]
    public function slugCanBeNull(): void
    {
        self::assertNull((new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, self::faker()->word()))->slug);
    }

    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    #[Test]
    public function slugInvalid(string $slug): void
    {
        self::expectException(\InvalidArgumentException::class);

        new ContentTypeControllerDefinition(SampleController::class, SampleContentType::class, self::faker()->word(), $slug);
    }

    #[Test]
    public function fromArrayClassNameKeyDoesNotExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        ContentTypeControllerDefinition::fromArray(['contentType' => SampleContentType::class, 'type' => self::faker()->word()]);
    }

    #[Test]
    public function fromArrayClassNameIsNotString(): void
    {
        $faker = self::faker();

        self::expectException(\InvalidArgumentException::class);

        ContentTypeControllerDefinition::fromArray(['className' => $faker->randomNumber(), 'contentType' => SampleContentType::class, 'type' => $faker->word()]);
    }

    #[Test]
    public function fromArrayContentTyeKeyDoesNotExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        ContentTypeControllerDefinition::fromArray(['className' => SampleController::class, 'type' => self::faker()->word()]);
    }

    #[Test]
    public function fromArrayContentTypeIsNotString(): void
    {
        $faker = self::faker();

        self::expectException(\InvalidArgumentException::class);

        ContentTypeControllerDefinition::fromArray(['className' => SampleController::class, 'contentType' => $faker->randomNumber(), 'type' => $faker->word()]);
    }

    #[Test]
    public function fromArrayTyeKeyDoesNotExist(): void
    {
        self::expectException(\InvalidArgumentException::class);

        ContentTypeControllerDefinition::fromArray(['className' => SampleController::class, 'contentType' => SampleContentType::class]);
    }

    #[Test]
    public function fromArrayTypeIsNotString(): void
    {
        $faker = self::faker();

        self::expectException(\InvalidArgumentException::class);

        ContentTypeControllerDefinition::fromArray(['className' => SampleController::class, 'contentType' => SampleContentType::class, 'type' => $faker->randomNumber()]);
    }

    #[Test]
    public function fromArraySlugIsOptional(): void
    {
        $faker = self::faker();

        self::assertNull(ContentTypeControllerDefinition::fromArray(['className' => SampleController::class, 'contentType' => SampleContentType::class, 'type' => $faker->word()])->slug);
    }
}
