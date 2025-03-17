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

namespace Storyblok\Bundle\Twig;

use Storyblok\Bundle\Block\Renderer\RendererInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class BlockExtension extends AbstractExtension
{
    public function __construct(
        private RendererInterface $renderer,
    ) {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('render_block', $this->renderBlock(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array<string, mixed> $values
     */
    public function renderBlock(array $values): string
    {
        return $this->renderer->render($values);
    }
}
