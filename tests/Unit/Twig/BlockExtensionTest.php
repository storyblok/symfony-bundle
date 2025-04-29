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

namespace Storyblok\Bundle\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\Renderer\RendererInterface;
use Storyblok\Bundle\Twig\BlockExtension;

final class BlockExtensionTest extends TestCase
{
    #[Test]
    public function getFilters(): void
    {
        $renderer = self::createMock(RendererInterface::class);

        $filters = (new BlockExtension($renderer))->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('render_block', $filters[0]->getName());
    }
}
