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

namespace Storyblok\Bundle\Tests\Unit\ContentType\Attribute;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\Domain\Value\Resolver\LinkType;
use Storyblok\Api\Domain\Value\Resolver\RelationCollection;
use Storyblok\Api\Domain\Value\Resolver\ResolveLinks;
use Storyblok\Bundle\ContentType\Attribute\AsContentTypeController;
use Storyblok\Bundle\Tests\Double\ContentType\SampleContentType;
use Storyblok\Bundle\Tests\Double\Controller\MultipleContentTypesController;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class AsContentTypeControllerTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function defaults(): void
    {
        $attribute = new AsContentTypeController(SampleContentType::class);

        self::assertNull($attribute->slug);
        self::assertCount(0, $attribute->resolveRelations);
        self::assertNull($attribute->resolveLinks->type);
    }

    #[Test]
    public function contentType(): void
    {
        $attribute = new AsContentTypeController(
            contentType: $expected = SampleContentType::class,
        );

        self::assertSame($expected, $attribute->contentType);
    }

    #[Test]
    public function slug(): void
    {
        $attribute = new AsContentTypeController(
            contentType: SampleContentType::class,
            slug: $expected = self::faker()->word(),
        );

        self::assertSame($expected, $attribute->slug);
    }

    #[Test]
    public function resolveRelations(): void
    {
        $attribute = new AsContentTypeController(
            contentType: SampleContentType::class,
            resolveRelations: $expected = new RelationCollection(['component.field']),
        );

        self::assertSame($expected, $attribute->resolveRelations);
        self::assertCount(1, $attribute->resolveRelations);
    }

    #[Test]
    public function resolveLinks(): void
    {
        $attribute = new AsContentTypeController(
            contentType: SampleContentType::class,
            resolveLinks: $expected = new ResolveLinks(LinkType::Story),
        );

        self::assertSame($expected, $attribute->resolveLinks);
        self::assertSame(LinkType::Story, $attribute->resolveLinks->type);
    }

    #[Test]
    public function isRepeatable(): void
    {
        $reflectionClass = new \ReflectionClass(MultipleContentTypesController::class);
        $attributes = $reflectionClass->getAttributes(AsContentTypeController::class);

        self::assertCount(3, $attributes);

        $slugs = array_map(
            static fn (\ReflectionAttribute $attr) => $attr->newInstance()->slug,
            $attributes,
        );

        self::assertSame([null, '/legal/imprint', '/legal/privacy'], $slugs);
    }
}
