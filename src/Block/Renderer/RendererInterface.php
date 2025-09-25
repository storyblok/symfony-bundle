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

interface RendererInterface
{
    /**
     * @param array<string, mixed>|object $values The values of the block coming from Storyblok
     * @param array<string, mixed> $context Additional parameters to pass to the template
     *
     * @return string Returns HTML
     */
    public function render(array|object $values, /* array $context = [] */): string;
}
