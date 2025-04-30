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

namespace Storyblok\Bundle\Block\Renderer;

use Storyblok\Bundle\Block\BlockRegistry;
use Storyblok\Bundle\Block\Exception\BlockNotFoundException;
use Twig\Environment;
use Webmozart\Assert\Assert;

final readonly class BlockRenderer implements RendererInterface
{
    public function __construct(
        private Environment $twig,
        private BlockRegistry $blocks,
    ) {
    }

    public function render(array|object $values): string
    {
        try {
            if (\is_object($values)) {
                $definition = $this->blocks::get($values::class);

                $block = $values;
            } else {
                Assert::keyExists($values, 'component');
                $name = $values['component'];
                $definition = $this->blocks::byName($name);

                $block = new ($definition->className)($values);
            }

            return $this->twig->render($definition->template, ['block' => $block]);
        } catch (BlockNotFoundException) {
            return '';
        }
    }
}
