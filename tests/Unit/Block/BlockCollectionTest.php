<?php

declare(strict_types=1);

/**
 * This file is part of sensiolabs-de/storyblok-bundle.
 *
 * (c) SensioLabs Deutschland <info@sensiolabs.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Storyblok\Bundle\Tests\Unit\Block;

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\BlockDefinition;
use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Block\Exception\BlockNotFoundException;
use Storyblok\Bundle\Tests\Double\Block\SampleBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class BlockCollectionTest extends TestCase
{
    use FakerTrait;
    private BlockRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new BlockRegistry();

        $reflection = new \ReflectionClass($this->registry);
        $property = $reflection->getProperty('blocks');
        $property->setValue($this->registry, []);
    }

    /**
     * @test
     */
    public function add(): void
    {
        $faker = self::faker();

        $this->registry::add(new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::assertCount(1, $this->registry);
    }

    /**
     * @test
     */
    public function addWithArray(): void
    {
        $faker = self::faker();

        $values = ['name' => $faker->word(), 'className' => SampleBlock::class, 'template' => $faker->word()];

        $this->registry::add($values);

        self::assertCount(1, $this->registry);
    }

    /**
     * @test
     */
    public function byClass(): void
    {
        $faker = self::faker();

        $this->registry::add($block = new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::assertSame($block, $this->registry::byClass($block->className));
    }

    /**
     * @test
     */
    public function byClassThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $faker = self::faker();

        $this->registry::add(new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::expectException(BlockNotFoundException::class);
        self::expectExceptionMessage(\sprintf('Block "%s" not found.', \stdClass::class));

        $this->registry::byClass(\stdClass::class);
    }

    /**
     * @test
     */
    public function byName(): void
    {
        $faker = self::faker();

        $this->registry::add($block = new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::assertSame($block, $this->registry::byName($block->name));
    }

    /**
     * @test
     */
    public function byNameThrowsExceptionWhenBlockDefinitionWasNotFound(): void
    {
        $faker = self::faker();

        $this->registry::add(new BlockDefinition($faker->word(), SampleBlock::class, $faker->word()));

        self::expectException(BlockNotFoundException::class);

        $this->registry::byName($faker->domainName());
    }
}
