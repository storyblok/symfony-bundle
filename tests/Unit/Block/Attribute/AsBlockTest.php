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

namespace Storyblok\Bundle\Tests\Unit\Block\Attribute;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\Attribute\AsBlock;
use Storyblok\Bundle\Tests\Util\FakerTrait;

final class AsBlockTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function defaults(): void
    {
        $block = new AsBlock();

        self::assertNull($block->name);
        self::assertNull($block->template);
    }

    #[Test]
    public function validName(): void
    {
        $block = new AsBlock(
            name: $expected = self::faker()->word(),
        );

        self::assertSame($expected, $block->name);
    }

    #[Test]
    public function template(): void
    {
        $block = new AsBlock(
            template: $expected = self::faker()->word(),
        );

        self::assertSame($expected, $block->template);
    }
}
