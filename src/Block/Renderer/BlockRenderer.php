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

namespace Storyblok\Bundle\Block\Renderer;

use Storyblok\Bundle\Block\BlockCollection;
use Storyblok\Bundle\Block\Exception\BlockNotFoundException;
use Twig\Environment;
use Webmozart\Assert\Assert;

final readonly class BlockRenderer implements RendererInterface
{
    public function __construct(
        private Environment $twig,
        private BlockCollection $blocks,
    ) {
    }

    public function render(array $values): string
    {
        try {
            Assert::keyExists($values, 'component');
            $name = $values['component'];

            $definition = $this->blocks->byTechnicalName($name);

            return $this->twig->render($definition->template, ['block' => new ($definition->className)($values)]);
        } catch (BlockNotFoundException) {
            return '';
        }
    }
}
