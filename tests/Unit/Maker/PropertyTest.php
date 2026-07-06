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

namespace Storyblok\Bundle\Tests\Unit\Maker;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Maker\Property;
use Storyblok\Bundle\Maker\Type;

final class PropertyTest extends TestCase
{
    #[Test]
    public function mappedProperty(): void
    {
        $property = new Property(key: 'hero_title', name: 'heroTitle', type: Type::String, maxLength: 120);

        self::assertFalse($property->isUnmapped());
        self::assertSame('string', $property->typehint());
        self::assertSame('public string $heroTitle;', $property->declaration());
        self::assertSame("\$this->heroTitle = self::string(\$values, 'hero_title', 120);", $property->assignment());
        self::assertNull($property->phpDoc());
    }

    #[Test]
    public function nullableProperty(): void
    {
        $property = new Property(key: 'image', name: 'image', type: Type::Asset, nullable: true);

        self::assertSame('?Asset', $property->typehint());
        self::assertSame('public ?Asset $image;', $property->declaration());
        self::assertSame("\$this->image = self::nullOrAsset(\$values, 'image');", $property->assignment());
    }

    #[Test]
    public function blocksPropertyHasListPhpDoc(): void
    {
        $property = new Property(key: 'items', name: 'items', type: Type::Blocks, min: 1, max: 5);

        self::assertSame('array', $property->typehint());
        self::assertSame('/** @var list<object> */', $property->phpDoc());
        self::assertSame("\$this->items = self::Blocks(\$values, 'items', 1, 5);", $property->assignment());
    }

    #[Test]
    public function unmappedProperty(): void
    {
        $property = new Property(key: 'seo_meta', name: 'seoMeta', storyblokType: 'custom');

        self::assertTrue($property->isUnmapped());
        self::assertSame(
            '// @TODO $this->seoMeta = ...($values, \'seo_meta\'); // unsupported Storyblok field type "custom"',
            $property->todo(),
        );
    }

    #[Test]
    public function unmappedPropertyCannotBuildAssignment(): void
    {
        $property = new Property(key: 'seo_meta', name: 'seoMeta', storyblokType: 'custom');

        $this->expectException(\LogicException::class);

        $property->assignment();
    }
}
