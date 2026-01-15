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
