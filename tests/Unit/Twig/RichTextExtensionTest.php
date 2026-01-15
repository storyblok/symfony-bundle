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

namespace Storyblok\Bundle\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Api\Domain\Type\RichText;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tiptap\EditorBuilderInterface;
use Storyblok\Bundle\Twig\RichTextExtension;
use Tiptap\Editor;

final class RichTextExtensionTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function getFilters(): void
    {
        $builder = self::createMock(EditorBuilderInterface::class);

        $filters = (new RichTextExtension($builder))->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('rich_text', $filters[0]->getName());
    }

    #[Test]
    public function richTextWithArray(): void
    {
        $expected = self::faker()->randomHtml();

        $editor = self::createMock(Editor::class);
        $editor->expects($this->once())
            ->method('getHTML')
            ->willReturn($expected);

        $richText = ['type' => 'doc', 'content' => []];

        $builder = self::createMock(EditorBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getEditor')
            ->with($richText)
            ->willReturn($editor);

        self::assertSame($expected, (new RichTextExtension($builder))->richText(new RichText($richText)));
    }

    #[Test]
    public function richText(): void
    {
        $expected = self::faker()->randomHtml();

        $editor = self::createMock(Editor::class);
        $editor->expects($this->once())
            ->method('getHTML')
            ->willReturn($expected);

        $richText = ['type' => 'doc', 'content' => []];

        $builder = self::createMock(EditorBuilderInterface::class);
        $builder->expects($this->once())
            ->method('getEditor')
            ->with($richText)
            ->willReturn($editor);

        self::assertSame($expected, (new RichTextExtension($builder))->richText(new RichText($richText)));
    }
}
