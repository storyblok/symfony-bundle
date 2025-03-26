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

use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tiptap\EditorBuilderInterface;
use Storyblok\Bundle\Twig\RichTextExtension;
use Tiptap\Editor;

final class RichTextExtensionTest extends TestCase
{
    use FakerTrait;

    /**
     * @test
     */
    public function getFilters(): void
    {
        $builder = $this->createMock(EditorBuilderInterface::class);

        $filters = (new RichTextExtension($builder))->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('rich_text', $filters[0]->getName());
    }

    /**
     * @test
     */
    public function richText(): void
    {
        $expected = self::faker()->randomHtml();

        $editor = $this->createMock(Editor::class);
        $editor->expects(self::once())
            ->method('getHTML')
            ->willReturn($expected);

        $richText = ['type' => 'doc', 'content' => []];

        $builder = $this->createMock(EditorBuilderInterface::class);
        $builder->expects(self::once())
            ->method('getEditor')
            ->with($richText)
            ->willReturn($editor);

        self::assertSame($expected, (new RichTextExtension($builder))->richText($richText));
    }
}
