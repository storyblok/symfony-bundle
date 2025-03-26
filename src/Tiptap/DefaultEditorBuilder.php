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

namespace Storyblok\Bundle\Tiptap;

use Storyblok\Bundle\Block\Renderer\RendererInterface;
use Storyblok\Tiptap\Extension\Storyblok;
use Tiptap\Editor;

final readonly class DefaultEditorBuilder implements EditorBuilderInterface
{
    public function __construct(
        private RendererInterface $renderer,
    ) {
    }

    public function getEditor(array $values): Editor
    {
        return (new Editor([
            'extensions' => [
                new Storyblok([
                    'blokOptions' => [
                        'renderer' => $this->renderer->render(...),
                    ],
                ]),
            ],
        ]))->setContent($values);
    }
}
