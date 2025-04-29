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

namespace Storyblok\Bundle\Tests\Unit\Tiptap;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Storyblok\Bundle\Block\Renderer\RendererInterface;
use Storyblok\Bundle\Tests\Util\FakerTrait;
use Storyblok\Bundle\Tiptap\DefaultEditorBuilder;

final class DefaultEditorBuilderTest extends TestCase
{
    use FakerTrait;

    #[Test]
    public function getEditor(): void
    {
        $renderer = self::createMock(RendererInterface::class);

        $editorBuilder = new DefaultEditorBuilder($renderer);

        $document = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->faker()->sentence(),
                        ],
                    ],
                ],
            ],
        ];

        $editor = $editorBuilder->getEditor($document);

        self::assertSame($document, $editor->getDocument());
    }
}
