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

namespace Storyblok\Bundle\Tests\Unit\Block\Attribute;

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class AsBlockTest extends TestCase
{
    use FakerTrait;

    /**
     * @test
     */
    public function defaults(): void
    {
        $block = new AsBlock();

        self::assertNull($block->name);
        self::assertNull($block->template);
    }

    /**
     * @test
     */
    public function name(): void
    {
        $block = new AsBlock(
            name: $expected = self::faker()->word(),
        );

        self::assertSame($expected, $block->name);
    }

    /**
     * @test
     */
    public function template(): void
    {
        $block = new AsBlock(
            template: $expected = self::faker()->word(),
        );

        self::assertSame($expected, $block->template);
    }
}
