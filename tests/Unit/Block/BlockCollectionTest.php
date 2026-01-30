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

namespace Storyblok\Bundle\Tests\Unit\Block;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\BlockDefinition;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Block\Exception\BlockNotFoundException;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Fixtures\MultipleAttributesBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class BlockCollectionTest extends TestCase
{
    use FakerTrait;

    protected function setUp(): void
    {
        BlockRegistry::$blocks = [];
    }

    #[Test]
    public function add(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add(new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::assertCount(1, $collection);
    }

    #[Test]
    public function addWithArray(): void
    {
        $faker = self::faker();

        $values = ['name' => $faker->word(), 'className' => SampleBlock::class, 'template' => $faker->word()];

        $collection = new BlockRegistry();
        $collection::add($values);

        self::assertCount(1, $collection);
    }

    #[Test]
    public function get(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add($block = new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::assertSame($block, $collection::get($block->className));
    }

    #[Test]
    public function getThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add(new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::expectException(BlockNotFoundException::class);
        self::expectExceptionMessage(\sprintf('Block "%s" not found.', \stdClass::class));

        $collection::get(\stdClass::class);
    }

    #[Test]
    public function byName(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add($block = new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::assertSame($block, $collection::byName($block->name));
    }

    #[Test]
    public function byNameThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add(new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::expectException(BlockNotFoundException::class);

        $collection::byName($faker->domainName());
    }

    #[Test]
    public function addMultipleBlocksWithSameClassButDifferentNames(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add(new BlockDefinition('youtube_embed', MultipleAttributesBlock::class, $faker->word()));
        $collection::add(new BlockDefinition('vimeo_embed', MultipleAttributesBlock::class, $faker->word()));

        self::assertCount(2, $collection);
    }

    #[Test]
    public function getReturnsFirstBlockForClassWithMultipleDefinitions(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add($first = new BlockDefinition('youtube_embed', MultipleAttributesBlock::class, $faker->word()));
        $collection::add(new BlockDefinition('vimeo_embed', MultipleAttributesBlock::class, $faker->word()));

        self::assertSame($first, $collection::get(MultipleAttributesBlock::class));
    }

    #[Test]
    public function byNameReturnsCorrectBlockForClassWithMultipleDefinitions(): void
    {
        $faker = self::faker();

        $collection = new BlockRegistry();
        $collection::add(new BlockDefinition('youtube_embed', MultipleAttributesBlock::class, $faker->word()));
        $collection::add($vimeoEmbed = new BlockDefinition('vimeo_embed', MultipleAttributesBlock::class, $faker->word()));

        self::assertSame($vimeoEmbed, $collection::byName('vimeo_embed'));
    }
}
