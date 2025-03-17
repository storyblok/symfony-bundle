<?php

declare(strict_types=1);

namespace Storyblok\Bundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\Renderer\RendererInterface;
use Storyblok\Bundle\Twig\BlockExtension;

final class BlockExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function getFilters(): void
    {
        $renderer = $this->createMock(RendererInterface::class);

        $filters = (new BlockExtension($renderer))->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('render_block', $filters[0]->getName());
    }
}
